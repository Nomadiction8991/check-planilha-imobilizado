<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CsvParserService;
use App\Services\ImportacaoService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;
use Throwable;

/**
 * Contrato do backend padrão na opção `--todas` de
 * `checkplanilha:processar-importacoes`.
 *
 * O deploy padrão (`docker-compose.yml` + `.env.example`) fornece apenas
 * MySQL: `DB_CONNECTION=mysql` é a conexão padrão da aplicação. Dois
 * defeitos tornavam `--todas` inútil nesse ambiente:
 *
 *  1. `getPendingImports()` consultava `DB::connection('pgsql')` com o nome
 *     fixo. Sem servidor PostgreSQL — ou sem a tabela naquela conexão — a
 *     lista de pendentes nunca vinha do MySQL onde as importações esperam.
 *  2. a consulta engolia a exceção e devolvia `[]`; `handle()` traduzia lista
 *     vazia em "Nenhuma importação pendente encontrada." com exit code 0.
 *
 * Estes testes fixam o contrato de ponta a ponta, com o comando real rodando
 * pelo kernel do console: o batch é buscado na conexão padrão (MySQL,
 * simulada sobre um arquivo SQLite para o runner não depender de servidor),
 * processa o registro pendente e sai com 0; quando a consulta falha, o
 * comando sai com código de falha e não anuncia sucesso.
 *
 * A lógica de importação não está em teste: `ImportacaoService` e
 * `CsvParserService` são dubês. O banco é acessado de verdade apenas pela
 * consulta dos IDs pendentes — que é exatamente o que este contrato fixa.
 */
final class ProcessarImportacoesTodasBackendPadraoTest extends TestCase
{
    private const ID_PENDENTE = 888;

    private const ID_CONCLUIDA = 889;

    /** @var string[] Exceções entregues ao handler da aplicação. */
    private array $relatadas = [];

    /** @var int[] IDs que o comando tentou processar. */
    private array $processados = [];

    /** @var array<int, array{conexao: string, sql: string}> Queries ouvidas no kernel. */
    private array $consultas = [];

    private string $arquivoBanco = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->relatadas = [];
        $this->processados = [];
        $this->consultas = [];

        // A conexão padrão se chama 'mysql' — como no deploy real
        // (DB_CONNECTION=mysql) — e roda sobre um arquivo SQLite: reproduz o
        // backend padrão sem exigir servidor MySQL no runner. O arquivo (em
        // vez de :memory:) mantém as duas conexões fisicamente separadas,
        // provando que o batch vem de *uma* conexão específica, a padrão.
        $this->arquivoBanco = sys_get_temp_dir() . '/checkplanilha-todas-backend-padrao.sqlite';
        @unlink($this->arquivoBanco);
        // SQLite em arquivo precisa do path existir antes de conectar.
        touch($this->arquivoBanco);

        Config::set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => $this->arquivoBanco,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('database.default', 'mysql');

        // A outra conexão da aplicação (a 'pgsql' que o código consultava
        // por nome fixo) fica isolada, em memória e sem a tabela: se o
        // comando ainda consultar por aqui, a pendência simplesmente não
        // aparece do lado certo.
        Config::set('database.connections.pgsql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge();

        $this->criarSchema(DB::connection());
        $this->semear(DB::connection(), [
            [self::ID_PENDENTE, 'aguardando'],
            [self::ID_CONCLUIDA, 'concluida'],
        ]);

        // Os ouvintes entram depois da semeadura: só o que o comando
        // executar durante a chamada do Artisan fica registrado.
        $this->ouvirConsultas();

        // O handler real escreveria no log do Laravel; o espião guarda a
        // mensagem para a asserção.
        $this->mock(
            ExceptionHandler::class,
            function (MockInterface $handler): void {
                $handler->shouldReceive('report')
                    ->andReturnUsing(function (Throwable $e): void {
                        $this->relatadas[] = $e->getMessage();
                    });
            },
        );
    }

    protected function tearDown(): void
    {
        if ($this->arquivoBanco !== '' && is_file($this->arquivoBanco)) {
            @unlink($this->arquivoBanco);
        }

        parent::tearDown();
    }

    /**
     * Cenário do relato do bug: backend padrão MySQL com importação
     * aguardando. O comando precisa buscá-la na conexão padrão, processá-la
     * e sair com sucesso.
     */
    public function test_todas_processa_importacao_pendente_do_backend_padrao(): void
    {
        $resultado = $this->processarTodas();

        self::assertSame(
            Command::SUCCESS,
            $resultado['exitCode'],
            'Com importação pendente no backend padrão, o batch precisa ser processado com sucesso.',
        );
        self::assertSame(
            [self::ID_PENDENTE],
            $this->processados,
            'A importação aguardando precisa ser processada; a concluída não entra no lote.',
        );
        $this->assertStringContainsString('Processando importação #' . self::ID_PENDENTE, $resultado['output']);
        $this->assertStringContainsString('Total: 1 registros importados.', $resultado['output']);
        $this->assertStringNotContainsString('Nenhuma importação pendente encontrada.', $resultado['output']);

        self::assertSame([], $this->relatadas, 'Caminho saudável não registra exceção.');
        $this->assertBatchBuscadoNaConexaoPadrao();
    }

    /**
     * O batch não pode vir de outra conexão que não a padrão da aplicação:
     * o que define "pendente" é o banco configurado em `database.default`.
     */
    private function assertBatchBuscadoNaConexaoPadrao(): void
    {
        $consultas = $this->consultasDeImportacoes();

        self::assertNotEmpty(
            $consultas,
            'O comando precisa consultar a tabela importacoes para descobrir as pendências.',
        );

        foreach ($consultas as $consulta) {
            self::assertSame(
                config('database.default'),
                $consulta['conexao'],
                'A lista de pendências tem de vir da conexão padrão da aplicação (o MySQL do deploy), nunca de uma conexão com nome fixo.',
            );
        }
    }

    /**
     * Falha de banco ao montar o lote: nada foi processado, então o comando
     * não pode sinalizar sucesso ao cron/CI.
     */
    public function test_falha_na_consulta_do_backend_padrao_sai_com_codigo_de_erro(): void
    {
        $this->derrubarTabelaImportacoes();

        $resultado = $this->processarTodas();

        self::assertSame(
            Command::FAILURE,
            $resultado['exitCode'],
            'Falha ao buscar os pendentes precisa gerar código de falha — sair com 0 faria o runner marcar o batch como sucesso sem processar nada.',
        );
        self::assertSame([], $this->processados, 'Sem lista de pendentes, nenhuma importação pode ser processada.');
        $this->assertStringNotContainsString('Nenhuma importação pendente encontrada.', $resultado['output']);
        $this->assertStringNotContainsString('Total:', $resultado['output']);
        $this->assertStringContainsString('no such table: importacoes', $resultado['output']);

        self::assertCount(1, $this->relatadas, 'A exceção da consulta precisa chegar ao handler da aplicação.');
        $this->assertStringContainsString('no such table: importacoes', $this->relatadas[0]);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * Roda `--todas` e devolve exit code + saída em uma única leitura.
     *
     * `Artisan::output()` drena o buffer do Symfony a cada chamada: a
     * segunda devolve string vazia. Por isso a saída é lida uma vez.
     *
     * @return array{exitCode: int, output: string}
     */
    private function processarTodas(): array
    {
        $this->app->instance(ImportacaoService::class, $this->importService());
        $this->app->instance(CsvParserService::class, $this->csvParser());

        $exitCode = Artisan::call('checkplanilha:processar-importacoes', ['--todas' => true]);

        return [
            'exitCode' => $exitCode,
            'output' => Artisan::output(),
        ];
    }

    /**
     * Registra as queries que tocam a tabela importacoes, com o nome da
     * conexão usada — é assim que se prova *qual banco* respondeu.
     */
    private function ouvirConsultas(): void
    {
        $registrar = function (QueryExecuted $query): void {
            if (stripos($query->sql, 'from "importacoes"') === false) {
                return;
            }

            $this->consultas[] = [
                'conexao' => (string) $query->connectionName,
                'sql' => $query->sql,
            ];
        };

        DB::connection()->listen($registrar);
        DB::connection('pgsql')->listen($registrar);
    }

    /**
     * @return array<int, array{conexao: string, sql: string}>
     */
    private function consultasDeImportacoes(): array
    {
        return array_values($this->consultas);
    }

    private function importService(): ImportacaoService
    {
        $mock = \Mockery::mock(ImportacaoService::class);

        $mock->shouldReceive('buscarProgresso')
            ->andReturnUsing(function ($id) {
                $this->processados[] = (int) $id;

                return ['id' => (int) $id, 'status' => 'aguardando'];
            });

        $mock->shouldReceive('processarComAcoes')
            ->andReturn(['sucesso' => 1, 'erro' => 0]);

        return $mock;
    }

    private function csvParser(): CsvParserService
    {
        $mock = \Mockery::mock(CsvParserService::class);
        $mock->shouldReceive('carregarAnalise')->andReturn([
            'registros' => [['linha_csv' => 30, 'status' => 'novo']],
        ]);

        return $mock;
    }

    private function criarSchema(\Illuminate\Database\Connection $connection): void
    {
        $this->executar($connection, 'CREATE TABLE IF NOT EXISTS importacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            status VARCHAR(50) DEFAULT \'aguardando\'
        )');
    }

    /**
     * @param array<int, array{0: int, 1: string}> $linhas
     */
    private function semear(\Illuminate\Database\Connection $connection, array $linhas): void
    {
        foreach ($linhas as [$id, $status]) {
            $this->executar($connection, 'INSERT INTO importacoes (id, status) VALUES (?, ?)', [$id, $status]);
        }
    }

    /**
     * Remove a tabela da conexão padrão: a consulta dos pendentes passa a
     * falhar com erro real de banco, não com exceção de mock.
     */
    private function derrubarTabelaImportacoes(): void
    {
        $this->executar(DB::connection(), 'DROP TABLE importacoes');
    }

    /**
     * @param array<mixed> $bindings
     */
    private function executar(\Illuminate\Database\Connection $connection, string $sql, array $bindings = []): void
    {
        $statement = $connection->getPdo()->prepare($sql);
        $statement->execute($bindings);
        $statement->closeCursor();
        unset($statement);
    }
}

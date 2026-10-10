<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CsvParserService;
use App\Services\ImportacaoService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;
use Throwable;

/**
 * Contrato de erro da consulta de pendentes de `--todas`.
 *
 * Bug (P2): `getPendingImports()` capturava qualquer exceção da consulta de
 * IDs pendentes e devolvia lista vazia. `handle()` interpretava lista vazia
 * como "nada a fazer": imprimia "Nenhuma importação pendente encontrada." e
 * retornava 0. No deploy padrão (docker-compose + .env.example) só existe
 * MySQL, e a conexão padrão do comando falhava — o batch inteiro deixava de
 * processar importações sinalizando sucesso ao cron/CI.
 *
 * Estes testes não tocam a lógica de importação: ImportacaoService e
 * CsvParserService são dubês. O que se fixa é o comportamento do comando
 * quando a consulta de pendentes quebra: erro visível, exceção registrada e
 * exit code diferente de zero.
 */
final class ProcessarImportacoesFalhaConsultaBancoTest extends TestCase
{
    private const ID_PENDENTE = 555;

    private const ID_PENDENTE_2 = 556;

    private const ID_CONCLUIDA = 557;

    /** @var string[] Mensagens das exceções capturadas pelo espião do handler. */
    private array $relatadas = [];

    /** @var int[] IDs que o comando tentou processar. */
    private array $processados = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->relatadas = [];
        $this->processados = [];

        // Conexão padrão sobre SQLite: reproduz o deploy padrão (onde
        // DB_CONNECTION=mysql) sem exigir um servidor MySQL no runner.
        Config::set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('database.default', 'mysql');

        DB::purge();

        $this->criarSchema(DB::connection());
        $this->semear(DB::connection(), [[self::ID_CONCLUIDA, 'concluida']]);

        // O handler real tenta escrever no log do Laravel; o espião apenas
        // guarda a mensagem para poder afirmar sobre ela.
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

    /**
     * A consulta de pendentes quebra: o erro tem de aparecer na saída e o
     * comando não pode devolver código de sucesso.
     */
    public function test_falha_na_consulta_de_pendentes_retorna_codigo_de_erro(): void
    {
        $resultado = $this->processarTodasComBancoCaido();

        self::assertSame(
            Command::FAILURE,
            $resultado['exitCode'],
            'Falha na consulta de pendentes precisa gerar exit code diferente de zero, senao o runner marca o batch como sucesso.',
        );
        $this->assertStringContainsString('no such table: importacoes', $resultado['output']);
    }

    /**
     * Nenhuma mensagem de sucesso pode conviver com a falha: o operador que
     * lê o stdout do cron precisa ver que nada foi processado.
     */
    public function test_falha_na_consulta_nao_imprime_mensagem_de_sucesso(): void
    {
        $resultado = $this->processarTodasComBancoCaido();

        $this->assertStringNotContainsString('Nenhuma importação pendente encontrada.', $resultado['output']);
        $this->assertStringNotContainsString('Total:', $resultado['output']);
    }

    /**
     * A exceção precisa chegar ao handler da aplicação — antes o rastro
     * existia apenas no stdout do cron, que ninguém lê depois.
     */
    public function test_falha_na_consulta_registra_a_excecao(): void
    {
        $this->processarTodasComBancoCaido();

        self::assertCount(1, $this->relatadas, 'A exceção da consulta precisa ser registrada, não apenas engolida.');
        $this->assertStringContainsString('no such table: importacoes', $this->relatadas[0]);
    }

    /**
     * Teste de controle: com a consulta funcionando e pendentes presentes, o
     * mesmo caminho processa as importações e devolve 0.
     */
    public function test_consulta_saudavel_com_pendentes_mantem_exit_code_de_sucesso(): void
    {
        $this->semear(DB::connection(), [
            [self::ID_PENDENTE, 'aguardando'],
            [self::ID_PENDENTE_2, 'aguardando'],
        ]);

        $resultado = $this->processarTodas();

        self::assertSame(Command::SUCCESS, $resultado['exitCode']);
        self::assertSame([self::ID_PENDENTE, self::ID_PENDENTE_2], $this->processados);
        self::assertSame([], $this->relatadas, 'Nenhuma exceção no caminho saudável.');
    }

    /**
     * Controle do controle: lista realmente vazia continua sendo sucesso,
     * para a correção não transformar "nada a fazer" em falha.
     */
    public function test_sem_pendentes_mantem_mensagem_e_exit_code_de_sucesso(): void
    {
        $resultado = $this->processarTodas();

        self::assertSame(Command::SUCCESS, $resultado['exitCode']);
        self::assertSame([], $this->processados);
        $this->assertStringContainsString('Nenhuma importação pendente encontrada.', $resultado['output']);
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    /**
     * Roda `--todas` e devolve exit code + saída em uma única leitura.
     *
     * Artisan::output() drena o buffer do Symfony a cada chamada: a segunda
     * devolve string vazia. Por isso a saída é lida uma única vez e guardada
     * junto do exit code.
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
     * Mesmo caminho com a consulta de pendentes quebrada de verdade: a
     * tabela sai da conexão padrão, então o SELECT falha com erro real de
     * banco (não exceção de mock).
     *
     * @return array{exitCode: int, output: string}
     */
    private function processarTodasComBancoCaido(): array
    {
        $this->derrubarTabelaImportacoes();

        return $this->processarTodas();
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
        $this->executar($connection, 'CREATE TABLE importacoes (
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
     * Remove a tabela da conexão padrão: a próxima consulta
     * `SELECT ... FROM importacoes` falha com erro real de banco.
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

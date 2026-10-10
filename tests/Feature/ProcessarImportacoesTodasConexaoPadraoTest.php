<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CsvParserService;
use App\Services\ImportacaoService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Contrato de conexao da opcao --todas de checkplanilha:processar-importacoes.
 *
 * Bug (P2): `getPendingImports()` consultava `DB::connection('pgsql')` de
 * forma fixa. O ambiente padrao (docker-compose + .env.example) fornece
 * apenas MySQL — logo `--todas` batia no PostgreSQL, nao achava nada, o
 * catch convertia o erro em lista vazia e o comando imprimia
 * "Nenhuma importacao pendente encontrada." saindo com codigo 0, mesmo com
 * importacoes aguardando no banco configurado.
 *
 * Estes testes nao tocam a logica de importacao: ImportacaoService e
 * CsvParserService sao dubes, para isolar o unico acesso real a banco —
 * a consulta dos IDs pendentes. O que se fixa aqui e *qual conexao* ela
 * usa: a padrao da aplicacao, qualquer que seja o driver configurado.
 */
final class ProcessarImportacoesTodasConexaoPadraoTest extends TestCase
{
    private const ID_PENDENTE = 555;

    private const ID_PENDENTE_2 = 556;

    private const ID_CONCLUIDA = 557;

    private const ID_OUTRA_CONEXAO = 999;

    /** @var int[] IDs que o comando tentou processar. */
    private array $processados = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->processados = [];

        // A conexao padrao passa a se chamar 'mysql' e roda sobre SQLite:
        // reproduz o deploy padrao (onde DB_CONNECTION=mysql) sem exigir um
        // servidor MySQL no runner. O ponto do teste e que o comando use a
        // conexao *padrao*, nao que o driver seja MySQL de verdade.
        Config::set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('database.default', 'mysql');

        // Conexao pgsql isolada, com schema proprio: se o comando continuar
        // consultando o PostgreSQL, os registros daqui aparecem — e o teste
        // falha porque a conexao padrao e que deve responder.
        Config::set('database.connections.pgsql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge();

        $this->criarSchema(DB::connection());
        $this->criarSchema(DB::connection('pgsql'));
    }

    public function test_todas_processa_as_importacoes_pendentes_da_conexao_padrao(): void
    {
        $this->semear(DB::connection(), [
            [self::ID_PENDENTE, 'aguardando'],
            [self::ID_PENDENTE_2, 'aguardando'],
            [self::ID_CONCLUIDA, 'concluida'],
        ]);

        $exitCode = $this->processarTodas();

        self::assertSame(0, $exitCode, 'Processar as pendentes nao pode falhar.');
        self::assertSame(
            [self::ID_PENDENTE, self::ID_PENDENTE_2],
            $this->processados,
            'Todas as importacoes aguardando da conexao padrao precisam ser processadas, em ordem de id.',
        );

        $output = Artisan::output();
        $this->assertStringContainsString('Processando importação #' . self::ID_PENDENTE, $output);
        $this->assertStringContainsString('Processando importação #' . self::ID_PENDENTE_2, $output);
        $this->assertStringNotContainsString('Nenhuma importação pendente encontrada', $output);
    }

    public function test_todas_nao_consulta_registros_de_outra_conexao(): void
    {
        // So a conexao padrao tem a importacao 555; a pgsql tem a 999.
        $this->semear(DB::connection(), [[self::ID_PENDENTE, 'aguardando']]);
        $this->semear(DB::connection('pgsql'), [[self::ID_OUTRA_CONEXAO, 'aguardando']]);

        $exitCode = $this->processarTodas();

        self::assertSame(0, $exitCode);
        self::assertSame(
            [self::ID_PENDENTE],
            $this->processados,
            'A lista de pendentes precisa vir da conexao padrao configurada, nunca de outra conexao fixa.',
        );
        self::assertNotContains(self::ID_OUTRA_CONEXAO, $this->processados);
    }

    public function test_todas_sem_pendentes_na_conexao_padrao_informa_lista_vazia(): void
    {
        $this->semear(DB::connection(), [[self::ID_CONCLUIDA, 'concluida']]);

        $exitCode = $this->processarTodas();

        self::assertSame(0, $exitCode);
        self::assertSame([], $this->processados, 'Nada aguardando, nada a processar.');

        $output = Artisan::output();
        $this->assertStringContainsString('Nenhuma importação pendente encontrada.', $output);
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    private function processarTodas(): int
    {
        $this->app->instance(ImportacaoService::class, $this->importService());
        $this->app->instance(CsvParserService::class, $this->csvParser());

        return Artisan::call('checkplanilha:processar-importacoes', ['--todas' => true]);
    }

    /**
     * O servico duble apenas registra os IDs que o comando tentou
     * processar — e devolve um destino de sucesso, para o exit code
     * depender so da consulta de pendentes que se quer verificar.
     */
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
        $statement = $connection->getPdo()->prepare(
            'CREATE TABLE IF NOT EXISTS importacoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                status VARCHAR(50) DEFAULT \'aguardando\'
            )',
        );
        $statement->execute();
        $statement->closeCursor();
        unset($statement);
    }

    /**
     * @param array<int, array{0: int, 1: string}> $linhas
     */
    private function semear(\Illuminate\Database\Connection $connection, array $linhas): void
    {
        foreach ($linhas as [$id, $status]) {
            $statement = $connection->getPdo()->prepare(
                'INSERT INTO importacoes (id, status) VALUES (?, ?)',
            );
            $statement->execute([$id, $status]);
            $statement->closeCursor();
            unset($statement);
        }
    }
}

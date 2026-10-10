<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImportacaoService;
use App\Services\LegacySpreadsheetImportService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Session;
use PDO;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * O bloco de reaproveitamento de `confirmImport()` consultava o progresso da
 * importação dentro de um try/catch que engolia a exceção sem registrar nada.
 * Quando o lookup falhava (banco fora, PDO quebrado, timeout), o usuário via
 * apenas "Falha ao concluir a importação." sem nenhum vestígio do motivo real.
 */
final class LegacySpreadsheetImportServiceReuseLookupTest extends TestCase
{
    private LegacySpreadsheetImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LegacySpreadsheetImportService;
    }

    public function test_falha_no_lookup_de_reaproveitamento_e_registrada_em_log(): void
    {
        $handler = $this->mock(ExceptionHandler::class);
        $handler->shouldReceive('report')
            ->once()
            ->with(\Mockery::type(RuntimeException::class));

        $resultado = $this->invokeReuseLookup($this->importacaoServiceSemTabela());

        self::assertNull(
            $resultado,
            'Sem importação concluída o método deve devolver null (sem resultado de reaproveitamento).',
        );
    }

    public function test_falha_no_lookup_nao_interrompe_o_fluxo(): void
    {
        $this->mock(ExceptionHandler::class)
            ->shouldReceive('report')
            ->once();

        $resultado = $this->invokeReuseLookup($this->importacaoServiceSemTabela());

        self::assertNull($resultado);
    }

    public function test_importacao_concluida_e_reaproveitada_com_contadores(): void
    {
        Session::put($this->service->previewActionsKey($importacaoId = 4242), ['30' => 'importar']);
        Session::put($this->service->previewChurchesKey($importacaoId), ['12-3456' => 'importar']);
        Session::put($this->service->previewDependenciesKey($importacaoId), ['12-3456:SALA' => 'importar']);
        Session::put('importacao_confirm_options_'.$importacaoId, ['importar_tudo' => true]);

        $resultado = $this->invokeReuseLookup(
            $this->importacaoServiceComImportacaoConcluida($importacaoId, sucesso: 12, erro: 3),
        );

        self::assertSame([
            'sucesso' => 12,
            'erro' => 3,
            'status' => 'concluida',
            'reutilizada' => true,
        ], $resultado);

        self::assertFalse(Session::has($this->service->previewActionsKey($importacaoId)));
        self::assertFalse(Session::has($this->service->previewChurchesKey($importacaoId)));
        self::assertFalse(Session::has($this->service->previewDependenciesKey($importacaoId)));
        self::assertFalse(Session::has('importacao_confirm_options_'.$importacaoId));
    }

    public function test_importacao_ainda_em_andamento_nao_e_reaproveitada(): void
    {
        $this->mock(ExceptionHandler::class)
            ->shouldNotReceive('report');

        $importacaoId = 4343;

        Session::put($this->service->previewActionsKey($importacaoId), ['30' => 'importar']);

        $resultado = $this->invokeReuseLookup(
            $this->importacaoServiceComStatus($importacaoId, 'processando'),
        );

        self::assertNull($resultado);
        self::assertTrue(Session::has($this->service->previewActionsKey($importacaoId)));
    }

    public function test_importacao_inexistente_nao_e_reaproveitada(): void
    {
        $this->mock(ExceptionHandler::class)
            ->shouldNotReceive('report');

        $resultado = $this->invokeReuseLookup($this->importacaoServiceVazia());

        self::assertNull($resultado);
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    /**
     * @return array<string, mixed>|null
     */
    private function invokeReuseLookup(ImportacaoService $importacaoService): ?array
    {
        $method = new ReflectionMethod($this->service, 'reuseCompletedImportation');
        $method->setAccessible(true);

        return $method->invoke($this->service, $importacaoService, 4242);
    }

    /**
     * ImportacaoService apontando para um SQLite sem a tabela `importacoes`:
     * qualquer consulta de progresso estoura PDOException.
     */
    private function importacaoServiceSemTabela(): ImportacaoService
    {
        return new ImportacaoService(new PDO('sqlite::memory:'));
    }

    private function importacaoServiceVazia(): ImportacaoService
    {
        return new ImportacaoService($this->criarConexaoSemRegistros());
    }

    private function importacaoServiceComImportacaoConcluida(
        int $importacaoId,
        int $sucesso,
        int $erro,
    ): ImportacaoService {
        $conexao = $this->criarConexaoSemRegistros();

        $conexao->exec(
            "INSERT INTO importacoes (id, usuario_id, comum_id, administracao_id, arquivo_nome, arquivo_caminho, total_linhas, linhas_sucesso, linhas_erro, status)
             VALUES ({$importacaoId}, 42, 7, 3, 'bens.csv', '/tmp/bens.csv', 20, {$sucesso}, {$erro}, 'concluida')",
        );

        return new ImportacaoService($conexao);
    }

    private function importacaoServiceComStatus(int $importacaoId, string $status): ImportacaoService
    {
        $conexao = $this->criarConexaoSemRegistros();

        $conexao->exec(
            "INSERT INTO importacoes (id, usuario_id, comum_id, administracao_id, arquivo_nome, arquivo_caminho, total_linhas, status)
             VALUES ({$importacaoId}, 42, 7, 3, 'bens.csv', '/tmp/bens.csv', 20, '{$status}')",
        );

        return new ImportacaoService($conexao);
    }

    /**
     * SQLite em memória com o esquema mínimo exigido por
     * ImportacaoRepository::buscarPorId() (JOINs em usuarios e administracoes).
     */
    private function criarConexaoSemRegistros(): PDO
    {
        $conexao = new PDO('sqlite::memory:');
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $conexao->exec('CREATE TABLE importacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            comum_id INTEGER DEFAULT NULL,
            administracao_id INTEGER DEFAULT NULL,
            arquivo_nome VARCHAR(255) NOT NULL,
            arquivo_caminho VARCHAR(500) NOT NULL,
            total_linhas INTEGER DEFAULT 0,
            linhas_processadas INTEGER DEFAULT 0,
            linhas_sucesso INTEGER DEFAULT 0,
            linhas_erro INTEGER DEFAULT 0,
            porcentagem DECIMAL(5,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT \'aguardando\',
            mensagem_erro TEXT DEFAULT NULL,
            iniciada_em TIMESTAMP DEFAULT NULL,
            concluida_em TIMESTAMP DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        $conexao->exec('CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            ativo TINYINT DEFAULT 1
        )');

        $conexao->exec('CREATE TABLE administracoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao VARCHAR(255) NOT NULL
        )');

        $conexao->exec("INSERT INTO usuarios (id, nome, email) VALUES (42, 'Maria Silva', 'maria@exemplo.com')");
        $conexao->exec("INSERT INTO administracoes (id, descricao) VALUES (3, 'Administração Central')");

        return $conexao;
    }
}

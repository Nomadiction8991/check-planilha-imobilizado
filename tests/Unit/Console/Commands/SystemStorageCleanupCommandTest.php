<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression tests for `system:storage-cleanup`.
 *
 * Bug (P2): the generic temporary-file cleanup deletes every old file under
 * `storage/tmp`, including `analise_{id}.json` files still required by imports
 * whose status is `aguardando`. Once the JSON is gone the import can no longer
 * be processed by `checkplanilha:processar-importacoes` — the operator has to
 * re-upload the spreadsheet, i.e. silent data loss.
 *
 * The dedicated `analyses:cleanup-orphans` command already preserves active
 * imports; the generic cleanup must not undo that work 30 minutes later.
 */
final class SystemStorageCleanupCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // pgsql is remapped to an in-memory SQLite database, mirroring the
        // production schema the commands read from (see AnalysesCleanupOrphansTest).
        Config::set('database.connections.pgsql', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        DB::connection('pgsql')->statement('
            CREATE TABLE importacoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL DEFAULT 1,
                comum_id INTEGER DEFAULT NULL,
                administracao_id INTEGER DEFAULT NULL,
                arquivo_nome VARCHAR(255) NOT NULL DEFAULT \'\',
                arquivo_caminho VARCHAR(500) NOT NULL DEFAULT \'\',
                total_linhas INTEGER NOT NULL DEFAULT 0,
                linhas_processadas INTEGER NOT NULL DEFAULT 0,
                linhas_sucesso INTEGER NOT NULL DEFAULT 0,
                linhas_erro INTEGER NOT NULL DEFAULT 0,
                porcentagem DECIMAL(5,2) NOT NULL DEFAULT 0,
                status VARCHAR(50) NOT NULL DEFAULT \'aguardando\',
                mensagem_erro TEXT DEFAULT NULL,
                iniciada_em TIMESTAMP DEFAULT NULL,
                concluida_em TIMESTAMP DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Same table on the default (sqlite) connection so the assertions hold
        // regardless of which connection the cleanup command consults.
        DB::connection()->statement('
            CREATE TABLE importacoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL DEFAULT 1,
                comum_id INTEGER DEFAULT NULL,
                administracao_id INTEGER DEFAULT NULL,
                arquivo_nome VARCHAR(255) NOT NULL DEFAULT \'\',
                arquivo_caminho VARCHAR(500) NOT NULL DEFAULT \'\',
                total_linhas INTEGER NOT NULL DEFAULT 0,
                linhas_processadas INTEGER NOT NULL DEFAULT 0,
                linhas_sucesso INTEGER NOT NULL DEFAULT 0,
                linhas_erro INTEGER NOT NULL DEFAULT 0,
                porcentagem DECIMAL(5,2) NOT NULL DEFAULT 0,
                status VARCHAR(50) NOT NULL DEFAULT \'aguardando\',
                mensagem_erro TEXT DEFAULT NULL,
                iniciada_em TIMESTAMP DEFAULT NULL,
                concluida_em TIMESTAMP DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->tempDir = sys_get_temp_dir() . '/checkplanilha_cleanup_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);

        // Redirect the whole storage tree into the temp dir so the real
        // project storage/ is never read or written.
        $this->app->useStoragePath($this->tempDir);
        mkdir($this->tempDir . '/tmp', 0777, true);
        mkdir($this->tempDir . '/app/tmp', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmdirRecursive($this->tempDir);

        parent::tearDown();
    }

    private function rmdirRecursive(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->rmdirRecursive($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    private function criarImportacao(int $id, string $status): void
    {
        $sql = 'INSERT INTO importacoes (id, usuario_id, arquivo_nome, arquivo_caminho, status, iniciada_em)
                VALUES (?, 1, ?, ?, ?, datetime(\'now\'))';

        $params = [$id, 'planilha_teste.csv', '/tmp/planilha_teste.csv', $status];

        DB::connection('pgsql')->insert($sql, $params);
        DB::connection()->insert($sql, $params);
    }

    /**
     * Writes an analysis file older than the retention window, exactly as the
     * AnalysisPersistenceService would have left it.
     */
    private function criarArquivoAnaliseAntigo(int $importacaoId, int $idadeDias): string
    {
        $path = $this->tempDir . '/tmp/analise_' . $importacaoId . '.json';

        file_put_contents($path, json_encode([
            'importacao_id' => $importacaoId,
            'registros'     => [],
        ], JSON_UNESCAPED_UNICODE));

        touch($path, time() - ($idadeDias * 86400));

        return $path;
    }

    /** @test */
    public function test_analysis_file_of_pending_import_survives_storage_cleanup(): void
    {
        $this->criarImportacao(4242, 'aguardando');
        $analisePath = $this->criarArquivoAnaliseAntigo(4242, 10);

        $this->assertFileExists($analisePath, 'Pré-condição: a análise existe antes do cleanup.');

        $this->artisan('system:storage-cleanup', ['--days' => 7])
            ->assertSuccessful();

        $this->assertFileExists(
            $analisePath,
            'A análise de uma importação ainda aguardando não pode ser removida pelo cleanup genérico.'
        );
    }

    /**
     * Control test: proves the cleanup really ran against the isolated temp
     * directory — without it the test above could pass simply because the
     * command never touched the directory at all.
     *
     * @test
     */
    public function test_cleanup_still_removes_old_non_analysis_files_in_temp_dir(): void
    {
        $oldFile = $this->tempDir . '/tmp/relatorio_antigo.tmp';
        file_put_contents($oldFile, 'conteudo qualquer');
        touch($oldFile, time() - (10 * 86400));

        $recentFile = $this->tempDir . '/tmp/relatorio_recente.tmp';
        file_put_contents($recentFile, 'conteudo qualquer');
        touch($recentFile, time() - 86400);

        $this->artisan('system:storage-cleanup', ['--days' => 7])
            ->assertSuccessful();

        $this->assertFileDoesNotExist($oldFile, 'Arquivo temporário antigo deve continuar sendo removido.');
        $this->assertFileExists($recentFile, 'Arquivo dentro da janela de retenção deve ser preservado.');
    }
}

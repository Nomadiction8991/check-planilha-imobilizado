<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AnalysesCleanupOrphans;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AnalysesCleanupOrphansTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Override pgsql connection to use SQLite in-memory for testing
        Config::set('database.connections.pgsql', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Create the importacoes table (matching production schema)
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

        // Create the import_erros table (for --force-delete tests)
        DB::connection('pgsql')->statement('
            CREATE TABLE import_erros (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                importacao_id INTEGER NOT NULL,
                linha INTEGER NOT NULL DEFAULT 0,
                coluna VARCHAR(255) DEFAULT NULL,
                mensagem TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (importacao_id) REFERENCES importacoes(id)
            )
        ');

        // Create temp directory for analysis files
        $this->tempDir = sys_get_temp_dir() . '/checkplanilha_aco_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            foreach (glob($this->tempDir . '/*') as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Inject the temp storage directory into the command via container binding.
     */
    private function bindCommandWithTempDir(): void
    {
        $command = $this->app->make(AnalysesCleanupOrphans::class);
        $command->setStorageDir($this->tempDir);
        $this->app->instance(AnalysesCleanupOrphans::class, $command);
    }

    /**
     * Create a fake analysis file in the temp directory.
     */
    private function criarArquivoAnalise(int $importacaoId): string
    {
        $path = $this->tempDir . '/analise_' . $importacaoId . '.json';
        file_put_contents($path, json_encode(['status' => 'test']));
        return $path;
    }

    /**
     * Insert an import record into the pgsql (SQLite in-memory) table.
     */
    private function criarImportacao(int $id, string $status = 'concluida'): void
    {
        DB::connection('pgsql')->insert(
            'INSERT INTO importacoes (id, usuario_id, arquivo_nome, arquivo_caminho, status, iniciada_em)
             VALUES (?, 1, \'test.csv\', \'/tmp/test.csv\', ?, datetime(\'now\'))',
            [$id, $status]
        );
    }

    /**
     * Insert an error record linked to an import.
     */
    private function criarErroImportacao(int $importacaoId, int $linha = 1): void
    {
        DB::connection('pgsql')->insert(
            'INSERT INTO import_erros (importacao_id, linha, coluna, mensagem)
             VALUES (?, ?, \'A\', \'Erro de teste\')',
            [$importacaoId, $linha]
        );
    }

    // ── Tests ──────────────────────────────────────────────────────────

    /** @test */
    public function test_warns_when_storage_dir_does_not_exist(): void
    {
        // Use a non-existent directory by injecting the command into the container
        $command = $this->app->make(AnalysesCleanupOrphans::class);
        $command->setStorageDir($this->tempDir . '/nonexistent');
        $this->app->instance(AnalysesCleanupOrphans::class, $command);

        $this->artisan('analyses:cleanup-orphans')
            ->expectsOutputToContain('Storage directory not found')
            ->assertSuccessful();
    }

    /** @test */
    public function test_no_files_found_when_dir_empty(): void
    {
        $this->bindCommandWithTempDir();

        $this->artisan('analyses:cleanup-orphans')
            ->expectsOutputToContain('No analysis files found')
            ->assertSuccessful();
    }

    /** @test */
    public function test_orphan_file_without_db_record_is_deleted(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarArquivoAnalise(99);

        $this->artisan('analyses:cleanup-orphans')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($this->tempDir . '/analise_99.json');
    }

    /** @test */
    public function test_orphan_file_with_completed_import_is_deleted(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarImportacao(10, 'concluida');
        $this->criarArquivoAnalise(10);

        $this->artisan('analyses:cleanup-orphans')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($this->tempDir . '/analise_10.json');
    }

    /** @test */
    public function test_orphan_file_with_errored_import_is_deleted(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarImportacao(15, 'erro');
        $this->criarArquivoAnalise(15);

        $this->artisan('analyses:cleanup-orphans')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($this->tempDir . '/analise_15.json');
    }

    /** @test */
    public function test_file_with_active_import_aguardando_is_skipped(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarImportacao(5, 'aguardando');
        $this->criarArquivoAnalise(5);

        $this->artisan('analyses:cleanup-orphans')
            ->assertSuccessful();

        $this->assertFileExists($this->tempDir . '/analise_5.json');
    }

    /** @test */
    public function test_file_with_active_import_processando_is_skipped(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarImportacao(7, 'processando');
        $this->criarArquivoAnalise(7);

        $this->artisan('analyses:cleanup-orphans')
            ->assertSuccessful();

        $this->assertFileExists($this->tempDir . '/analise_7.json');
    }

    /** @test */
    public function test_dry_run_does_not_delete_files(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarImportacao(20, 'concluida');
        $this->criarArquivoAnalise(20);

        $this->artisan('analyses:cleanup-orphans', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN')
            ->assertSuccessful();

        $this->assertFileExists($this->tempDir . '/analise_20.json');
    }

    /** @test */
    public function test_dry_run_with_force_delete_shows_hint(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarArquivoAnalise(30);

        $this->artisan('analyses:cleanup-orphans', [
            '--dry-run' => true,
            '--force-delete' => true,
        ])
            ->expectsOutputToContain('DRY-RUN')
            ->expectsOutputToContain('--force-delete, database records')
            ->assertSuccessful();

        $this->assertFileExists($this->tempDir . '/analise_30.json');
    }

    /** @test */
    public function test_force_delete_removes_db_records(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarImportacao(40, 'concluida');
        $this->criarArquivoAnalise(40);
        $this->criarErroImportacao(40, 1);
        $this->criarErroImportacao(40, 2);

        // Confirm records exist before
        $this->assertSame(1, DB::connection('pgsql')->table('importacoes')->where('id', 40)->count());
        $this->assertSame(2, DB::connection('pgsql')->table('import_erros')->where('importacao_id', 40)->count());

        $this->artisan('analyses:cleanup-orphans', ['--force-delete' => true])
            ->expectsOutputToContain('DB record(s) deleted')
            ->assertSuccessful();

        // File should be gone
        $this->assertFileDoesNotExist($this->tempDir . '/analise_40.json');

        // DB records should be purged
        $this->assertSame(0, DB::connection('pgsql')->table('importacoes')->where('id', 40)->count());
        $this->assertSame(0, DB::connection('pgsql')->table('import_erros')->where('importacao_id', 40)->count());
    }

    /** @test */
    public function test_force_delete_skipped_when_import_not_found(): void
    {
        $this->bindCommandWithTempDir();
        // File without DB record — can_db_delete is false
        $this->criarArquivoAnalise(50);

        $this->artisan('analyses:cleanup-orphans', ['--force-delete' => true])
            ->expectsOutputToContain('Cleanup complete')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($this->tempDir . '/analise_50.json');
    }

    /** @test */
    public function test_without_force_delete_keeps_db_records(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarImportacao(60, 'concluida');
        $this->criarArquivoAnalise(60);
        $this->criarErroImportacao(60);

        $this->artisan('analyses:cleanup-orphans')
            ->expectsOutputToContain('Database records were kept')
            ->assertSuccessful();

        // File deleted
        $this->assertFileDoesNotExist($this->tempDir . '/analise_60.json');

        // DB records kept
        $this->assertSame(1, DB::connection('pgsql')->table('importacoes')->where('id', 60)->count());
        $this->assertSame(1, DB::connection('pgsql')->table('import_erros')->where('importacao_id', 60)->count());
    }

    /** @test */
    public function test_unexpected_filename_patterns_are_ignored(): void
    {
        $this->bindCommandWithTempDir();

        // Files that do not match analise_{id}.json
        file_put_contents($this->tempDir . '/analise_abc.json', '{}');
        file_put_contents($this->tempDir . '/other_file.json', '{}');
        file_put_contents($this->tempDir . '/analise_.json', '{}');
        file_put_contents($this->tempDir . '/analise_12a34.json', '{}');

        $this->artisan('analyses:cleanup-orphans')
            ->assertSuccessful();

        // None should be removed (they don't match the expected pattern)
        $this->assertFileExists($this->tempDir . '/analise_abc.json');
        $this->assertFileExists($this->tempDir . '/other_file.json');
        $this->assertFileExists($this->tempDir . '/analise_.json');
        $this->assertFileExists($this->tempDir . '/analise_12a34.json');
    }

    /** @test */
    public function test_mixed_scenario(): void
    {
        $this->bindCommandWithTempDir();

        // Orphan with completed import
        $this->criarImportacao(70, 'concluida');
        $this->criarArquivoAnalise(70);

        // Active import (aguardando)
        $this->criarImportacao(71, 'aguardando');
        $this->criarArquivoAnalise(71);

        // Active import (processando)
        $this->criarImportacao(72, 'processando');
        $this->criarArquivoAnalise(72);

        // Orphan without DB record
        $this->criarArquivoAnalise(73);

        // Non-matching file kept
        file_put_contents($this->tempDir . '/random.log', 'log data');

        $this->artisan('analyses:cleanup-orphans')
            ->assertSuccessful();

        // Orphans should be deleted
        $this->assertFileDoesNotExist($this->tempDir . '/analise_70.json', 'Orphan with completed import should be removed');
        $this->assertFileDoesNotExist($this->tempDir . '/analise_73.json', 'Orphan without DB record should be removed');

        // Active imports should keep their files
        $this->assertFileExists($this->tempDir . '/analise_71.json', 'Active import (aguardando) should be kept');
        $this->assertFileExists($this->tempDir . '/analise_72.json', 'Active import (processando) should be kept');

        // Non-matching file should remain untouched
        $this->assertFileExists($this->tempDir . '/random.log');
    }

    /** @test */
    public function test_db_query_exception_is_handled_gracefully(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarArquivoAnalise(80);

        // Break the pgsql connection so the DB query throws
        DB::connection('pgsql')->statement('DROP TABLE importacoes');
        DB::connection('pgsql')->statement('DROP TABLE import_erros');

        $this->artisan('analyses:cleanup-orphans')
            ->expectsOutputToContain('DB error')
            ->assertSuccessful();

        // File should remain since DB query failed and it was skipped
        // (the file is skipped because the DB error prevents classification,
        //  but in this case analise_80.json still exists in the dir)
        $this->assertFileExists($this->tempDir . '/analise_80.json');
    }

    /** @test */
    public function test_db_exception_during_force_delete_is_handled_gracefully(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarImportacao(90, 'concluida');
        $this->criarArquivoAnalise(90);

        // Create the file removal scenario: file is deleted but DB fails
        // Simulate by dropping the import_erros table + dropping importacoes after
        // The first file unlink should succeed, then DB transaction fails
        // We need to make the DB transaction throw
        // By dropping the importacoes table, the delete will fail
        DB::connection('pgsql')->statement('DROP TABLE import_erros');

        $this->artisan('analyses:cleanup-orphans', ['--force-delete' => true])
            ->expectsOutputToContain('Failed to delete DB records')
            ->assertExitCode(1);

        // File should be removed even though DB failed
        $this->assertFileDoesNotExist($this->tempDir . '/analise_90.json');
    }

    /** @test */
    public function test_output_shows_orphan_summary_table(): void
    {
        $this->bindCommandWithTempDir();
        $this->criarImportacao(100, 'concluida');
        $this->criarArquivoAnalise(100);

        $this->artisan('analyses:cleanup-orphans')
            ->expectsOutputToContain('orphan analysis file(s)')
            ->expectsTable(['File', 'Import #', 'Import Status'], [
                ['analise_100.json', '100', 'concluida'],
            ])
            ->assertSuccessful();
    }
}

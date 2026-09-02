<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * analyses:cleanup-orphans — Remove arquivos de análise órfãos.
 *
 * Arquivos de análise (`storage/tmp/analise_{id}.json`) cuja importação
 * associada já foi concluída/erro (ou não existe mais) são considerados
 * órfãos e podem ser removidos para liberar espaço.
 *
 * Importações ativas (aguardando/processando) preservam seus arquivos.
 *
 * Uso:
 *   php artisan analyses:cleanup-orphans
 *   php artisan analyses:cleanup-orphans --dry-run
 *   php artisan analyses:cleanup-orphans --force-delete
 */
class AnalysesCleanupOrphans extends Command
{
    protected $signature = 'analyses:cleanup-orphans
                           {--dry-run : Apenas lista o que seria removido, sem apagar}
                           {--force-delete : Remove também os registros de banco das importações órfãs}';

    protected $description = 'Remove arquivos de análise órfãos (importação concluída, com erro ou inexistente)';

    private string $storageDir;

    /**
     * Diretório dos arquivos de análise. Padrão: storage/tmp (mesmo do
     * AnalysisPersistenceService). Injetável para testes.
     */
    public function setStorageDir(string $storageDir): void
    {
        $this->storageDir = $storageDir;
    }

    public function handle(): int
    {
        $this->storageDir = $this->storageDir ?? storage_path('tmp');

        $dryRun = (bool) $this->option('dry-run');
        $forceDelete = (bool) $this->option('force-delete');

        if (! is_dir($this->storageDir)) {
            $this->warn('Storage directory not found: ' . $this->storageDir);

            return 0;
        }

        $files = glob($this->storageDir . '/analise_*.json') ?: [];

        if ($files === []) {
            $this->line('No analysis files found in ' . $this->storageDir);

            return 0;
        }

        if ($dryRun) {
            $this->info('DRY-RUN — nenhum arquivo será removido.');
            if ($forceDelete) {
                $this->line('Com --force-delete, database records também seriam removidos.');
            }
        }

        $orphans = [];

        foreach ($files as $file) {
            $filename = basename($file);

            if (! preg_match('/^analise_(\d+)\.json$/', $filename, $m)) {
                // Padrão inesperado: ignora sem tocar no arquivo.
                continue;
            }

            $importId = (int) $m[1];

            try {
                $import = DB::connection('pgsql')
                    ->table('importacoes')
                    ->where('id', $importId)
                    ->first();
            } catch (Throwable $e) {
                $this->error('DB error ao consultar importação ' . $importId . ': ' . $e->getMessage());
                continue;
            }

            if ($import === null) {
                $orphans[] = ['file' => $filename, 'id' => $importId, 'status' => 'sem registro'];
                continue;
            }

            $status = (string) ($import->status ?? '');

            if (in_array($status, ['aguardando', 'processando'], true)) {
                // Importação ativa — preserva o arquivo.
                continue;
            }

            $orphans[] = ['file' => $filename, 'id' => $importId, 'status' => $status];
        }

        if ($dryRun) {
            $this->renderOrphanTable($orphans);
            $this->line(count($orphans) . ' orphan analysis file(s) encontrado(s).');

            return 0;
        }

        $deletedFiles = 0;
        $deletedDb = 0;

        foreach ($orphans as $orphan) {
            $filePath = $this->storageDir . '/' . $orphan['file'];

            if (is_file($filePath)) {
                @unlink($filePath);
                $deletedFiles++;
            }

            if ($forceDelete && $orphan['status'] !== 'sem registro') {
                try {
                    DB::connection('pgsql')->transaction(function () use ($orphan): void {
                        DB::connection('pgsql')->table('import_erros')
                            ->where('importacao_id', $orphan['id'])
                            ->delete();

                        DB::connection('pgsql')->table('importacoes')
                            ->where('id', $orphan['id'])
                            ->delete();
                    });

                    $deletedDb++;
                } catch (Throwable $e) {
                    $this->error('Failed to delete DB records para importação ' . $orphan['id'] . ': ' . $e->getMessage());

                    return 1;
                }
            }
        }

        if ($forceDelete && $deletedDb > 0) {
            $this->line($deletedDb . ' DB record(s) deleted.');
        }

        if ($forceDelete) {
            $this->line('Database records were kept.' === '' ? '' : '');
        } elseif (! $forceDelete && $deletedFiles > 0) {
            $this->line('Database records were kept.');
        }

        $this->renderOrphanTable($orphans);

        $this->info($deletedFiles . ' orphan analysis file(s) removido(s).');
        $this->line('Cleanup complete.');

        return 0;
    }

    /**
     * Renderiza a tabela-resumo dos órfãos (se houver).
     *
     * @param  array<int, array{file: string, id: int, status: string}>  $orphans
     */
    private function renderOrphanTable(array $orphans): void
    {
        if ($orphans === []) {
            return;
        }

        $rows = array_map(
            fn (array $o): array => [$o['file'], (string) $o['id'], $o['status']],
            $orphans,
        );

        $this->table(['File', 'Import #', 'Import Status'], $rows);
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SystemStorageCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:storage-cleanup
                           {--days=7 : Dias de retenção para arquivos temporários}
                           {--dry-run : Apenas simula sem deletar nenhum arquivo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa arquivos temporários antigos e logs acumulados';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoffTimestamp = time() - ($days * 86400);

        $directories = [
            storage_path('tmp'),
            storage_path('app/tmp'),
        ];

        $totalDeleted = 0;
        $bytesFreed = 0;

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $items = @scandir($dir);
            if ($items === false) {
                continue;
            }

            foreach ($items as $item) {
                if ($item === '.' || $item === '..' || $item === '.gitignore') {
                    continue;
                }

                $filePath = $dir . DIRECTORY_SEPARATOR . $item;
                if (!is_file($filePath)) {
                    continue;
                }

                $mtime = @filemtime($filePath);
                if ($mtime !== false && $mtime < $cutoffTimestamp) {
                    $size = (int) @filesize($filePath);
                    if (!$dryRun) {
                        @unlink($filePath);
                    }
                    $totalDeleted++;
                    $bytesFreed += $size;
                }
            }
        }

        $mbFreed = round($bytesFreed / 1024 / 1024, 2);

        if ($dryRun) {
            $this->info(sprintf('[Dry-run] %d arquivos antigos identificados (%.2f MB a liberar).', $totalDeleted, $mbFreed));
        } else {
            $this->info(sprintf('Limpeza concluída: %d arquivos removidos (%.2f MB liberados).', $totalDeleted, $mbFreed));
        }

        return Command::SUCCESS;
    }
}

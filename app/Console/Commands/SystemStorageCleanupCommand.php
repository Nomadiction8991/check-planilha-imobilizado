<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemStorageCleanupCommand extends Command
{
    /**
     * Status de importação considerados terminais: a análise já não será
     * mais lida e o arquivo pode ser removido. Qualquer outro estado
     * (aguardando, processando, desconhecido) preserva o arquivo — espelho
     * da regra de AnalysesCleanupOrphans.
     */
    private const array REMOVABLE_STATUSES = ['concluida', 'erro'];

    private const string ANALYSIS_FILENAME_PATTERN = '/^analise_(\d+)\.json$/';

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
        $skippedActive = 0;

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
                if ($mtime === false || $mtime >= $cutoffTimestamp) {
                    continue;
                }

                if ($this->belongsToActiveImport($item)) {
                    $skippedActive++;
                    continue;
                }

                $size = (int) @filesize($filePath);
                if (!$dryRun) {
                    @unlink($filePath);
                }
                $totalDeleted++;
                $bytesFreed += $size;
            }
        }

        $mbFreed = round($bytesFreed / 1024 / 1024, 2);

        if ($dryRun) {
            $this->info(sprintf('[Dry-run] %d arquivos antigos identificados (%.2f MB a liberar).', $totalDeleted, $mbFreed));
        } else {
            $this->info(sprintf('Limpeza concluída: %d arquivos removidos (%.2f MB liberados).', $totalDeleted, $mbFreed));
        }

        if ($skippedActive > 0) {
            $this->line(sprintf('%d arquivo(s) de análise preservado(s) por importação ainda ativa.', $skippedActive));
        }

        return Command::SUCCESS;
    }

    /**
     * Um arquivo `analise_{id}.json` é preservado enquanto a importação
     * correspondente não estiver em estado terminal — o processamento
     * (`checkplanilha:processar-importacoes` e o fluxo web) ainda depende
     * dele. Mesma regra de `analyses:cleanup-orphans`.
     *
     * Falha de consulta (banco fora do ar, tabela ausente) preserva o
     * arquivo: perder uma análise é pior do que deixar um arquivo a mais.
     */
    private function belongsToActiveImport(string $filename): bool
    {
        if (! preg_match(self::ANALYSIS_FILENAME_PATTERN, $filename, $matches)) {
            return false;
        }

        $importId = (int) $matches[1];

        try {
            $import = DB::connection('pgsql')
                ->table('importacoes')
                ->where('id', $importId)
                ->first();
        } catch (Throwable $e) {
            return true;
        }

        if ($import === null) {
            return false;
        }

        return ! in_array((string) ($import->status ?? ''), self::REMOVABLE_STATUSES, true);
    }
}

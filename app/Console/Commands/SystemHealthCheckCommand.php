<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:health-check
                           {--json : Retorna o diagnóstico em formato JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica a integridade do sistema (banco de dados, storage e recursos de disco)';

    public function handle(): int
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage_permissions' => $this->checkStoragePermissions(),
            'disk_space' => $this->checkDiskSpace(),
        ];

        $hasFailures = false;
        foreach ($checks as $check) {
            if ($check['status'] === 'error') {
                $hasFailures = true;
                break;
            }
        }

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'status' => $hasFailures ? 'error' : 'ok',
                'timestamp' => now()->toIso8601String(),
                'checks' => $checks,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $hasFailures ? Command::FAILURE : Command::SUCCESS;
        }

        $this->info('=== Diagnóstico de Integridade do Sistema ===');
        $this->newLine();

        $rows = [];
        foreach ($checks as $name => $info) {
            $statusDisplay = $info['status'] === 'ok' ? '<info>OK</info>' : '<error>FALHA</error>';
            $rows[] = [ucfirst(str_replace('_', ' ', $name)), $statusDisplay, $info['message']];
        }

        $this->table(['Componente', 'Status', 'Detalhes'], $rows);

        if ($hasFailures) {
            $this->error('Atenção: Um ou mais componentes apresentaram falha.');
            return Command::FAILURE;
        }

        $this->info('Todos os componentes operacionais.');
        return Command::SUCCESS;
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'ok',
                'message' => 'Conexão ativa com o banco de dados principal.',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Erro ao conectar no banco: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkStoragePermissions(): array
    {
        $directories = [
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        $unwritable = [];
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (!is_writable($dir)) {
                $unwritable[] = basename($dir);
            }
        }

        if (!empty($unwritable)) {
            return [
                'status' => 'error',
                'message' => 'Diretórios sem permissão de escrita: ' . implode(', ', $unwritable),
            ];
        }

        return [
            'status' => 'ok',
            'message' => 'Todos os diretórios de storage e cache com permissão de escrita.',
        ];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkDiskSpace(): array
    {
        $path = storage_path();
        $freeBytes = @disk_free_space($path);
        $totalBytes = @disk_total_space($path);

        if ($freeBytes === false || $totalBytes === false) {
            return [
                'status' => 'ok',
                'message' => 'Métricas de disco não disponíveis no ambiente.',
            ];
        }

        $freeMb = (int) round($freeBytes / 1024 / 1024);
        $totalMb = (int) round($totalBytes / 1024 / 1024);
        $percentFree = $totalMb > 0 ? (int) round(($freeMb / $totalMb) * 100) : 0;

        if ($freeMb < 100) {
            return [
                'status' => 'error',
                'message' => sprintf('Espaço em disco crítico: %d MB livres (%d%%).', $freeMb, $percentFree),
            ];
        }

        return [
            'status' => 'ok',
            'message' => sprintf('%d MB livres de %d MB (%d%% disponível).', $freeMb, $totalMb, $percentFree),
        ];
    }
}

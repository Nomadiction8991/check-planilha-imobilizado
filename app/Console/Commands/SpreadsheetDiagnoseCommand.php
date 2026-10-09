<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SpreadsheetDiagnosticService;
use Illuminate\Console\Command;

class SpreadsheetDiagnoseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spreadsheet:diagnose
                           {path : Caminho absoluto ou relativo do arquivo de planilha}
                           {--json : Retorna o diagnóstico estruturado em formato JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realiza diagnóstico prévio de arquivos de planilhas (CSV, XLS, XLSX) verificando layout, cabeçalhos e registros de bens';

    public function handle(SpreadsheetDiagnosticService $service): int
    {
        $path = (string) $this->argument('path');
        $resolvedPath = file_exists($path) ? realpath($path) : $path;
        if ($resolvedPath === false) {
            $resolvedPath = $path;
        }

        $report = $service->diagnose($resolvedPath);

        if ($this->option('json')) {
            $this->line((string) json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return $report->isImportReady ? Command::SUCCESS : Command::FAILURE;
        }

        $this->info('=== Relatório de Diagnóstico de Planilha ===');
        $this->newLine();

        $rows = [
            ['Arquivo', $report->filePath],
            ['Formato', $report->format],
            ['Tamanho', $report->toArray()['file_size_formatted']],
            ['Total de Linhas', (string) $report->totalRows],
            ['Linha do Cabeçalho', $report->headerRowIndex !== null ? 'Linha ' . ($report->headerRowIndex + 1) : 'Não detectado'],
            ['Colunas Mapeadas', (string) json_encode($report->detectedColumns, JSON_UNESCAPED_UNICODE)],
            ['Bens Válidos Identificados', (string) $report->validProductCount],
            ['Linhas Incompletas/Ignoradas', (string) $report->invalidRowCount],
            ['Igrejas/Comuns Detectadas', !empty($report->churchCodes) ? implode(', ', $report->churchCodes) : 'Nenhuma'],
            ['Pronto para Importação', $report->isImportReady ? '<info>SIM (Pronto)</info>' : '<error>NÃO (Ajustes necessários)</error>'],
        ];

        $this->table(['Item', 'Diagnóstico'], $rows);

        if (!empty($report->warnings)) {
            $this->newLine();
            $this->warn('Alertas:');
            foreach ($report->warnings as $w) {
                $this->line(" - {$w}");
            }
        }

        if (!empty($report->errors)) {
            $this->newLine();
            $this->error('Erros críticos:');
            foreach ($report->errors as $e) {
                $this->line(" - {$e}");
            }
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Planilha validada com sucesso e compatível com o importador!');
        return Command::SUCCESS;
    }
}

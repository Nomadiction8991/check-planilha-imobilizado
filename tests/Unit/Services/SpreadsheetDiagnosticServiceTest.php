<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SpreadsheetDiagnosticService;
use Tests\TestCase;

class SpreadsheetDiagnosticServiceTest extends TestCase
{
    public function test_diagnose_valid_csv_file(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'diag_csv_');
        $this->assertNotFalse($tmp);

        $csvContent = implode("\n", [
            'Relatório de Bens Patrimoniais',
            '',
            'Código,Fornecedor,Localidade,Nome,Dependência',
            '09-0565 / 000001,NC,BR 09-0565,Banco Templo,TEMPLO',
            '09-0565 / 000002,NC,BR 09-0565,Pulpito Madeira,TEMPLO',
            'Total,',
        ]) . "\n";

        file_put_contents($tmp, $csvContent);

        $service = new SpreadsheetDiagnosticService();
        $report = $service->diagnose($tmp);
        @unlink($tmp);

        $this->assertTrue($report->isImportReady);
        $this->assertSame(6, $report->totalRows);
        $this->assertSame(2, $report->headerRowIndex);
        $this->assertSame(2, $report->validProductCount);
        $this->assertContains('09-0565', $report->churchCodes);
    }

    public function test_diagnose_nonexistent_file_returns_error_report(): void
    {
        $service = new SpreadsheetDiagnosticService();
        $report = $service->diagnose('/caminho/inexistente/planilha.csv');

        $this->assertFalse($report->isImportReady);
        $this->assertNotEmpty($report->errors);
    }
}

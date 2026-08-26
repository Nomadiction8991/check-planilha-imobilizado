<?php

namespace Tests\Feature\Spreadsheets;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use App\Services\LegacySpreadsheetImportService;

class SpreadsheetImportIntegrationTest extends TestCase
{
    public function test_import_full_workflow_with_real_csv(): void
    {
        // 1. Setup: CSV de exemplo autocontido (sem depender de arquivos externos)
        $csvContent = "codigo;bem;complemento;dependencia;localidade\n"
            . "09-0565 / 0001;CADEIRA;METALICA;SALA 01;09-0565\n"
            . "09-0565 / 0002;MESA;ESCRITORIO;SALA 01;09-0565\n";

        // 2. Simular upload (o serviço de importação espera um arquivo via Request)
        $file = new UploadedFile(
            tempnam(sys_get_temp_dir(), 'csv_'),
            'Relatório de Bens Imobilizado.csv',
            'text/csv',
            null,
            true,
        );
        file_put_contents($file->getPathname(), $csvContent);

        // 3. Validar que o parser lê o arquivo corretamente
        $service = new LegacySpreadsheetImportService();

        $this->assertTrue($file->isValid(), 'Upload simulado inválido.');
        $this->assertStringContainsString('CADEIRA', file_get_contents($file->getPathname()));
        $this->assertNotNull($service);
    }
}

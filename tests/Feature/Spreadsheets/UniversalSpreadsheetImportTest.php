<?php

declare(strict_types=1);

namespace Tests\Feature\Spreadsheets;

use App\Http\Requests\StoreSpreadsheetImportRequest;
use App\Support\UniversalSpreadsheetReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UniversalSpreadsheetImportTest extends TestCase
{
    public function test_store_request_accepts_csv_xls_and_xlsx_extensions(): void
    {
        $request = new StoreSpreadsheetImportRequest();
        $rules = $request->rules();

        $csvFile = UploadedFile::fake()->create('relatorio.csv', 100, 'text/csv');
        $xlsFile = UploadedFile::fake()->create('relatorio.xls', 100, 'application/vnd.ms-excel');
        $xlsxFile = UploadedFile::fake()->create('relatorio.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $pdfFile = UploadedFile::fake()->create('relatorio.pdf', 100, 'application/pdf');

        $vCsv = Validator::make(['administracao_id' => 1, 'arquivo_csv' => $csvFile], $rules);
        $vXls = Validator::make(['administracao_id' => 1, 'arquivo_csv' => $xlsFile], $rules);
        $vXlsx = Validator::make(['administracao_id' => 1, 'arquivo_csv' => $xlsxFile], $rules);
        $vPdf = Validator::make(['administracao_id' => 1, 'arquivo_csv' => $pdfFile], $rules);

        $this->assertTrue($vCsv->passes(), 'CSV deve ser aceito');
        $this->assertTrue($vXls->passes(), 'XLS deve ser aceito');
        $this->assertTrue($vXlsx->passes(), 'XLSX deve ser aceito');
        $this->assertFalse($vPdf->passes(), 'PDF deve ser rejeitado');
    }

    public function test_universal_reader_parses_csv_rows(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_csv_');
        file_put_contents($tmp, "Código,Nome,Dependência\n09-0565 / 000001,Banco,Templo\n");

        $rows = UniversalSpreadsheetReader::read($tmp);
        @unlink($tmp);

        $this->assertNotEmpty($rows);
        $this->assertSame('Código', $rows[0][0]);
        $this->assertSame('09-0565 / 000001', $rows[1][0]);
    }
}

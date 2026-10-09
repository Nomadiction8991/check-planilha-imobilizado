<?php

declare(strict_types=1);

namespace Tests\Feature\Spreadsheets;

use App\Http\Requests\StoreSpreadsheetImportRequest;
use App\Support\UniversalSpreadsheetReader;
use Tests\TestCase;

class UniversalSpreadsheetImportTest extends TestCase
{
    public function test_store_request_accepts_csv_xls_and_xlsx_extensions(): void
    {
        $request = new StoreSpreadsheetImportRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('arquivo_csv', $rules);
        $this->assertContains('extensions:csv,txt,xls,xlsx', $rules['arquivo_csv']);
    }

    public function test_universal_reader_parses_csv_rows(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_csv_');
        if ($tmp === false) {
            $this->fail('Falha ao criar arquivo temporário.');
        }
        file_put_contents($tmp, "Código,Nome,Dependência\n09-0565 / 000001,Banco,Templo\n");

        $rows = UniversalSpreadsheetReader::read($tmp);

        $this->assertCount(2, $rows);
        $this->assertSame(['Código', 'Nome', 'Dependência'], $rows[0]);
        $this->assertSame(['09-0565 / 000001', 'Banco', 'Templo'], $rows[1]);

        @unlink($tmp);
    }

    public function test_universal_reader_ensure_csv_preserves_csv(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_csv_');
        if ($tmp === false) {
            $this->fail('Falha ao criar arquivo temporário.');
        }
        file_put_contents($tmp, "Código,Nome,Dependência\n09-0565 / 000001,Banco,Templo\n");

        $csvPath = UniversalSpreadsheetReader::ensureCsv($tmp);
        $this->assertSame($tmp, $csvPath);

        @unlink($tmp);
    }

    public function test_universal_reader_ensure_csv_converts_xls(): void
    {
        $header = str_repeat("\x00", 512);
        $header = substr_replace($header, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", 0, 8);
        $header = substr_replace($header, pack('v', 9), 30, 2);
        $header = substr_replace($header, pack('v', 6), 32, 2);
        $header = substr_replace($header, pack('V', 1), 44, 4);
        $header = substr_replace($header, pack('V', 1), 48, 4);
        $header = substr_replace($header, pack('V', 0), 76, 4);
        for ($i = 1; $i < 109; $i++) {
            $header = substr_replace($header, pack('V', 0xFFFFFFFF), 76 + $i * 4, 4);
        }

        $fat = str_repeat("\xFF", 512);
        $fat = substr_replace($fat, pack('V', 0xFFFFFFFD), 0, 4);
        $fat = substr_replace($fat, pack('V', 0xFFFFFFFE), 4, 4);
        $fat = substr_replace($fat, pack('V', 0xFFFFFFFE), 8, 4);

        $dir = str_repeat("\x00", 512);
        $rootName = (string) mb_convert_encoding("Root Entry\0", 'UTF-16LE');
        $dir = substr_replace($dir, $rootName, 0, strlen($rootName));
        $dir = substr_replace($dir, pack('v', strlen($rootName)), 64, 2);
        $dir[66] = "\x05";
        $dir = substr_replace($dir, pack('V', 0xFFFFFFFE), 116, 4);

        $wbName = (string) mb_convert_encoding("Workbook\0", 'UTF-16LE');
        $dir = substr_replace($dir, $wbName, 128, strlen($wbName));
        $dir = substr_replace($dir, pack('v', strlen($wbName)), 128 + 64, 2);
        $dir[128 + 66] = "\x02";
        $dir = substr_replace($dir, pack('V', 2), 128 + 116, 4);

        $wb = pack('vv', 0x0809, 16) . pack('vvvv', 0x0600, 0x0005, 0x0dbb, 0x07cd) . str_repeat("\x00", 8);
        $txt = 'Teste';
        $rec = pack('vvv', 0, 0, 0) . pack('v', strlen($txt)) . "\x00" . $txt;
        $wb .= pack('vv', 0x0204, strlen($rec)) . $rec;
        $wb .= pack('vv', 0x000A, 0);

        $wbSize = strlen($wb);
        $dir = substr_replace($dir, pack('V', $wbSize), 128 + 120, 4);
        $wb .= str_repeat("\x00", 512 - ($wbSize % 512));

        $tmpXls = tempnam(sys_get_temp_dir(), 'test_xls_') . '.xls';
        file_put_contents($tmpXls, $header . $fat . $dir . $wb);

        $convertedCsv = UniversalSpreadsheetReader::ensureCsv($tmpXls);

        $this->assertNotSame($tmpXls, $convertedCsv);
        $this->assertFileExists($convertedCsv);
        $this->assertStringEndsWith('.csv', $convertedCsv);

        $rows = UniversalSpreadsheetReader::read($convertedCsv);
        $this->assertNotEmpty($rows);
        $this->assertSame('Teste', $rows[0][0]);

        @unlink($tmpXls);
        @unlink($convertedCsv);
    }
}

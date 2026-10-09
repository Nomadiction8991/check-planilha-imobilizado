<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SpreadsheetDiagnoseCommandTest extends TestCase
{
    public function test_command_diagnoses_csv_file_successfully(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cmd_diag_');
        $this->assertNotFalse($tmp);

        $csvContent = "Código,Nome,Localidade,Dependência\n09-0565 / 0001,Cadeira,BR 09-0565,Salão\n";
        file_put_contents($tmp, $csvContent);

        $exitCode = Artisan::call('spreadsheet:diagnose', ['path' => $tmp]);
        @unlink($tmp);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Relatório de Diagnóstico de Planilha', $output);
        $this->assertStringContainsString('09-0565', $output);
    }

    public function test_command_json_output(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cmd_diag_json_');
        $this->assertNotFalse($tmp);

        $csvContent = "Código,Nome,Localidade,Dependência\n09-0565 / 0001,Mesa,BR 09-0565,Salão\n";
        file_put_contents($tmp, $csvContent);

        $exitCode = Artisan::call('spreadsheet:diagnose', ['path' => $tmp, '--json' => true]);
        @unlink($tmp);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('is_import_ready', $data);
        $this->assertTrue($data['is_import_ready']);
        $this->assertSame(1, $data['valid_products_count']);
    }
}

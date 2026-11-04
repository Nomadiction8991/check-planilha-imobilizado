<?php

declare(strict_types=1);

namespace Tests\Feature\Spreadsheets;

use App\Services\CsvParserService;
use App\Services\LegacySpreadsheetImportService;
use App\Support\UniversalSpreadsheetReader;
use Illuminate\Http\UploadedFile;
use PDO;
use ReflectionMethod;
use Tests\TestCase;

final class RealSpreadsheetImportTest extends TestCase
{
    public function test_planilha_real_convertida_e_analisada_no_diretorio_permitido(): void
    {
        $origem = tempnam(sys_get_temp_dir(), 'planilha_');
        self::assertNotFalse($origem);
        self::assertTrue(copy(base_path('tests/Fixtures/importacao/relatorio-ficticio.xls'), $origem));
        $csv = null;
        $destino = null;

        try {
            $csv = UniversalSpreadsheetReader::ensureCsv($origem);
            self::assertNotSame($origem, $csv);
            $arquivo = new UploadedFile($csv, 'planilha.csv', 'text/csv', null, true);
            $mover = new ReflectionMethod(LegacySpreadsheetImportService::class, 'moveUploadedFile');
            $destino = $mover->invoke(new LegacySpreadsheetImportService(), $arquivo, 0);

            $banco = new PDO('sqlite::memory:');
            $banco->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $banco->exec('CREATE TABLE configuracoes (pulo_linhas INTEGER, mapeamento_colunas TEXT)');
            $banco->exec('CREATE TABLE comums (id INTEGER, codigo TEXT)');
            $banco->exec('CREATE TABLE tipos_bens (id INTEGER, codigo TEXT, descricao TEXT)');
            $analise = (new CsvParserService($banco))->analisar($destino);

            self::assertSame(2, $analise['resumo']['total']);
            self::assertCount(2, $analise['registros']);
            self::assertSame(0, $analise['resumo']['erros']);
            $linhas = UniversalSpreadsheetReader::read($origem);
            $primeiro = $analise['registros'][0];
            $linhaOriginal = $linhas[$primeiro['linha_csv'] - 1];
            self::assertSame(trim($linhaOriginal[0]), $primeiro['dados_csv']['codigo']);
            self::assertSame('99-9999 / 0001', $primeiro['dados_csv']['codigo']);
        } finally {
            if (is_string($destino) && is_file($destino)) {
                unlink($destino);
            }
            if (is_string($csv) && is_file($csv)) {
                unlink($csv);
            }
            if (is_file($origem)) {
                unlink($origem);
            }
        }
    }
}

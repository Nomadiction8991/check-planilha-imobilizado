<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Support\UniversalSpreadsheetReader;
use Tests\TestCase;

final class UniversalSpreadsheetReaderTest extends TestCase
{
    public function test_le_xls_biff_com_celulas_ficticias(): void
    {
        $linhas = UniversalSpreadsheetReader::read(base_path('tests/Fixtures/importacao/relatorio-ficticio.xls'));

        self::assertCount(28, $linhas);
        self::assertSame('99-9999 / 0001', $linhas[26][0]);
        self::assertSame('4 - CADEIRA AZUL', $linhas[26][3]);
        self::assertSame('SALA DE TESTE', $linhas[27][15]);
    }

    public function test_le_csv_com_dados_ficticios_e_preserva_celulas(): void
    {
        $caminho = base_path('tests/Fixtures/importacao/exemplo-leitor.csv');

        self::assertSame($caminho, UniversalSpreadsheetReader::ensureCsv($caminho));
        self::assertSame([
            ['Código', 'Descrição', 'Localidade'],
            ['001', 'Cadeira azul', 'Sala de teste'],
            ['002', 'Mesa branca', 'Escritório de teste'],
        ], UniversalSpreadsheetReader::read($caminho));
    }
}

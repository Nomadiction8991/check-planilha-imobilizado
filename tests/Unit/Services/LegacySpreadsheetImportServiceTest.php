<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CsvParserService;
use App\Services\LegacySpreadsheetImportService;
use App\Models\Legacy\Usuario;
use Illuminate\Support\Facades\Session;
use ReflectionMethod;
use Tests\TestCase;

final class LegacySpreadsheetImportServiceTest extends TestCase
{
    public function testResolveImportDirectoryUsesProjectStoragePath(): void
    {
        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'resolveImportDirectory');
        $method->setAccessible(true);

        self::assertSame(
            storage_path('tmp'),
            $method->invoke($service),
        );
    }

    public function testBuildChurchPreviewSummariesGroupsByChurch(): void
    {
        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'buildChurchPreviewSummaries');
        $method->setAccessible(true);

        $summaries = $method->invoke(
            $service,
            [
                [
                    'status' => CsvParserService::STATUS_NOVO,
                    'dados_csv' => ['codigo_comum' => '12-3456'],
                ],
                [
                    'status' => CsvParserService::STATUS_ATUALIZAR,
                    'dados_csv' => ['codigo_comum' => '12-3456'],
                ],
                [
                    'status' => CsvParserService::STATUS_EXCLUIR,
                    'dados_csv' => ['codigo_comum' => '12-3456'],
                ],
                [
                    'status' => 'erro',
                    'dados_csv' => ['codigo_comum' => ''],
                ],
            ],
            0,
        );

        self::assertCount(2, $summaries);

        $churchSummary = array_values(array_filter(
            $summaries,
            static fn (array $summary): bool => $summary['codigo'] === '12-3456',
        ))[0] ?? null;

        self::assertIsArray($churchSummary);
        self::assertSame(3, $churchSummary['total']);
        self::assertSame(1, $churchSummary['novos']);
        self::assertSame(1, $churchSummary['atualizar']);
        self::assertSame(1, $churchSummary['exclusoes']);
        self::assertSame('com_alteracoes', $churchSummary['status']);

        $fallbackSummary = array_values(array_filter(
            $summaries,
            static fn (array $summary): bool => $summary['descricao'] === 'Sem localidade detectada',
        ))[0] ?? null;

        self::assertIsArray($fallbackSummary);
        self::assertSame(1, $fallbackSummary['erros']);
        self::assertSame('com_erro', $fallbackSummary['status']);
    }

    public function testBuildConfirmActionsByChurchUsesChurchSelectionOnly(): void
    {
        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'buildConfirmActionsByChurch');
        $method->setAccessible(true);

        $actions = $method->invoke(
            $service,
            [
                [
                    'linha_csv' => 10,
                    'status' => CsvParserService::STATUS_NOVO,
                    'dados_csv' => ['codigo_comum' => '12-3456'],
                ],
                [
                    'linha_csv' => 'ex15',
                    'status' => CsvParserService::STATUS_EXCLUIR,
                    'dados_csv' => ['codigo_comum' => '12-3456'],
                ],
                [
                    'linha_csv' => 20,
                    'status' => CsvParserService::STATUS_ATUALIZAR,
                    'dados_csv' => ['codigo_comum' => '34-5678'],
                ],
                [
                    'linha_csv' => 30,
                    'status' => 'erro',
                    'dados_csv' => ['codigo_comum' => '12-3456'],
                ],
            ],
            ['12-3456' => CsvParserService::ACAO_IMPORTAR, '34-5678' => CsvParserService::ACAO_PULAR], [],
            0,
            false,
        );

        self::assertSame([
            10 => CsvParserService::ACAO_IMPORTAR,
            'ex15' => CsvParserService::ACAO_EXCLUIR,
            20 => CsvParserService::ACAO_PULAR,
            30 => CsvParserService::ACAO_PULAR,
        ], $actions);
    }

    public function testAssertImportErrorScopeAllowsCurrentAdministration(): void
    {
        Session::put('administracao_id', 3);
        Session::forget('comum_id');

        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'assertImportErrorScope');
        $method->setAccessible(true);

        $method->invoke($service, (object) [
            'administracao_id' => 3,
            'comum_id' => 7,
        ]);

        self::assertTrue(true);
    }

    public function testAssertImportErrorScopeAllowsScopedAdministrations(): void
    {
        Session::put('administracao_id', 7);
        Session::put('administracoes_permitidas', [7, 8]);
        Session::forget('comum_id');
        Session::put('is_admin', false);

        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'assertImportErrorScope');
        $method->setAccessible(true);

        $method->invoke($service, (object) [
            'administracao_id' => 8,
            'comum_id' => 7,
        ]);

        self::assertTrue(true);
    }

    public function testAssertImportErrorScopeRejectsDifferentAdministration(): void
    {
        Session::put('administracao_id', 3);
        Session::forget('comum_id');

        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'assertImportErrorScope');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $method->invoke($service, (object) [
            'administracao_id' => 4,
            'comum_id' => 7,
        ]);
    }

    public function testAssertImportErrorScopeFallsBackToCurrentChurch(): void
    {
        Session::forget('administracao_id');
        Session::put('comum_id', 7);

        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'assertImportErrorScope');
        $method->setAccessible(true);

        $method->invoke($service, (object) [
            'administracao_id' => 4,
            'comum_id' => 7,
        ]);

        self::assertTrue(true);
    }

    public function testIsUserAllowedForAdministrationAcceptsPermittedAdministration(): void
    {
        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'isUserAllowedForAdministration');
        $method->setAccessible(true);

        $user = new Usuario();
        $user->forceFill([
            'id' => 9,
            'tipo' => 'operador',
            'administracao_id' => 7,
            'administracoes_permitidas' => [8],
        ]);

        self::assertTrue($method->invoke($service, $user, 8));
    }

    public function testCurrentAdministrationScopeIdsUsesPermittedAdministrations(): void
    {
        Session::forget('administracao_id');
        Session::put('administracoes_permitidas', [7, 8]);
        Session::forget('comum_id');
        Session::put('is_admin', false);

        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'currentAdministrationScopeIds');
        $method->setAccessible(true);

        self::assertSame([7, 8], $method->invoke($service));

        Session::forget('administracoes_permitidas');
        Session::forget('is_admin');
    }

    public function testFriendlyErrorMessagePreservaErroDeColunasObrigatorias(): void
    {
        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'friendlyErrorMessage');

        $original = 'A planilha não possui dados nas colunas obrigatórias: Nome (coluna D). '
            . 'Verifique se o arquivo segue o layout esperado ou ajuste o mapeamento de colunas na configuração.';

        self::assertSame($original, $method->invoke($service, $original));
    }

    public function testFriendlyErrorMessagePreservaErroDeProdutoSemNome(): void
    {
        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'friendlyErrorMessage');

        $original = 'Produto sem nome na planilha (código 09-0565 / 0002, linha 31). '
            . 'Verifique se a coluna de nome está preenchida.';

        self::assertSame($original, $method->invoke($service, $original));
    }

    public function testExtractPreviewErrorsListaSomenteLinhasComFalha(): void
    {
        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'extractPreviewErrors');

        $erros = $method->invoke($service, [
            [
                'linha_csv' => 10,
                'status' => CsvParserService::STATUS_NOVO,
                'dados_csv' => ['codigo' => '09-0565 / 0001'],
            ],
            [
                'linha_csv' => 11,
                'status' => 'erro',
                'erro' => 'Dependência inválida.',
                'dados_csv' => [
                    'codigo' => '09-0565 / 0002',
                    'bem' => 'CADEIRA',
                    'complemento' => 'METALICA',
                ],
            ],
            [
                'linha_csv' => 12,
                'status' => 'erro',
                'dados_csv' => ['codigo' => ''],
            ],
            [
                'status' => 'erro',
                'erro' => 'Sem linha informada.',
                'dados_csv' => [],
            ],
        ]);

        self::assertCount(3, $erros);

        self::assertSame(11, $erros[0]['linha']);
        self::assertSame('09-0565 / 0002', $erros[0]['codigo']);
        self::assertSame('CADEIRA METALICA', $erros[0]['nome']);
        self::assertSame('Dependência inválida.', $erros[0]['mensagem']);

        self::assertSame(12, $erros[1]['linha']);
        self::assertSame('', $erros[1]['codigo']);
        self::assertSame('Erro desconhecido', $erros[1]['mensagem']);

        self::assertSame(0, $erros[2]['linha']);
        self::assertSame('Sem linha informada.', $erros[2]['mensagem']);
    }

    public function testExtractPreviewErrorsRespeitaLimiteInformado(): void
    {
        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'extractPreviewErrors');

        $registros = [];
        for ($i = 1; $i <= 60; $i++) {
            $registros[] = [
                'linha_csv' => $i,
                'status' => 'erro',
                'erro' => sprintf('Falha na linha %d.', $i),
                'dados_csv' => [],
            ];
        }

        $erros = $method->invoke($service, $registros, 50);

        self::assertCount(50, $erros);
        self::assertSame(1, $erros[0]['linha']);
        self::assertSame(50, $erros[49]['linha']);
    }

    public function testDownloadImportErrorsCsvSanitizaCamposTextuaisContraFormulas(): void
    {
        $service = new LegacySpreadsheetImportService();

        $method = new ReflectionMethod($service, 'sanitizeCsvText');
        $method->setAccessible(true);

        self::assertSame("'=SOMA(1;2)", $method->invoke($service, '=SOMA(1;2)'));
        self::assertSame("'+551199999", $method->invoke($service, '+551199999'));
        self::assertSame("'-100", $method->invoke($service, '-100'));
        self::assertSame("'@COMUM", $method->invoke($service, '@COMUM'));
        self::assertSame("'\tTAB", $method->invoke($service, "\tTAB"));
        self::assertSame("CADEIRA NORMAL", $method->invoke($service, 'CADEIRA NORMAL'));
        self::assertSame('', $method->invoke($service, ''));
        self::assertSame('', $method->invoke($service, null));
    }
}

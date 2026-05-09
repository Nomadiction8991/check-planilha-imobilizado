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
            storage_path('importacao'),
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
            ['12-3456' => CsvParserService::ACAO_IMPORTAR, '34-5678' => CsvParserService::ACAO_PULAR],
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
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\LegacyAuditEntryData;
use App\Services\LegacyAuditTrailService;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class LegacyAuditTrailServiceTest extends TestCase
{
    private string $storageFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageFile = tempnam(sys_get_temp_dir(), 'audit_') ?: sys_get_temp_dir() . '/audit_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_file($this->storageFile)) {
            unlink($this->storageFile);
        }

        parent::tearDown();
    }

    public function testRecordAndPaginateAuditEntries(): void
    {
        $service = new LegacyAuditTrailService($this->storageFile);

        $service->record(new LegacyAuditEntryData(
            occurredAt: '2026-04-17 09:15:00',
            userId: 1,
            userName: 'Ana',
            userEmail: 'ana@example.com',
            administrationId: 9,
            churchId: null,
            isAdmin: false,
            module: 'Produtos',
            action: 'Atualização',
            description: 'Produto atualizado com sucesso.',
            routeName: 'migration.products.update',
            path: 'products/1',
            method: 'PUT',
            statusCode: 302,
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        ));

        $service->record(new LegacyAuditEntryData(
            occurredAt: '2026-04-17 10:45:00',
            userId: 2,
            userName: 'Bruno',
            userEmail: 'bruno@example.com',
            administrationId: 8,
            churchId: null,
            isAdmin: false,
            module: 'Usuários',
            action: 'Criação',
            description: 'Usuário cadastrado com sucesso.',
            routeName: 'migration.users.store',
            path: 'users',
            method: 'POST',
            statusCode: 302,
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        ));

        $paginator = $service->paginate(
            ['search' => 'produto', 'module' => 'Produtos'],
            1,
            9,
            null,
            false,
            '/audits',
            [],
            1,
            20,
        );

        self::assertInstanceOf(LengthAwarePaginator::class, $paginator);
        self::assertSame(1, $paginator->total());

        $items = $paginator->items();
        self::assertCount(1, $items);
        self::assertInstanceOf(LegacyAuditEntryData::class, $items[0]);
        self::assertSame('Produtos', $items[0]->module);
        self::assertSame('Produto atualizado com sucesso.', $items[0]->description);
    }

    public function testAvailableModulesUsesConfiguredValues(): void
    {
        $service = new LegacyAuditTrailService($this->storageFile);

        self::assertContains('Sessão', $service->availableModules());
        self::assertContains('Importação', $service->availableModules());
    }

    /**
     * @param array<string, string> $extra
     */
    private function recordEntry(
        LegacyAuditTrailService $service,
        string $occurredAt,
        string $userName,
        ?int $administrationId,
        string $module = 'Sessão',
        array $extra = [],
    ): void {
        $service->record(new LegacyAuditEntryData(
            occurredAt: $occurredAt,
            userId: 1,
            userName: $userName,
            userEmail: $extra['userEmail'] ?? ($userName === 'Ana' ? 'ana@example.com' : null),
            administrationId: $administrationId,
            churchId: $extra['churchId'] ?? null,
            isAdmin: false,
            module: $module,
            action: $extra['action'] ?? 'Login',
            description: $extra['description'] ?? 'Evento auditado de teste.',
            routeName: $extra['routeName'] ?? 'migration.login.store',
            path: $extra['path'] ?? 'login',
            method: 'POST',
            statusCode: 302,
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        ));
    }

    public function testExportCsvReturnsAllFilteredEntriesWithHeaders(): void
    {
        $service = new LegacyAuditTrailService($this->storageFile);

        // 25 entradas que casam com o filtro — mais que uma página de 20.
        for ($i = 1; $i <= 25; $i++) {
            $this->recordEntry(
                $service,
                sprintf('2026-04-17 09:%02d:00', $i),
                'Ana',
                9,
                'Sessão',
                ['description' => 'Login número ' . $i],
            );
        }

        // Entrada fora dos filtros (outro módulo) — não pode aparecer.
        $this->recordEntry($service, '2026-04-17 10:00:00', 'Ana', 9, 'Produtos');

        $file = $service->exportCsv(
            ['search' => '', 'module' => 'Sessão'],
            1,
            9,
            null,
            false,
        );

        self::assertArrayHasKey('filename', $file);
        self::assertArrayHasKey('content', $file);
        self::assertNotSame('', $file['content']);
        self::assertStringStartsWith('auditoria_', $file['filename']);
        self::assertStringEndsWith('.csv', $file['filename']);

        // BOM UTF-8 presente no início do conteúdo.
        self::assertSame("\xEF\xBB\xBF", mb_substr($file['content'], 0, 3, '8bit'));

        // Round-trip com separador ponto e vírgula (nunca comparação por string crua).
        $lines = preg_split('/\r\n|\r|\n/', rtrim($file['content'], "\r\n")) ?: [];
        self::assertCount(26, $lines); // cabeçalho + 25 eventos

        // Ao ler, tirar o BOM do primeiro campo (lição do CSV no PHP 8.5).
        $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]) ?? $lines[0];
        $header = str_getcsv($lines[0], ';');
        self::assertSame('Data/Hora', $header[0]);
        self::assertSame('Usuário', $header[1]);
        self::assertSame('Módulo', array_search('Módulo', $header, true) !== false ? 'Módulo' : '');

        $rows = array_slice($lines, 1);
        $descriptions = [];
        foreach ($rows as $line) {
            $row = str_getcsv($line, ';');
            self::assertCount(13, $row);
            self::assertSame('Sessão', $row[5]);
            $descriptions[] = $row[7];
        }

        for ($i = 1; $i <= 25; $i++) {
            self::assertContains('Login número ' . $i, $descriptions);
        }
        self::assertNotContains('Evento auditado de teste.', $descriptions);
    }

    public function testExportCsvRespectsUserScopeForNonAdmin(): void
    {
        $service = new LegacyAuditTrailService($this->storageFile);

        // Mesma administração do usuário exportando.
        $this->recordEntry($service, '2026-04-17 09:00:00', 'Ana', 9);
        // Outra administração — deve ficar fora da exportação.
        $this->recordEntry($service, '2026-04-17 09:05:00', 'Bruno', 8);

        $file = $service->exportCsv(
            ['search' => '', 'module' => ''],
            1,
            9,
            null,
            false,
        );

        $lines = preg_split('/\r\n|\r|\n/', trim($file['content'])) ?: [];
        self::assertCount(2, $lines); // cabeçalho + 1 evento

        $row = str_getcsv($lines[1], ';');
        self::assertSame('Ana', $row[1]);
        self::assertSame('9', $row[3]);
    }

    public function testExportCsvWithoutEntriesSignalsEmptyResult(): void
    {
        $service = new LegacyAuditTrailService($this->storageFile);

        $file = $service->exportCsv(
            ['search' => 'nada-encontrado', 'module' => ''],
            1,
            9,
            null,
            false,
        );

        self::assertSame('', $file['content']);
    }

    public function testExportCsvNeutralizesFormulaLikeTextFields(): void
    {
        $service = new LegacyAuditTrailService($this->storageFile);
        $this->recordEntry($service, '2026-04-17 09:00:00', '=Nome', 9, '+Módulo', [
            'description' => '=1+1',
            'routeName' => '@rota',
            'path' => "\t/caminho",
        ]);

        $file = $service->exportCsv(['search' => '', 'module' => ''], 1, 9, null, false);
        $lines = preg_split('/\r\n|\r|\n/', trim($file['content'])) ?: [];
        $row = str_getcsv($lines[1], ';');

        self::assertSame("'=Nome", $row[1]);
        self::assertSame("'=1+1", $row[7]);
        self::assertSame("'@rota", $row[8]);
        self::assertSame("'\t/caminho", $row[9]);
        self::assertSame('2026-04-17 09:00:00', $row[0]);
        self::assertSame('9', $row[3]);
    }

    public function testPaginateAndExportFilterByAdministrationWhenAdmin(): void
    {
        $service = new LegacyAuditTrailService($this->storageFile);
        $this->recordEntry($service, '2026-04-17 09:00:00', 'Admin 1', 10);
        $this->recordEntry($service, '2026-04-17 09:10:00', 'Admin 2', 20);

        $paginator = $service->paginate(
            ['administracao_id' => '20'],
            1,
            null,
            null,
            true,
            '/audits',
        );

        self::assertSame(1, $paginator->total());
        self::assertSame(20, $paginator->items()[0]->administrationId);

        $export = $service->exportCsv(
            ['administracao_id' => '20'],
            1,
            null,
            null,
            true,
        );

        $lines = preg_split('/\r\n|\r|\n/', trim($export['content'])) ?: [];
        self::assertCount(2, $lines);
        $row = str_getcsv($lines[1], ';');
        self::assertSame('Admin 2', $row[1]);
        self::assertSame('20', $row[3]);
    }
}

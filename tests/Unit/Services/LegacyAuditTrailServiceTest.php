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
}

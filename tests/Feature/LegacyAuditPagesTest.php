<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuditTrailServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\DTO\LegacyAuditEntryData;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyAuditPagesTest extends TestCase
{
    private function mockAuditUser(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 7,
                'nome' => 'Maria Oliveira',
                'email' => 'maria@example.com',
                'comum_id' => null,
                'administracao_id' => 2,
                'is_admin' => false,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn(null);
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('availableAdministrations')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });

        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([
                'audits.view' => true,
            ]);
            $mock->shouldReceive('can')->andReturnTrue();
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });
    }

    private function bindAuditServiceWithEntry(LegacyAuditEntryData $entry): void
    {
        $this->app->instance(
            LegacyAuditTrailServiceInterface::class,
            new class($entry) implements LegacyAuditTrailServiceInterface
            {
                public function __construct(
                    private readonly LegacyAuditEntryData $storedEntry,
                ) {
                }

                public function record(LegacyAuditEntryData $entry): void
                {
                }

                public function paginate(
                    array $filters,
                    ?int $userId,
                    ?int $administrationId,
                    ?int $churchId,
                    bool $isAdmin,
                    string $path,
                    array $query = [],
                    int $page = 1,
                    int $perPage = 20,
                ): LengthAwarePaginator {
                    return new LengthAwarePaginator(
                        items: collect([$this->storedEntry]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: [
                            'path' => $path,
                            'query' => $query,
                        ]
                    );
                }

                public function availableModules(): array
                {
                    return [
                        'Sessão',
                        'Produtos',
                    ];
                }

                public function exportCsv(
                    array $filters,
                    ?int $userId,
                    ?int $administrationId,
                    ?int $churchId,
                    bool $isAdmin,
                ): array {
                    return ['filename' => '', 'content' => ''];
                }
            }
        );
    }

    public function testAuditPageRendersLoggedEvents(): void
    {
        $this->mockAuditUser();

        $this->bindAuditServiceWithEntry(new LegacyAuditEntryData(
            occurredAt: '2026-04-17 10:30:15',
            userId: 7,
            userName: 'Maria Oliveira',
            userEmail: 'maria@example.com',
            administrationId: 2,
            churchId: null,
            isAdmin: false,
            module: 'Sessão',
            action: 'Login',
            description: 'Autenticação realizada com sucesso.',
            routeName: 'migration.login.store',
            path: 'login',
            method: 'POST',
            statusCode: 302,
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        ));

        $response = $this->get('/audits?busca=Login');

        $response->assertOk();
        $response->assertSee('Auditoria do sistema');
        $response->assertSee('Escopo atual: Administração #2');
        $response->assertSee('Maria Oliveira');
        $response->assertSee('Autenticação realizada com sucesso.');
        $response->assertSee('Sessão');
        $response->assertSee('Produtos');
    }

    public function testAuditPageShowsExportButtonPreservingCurrentFilters(): void
    {
        $this->mockAuditUser();

        $this->bindAuditServiceWithEntry(new LegacyAuditEntryData(
            occurredAt: '2026-04-17 10:30:15',
            userId: 7,
            userName: 'Maria Oliveira',
            userEmail: 'maria@example.com',
            administrationId: 2,
            churchId: null,
            isAdmin: false,
            module: 'Sessão',
            action: 'Login',
            description: 'Autenticação realizada com sucesso.',
            routeName: 'migration.login.store',
            path: 'login',
            method: 'POST',
            statusCode: 302,
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        ));

        $response = $this->get('/audits?busca=Login&modulo=Sess%C3%A3o');

        $response->assertOk();

        // O botão de exportar preserva busca e módulo na URL do download
        // (no HTML os separadores aparecem escapados como &amp;).
        $exportPath = '/audits/export?busca=Login&amp;modulo=Sess%C3%A3o';
        $response->assertSee($exportPath, false);
        $response->assertSee('Exportar CSV', false);
    }
}

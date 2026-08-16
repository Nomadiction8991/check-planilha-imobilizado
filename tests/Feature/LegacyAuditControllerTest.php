<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuditTrailServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\DTO\LegacyAuditEntryData;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyAuditControllerTest extends TestCase
{
    private const int PER_PAGE = 20;

    private function mockAuthenticatedUser(): void
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
            $mock->shouldReceive('editorItems')->andReturn([]);
            $mock->shouldReceive('availableKeys')->andReturn([]);
            $mock->shouldReceive('currentOrder')->andReturn(new \App\DTO\LegacyNavigationOrderData([]));
            $mock->shouldReceive('saveOrder')->andReturnNull();
        });
    }

    public function testIndexReturnsAuditEntriesWithCorrectPagination(): void
    {
        $this->mockAuthenticatedUser();

        $entries = collect([
            new LegacyAuditEntryData(
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
            ),
            new LegacyAuditEntryData(
                occurredAt: '2026-04-17 11:00:00',
                userId: 7,
                userName: 'Maria Oliveira',
                userEmail: 'maria@example.com',
                administrationId: 2,
                churchId: null,
                isAdmin: false,
                module: 'Produtos',
                action: 'Exportar',
                description: 'Exportação de produtos concluída.',
                routeName: 'migration.products.index',
                path: 'products',
                method: 'GET',
                statusCode: 200,
                ipAddress: '127.0.0.1',
                userAgent: 'PHPUnit',
            ),
        ]);

        $paginator = new LengthAwarePaginator(
            items: $entries,
            total: 25,
            perPage: self::PER_PAGE,
            currentPage: 1,
            options: ['path' => url('/audits'), 'query' => []],
        );

        $this->bindAuditTrailService($paginator);

        $response = $this->get(route('migration.audits.index'));

        $response->assertOk();
        $response->assertViewHas('audits');
        $response->assertViewHas('filters');
        $response->assertViewHas('modules');
        $response->assertViewHas('scopeLabel');
        $response->assertSee('Auditoria do sistema');
        $response->assertSee('Escopo atual: Administração #2');
        $response->assertSee('Maria Oliveira');
        $response->assertSee('Autenticação realizada com sucesso.');
        $response->assertSee('Exportação de produtos concluída.');
        $response->assertSee('Sessão');
        $response->assertSee('Produtos');

        /** @var LengthAwarePaginator $audits */
        $audits = $response->viewData('audits');
        $this->assertSame(1, $audits->currentPage());
        $this->assertSame(2, $audits->count());
        $this->assertSame(25, $audits->total());
        $this->assertTrue($audits->hasMorePages());
    }

    public function testIndexFirstPagePaginationBehavior(): void
    {
        $this->mockAuthenticatedUser();

        $entries = $this->generateEntries(self::PER_PAGE, '2026-04-17 10:00:00', 'Entrada no sistema.');

        $paginator = new LengthAwarePaginator(
            items: $entries,
            total: 45,
            perPage: self::PER_PAGE,
            currentPage: 1,
            options: ['path' => url('/audits'), 'query' => ['page' => '1']],
        );

        $this->bindAuditTrailService($paginator);

        $response = $this->get(route('migration.audits.index', ['page' => 1]));

        $response->assertOk();

        /** @var LengthAwarePaginator $audits */
        $audits = $response->viewData('audits');
        $this->assertSame(1, $audits->currentPage());
        $this->assertSame(self::PER_PAGE, $audits->count());
        $this->assertSame(45, $audits->total());
        $this->assertTrue($audits->hasMorePages());
    }

    public function testIndexLastPagePaginationBehavior(): void
    {
        $this->mockAuthenticatedUser();

        $entries = $this->generateEntries(5, '2026-04-17 12:00:00', 'Saída do sistema.');

        $paginator = new LengthAwarePaginator(
            items: $entries,
            total: 45,
            perPage: self::PER_PAGE,
            currentPage: 3,
            options: ['path' => url('/audits'), 'query' => ['page' => '3']],
        );

        $this->bindAuditTrailService($paginator);

        $response = $this->get(route('migration.audits.index', ['page' => 3]));

        $response->assertOk();

        /** @var LengthAwarePaginator $audits */
        $audits = $response->viewData('audits');
        $this->assertSame(3, $audits->currentPage());
        $this->assertSame(5, $audits->count());
        $this->assertSame(45, $audits->total());
        $this->assertFalse($audits->hasMorePages());
    }

    public function testIndexOutOfRangePageReturnsEmptyResults(): void
    {
        $this->mockAuthenticatedUser();

        $entries = collect();

        $paginator = new LengthAwarePaginator(
            items: $entries,
            total: 45,
            perPage: self::PER_PAGE,
            currentPage: 100,
            options: ['path' => url('/audits'), 'query' => ['page' => '100']],
        );

        $this->bindAuditTrailService($paginator);

        $response = $this->get(route('migration.audits.index', ['page' => 100]));

        $response->assertOk();

        /** @var LengthAwarePaginator $audits */
        $audits = $response->viewData('audits');
        $this->assertSame(100, $audits->currentPage());
        $this->assertCount(0, $audits->items());
        $this->assertSame(45, $audits->total());
        $this->assertFalse($audits->hasMorePages());
    }

    public function testUnauthenticatedRequestRedirectsToLogin(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn(null);
            $mock->shouldReceive('currentChurch')->andReturn(null);
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });

        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([]);
            $mock->shouldReceive('can')->andReturnFalse();
        });

        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.audits.index'));

        $response->assertRedirect(route('migration.login'));
    }

    /**
     * @return Collection<int, LegacyAuditEntryData>
     */
    private function generateEntries(int $count, string $occurredAt, string $description): Collection
    {
        return collect(array_fill(0, $count, new LegacyAuditEntryData(
            occurredAt: $occurredAt,
            userId: 7,
            userName: 'Maria Oliveira',
            userEmail: 'maria@example.com',
            administrationId: 2,
            churchId: null,
            isAdmin: false,
            module: 'Sessão',
            action: 'Ação',
            description: $description,
            routeName: null,
            path: '/audits',
            method: 'GET',
            statusCode: 200,
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        )));
    }

    private function bindAuditTrailService(LengthAwarePaginator $paginator): void
    {
        $this->app->instance(
            LegacyAuditTrailServiceInterface::class,
            new class($paginator) implements LegacyAuditTrailServiceInterface
            {
                public function __construct(
                    private readonly LengthAwarePaginator $paginator,
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
                    return $this->paginator;
                }

                public function availableModules(): array
                {
                    return [
                        'Sessão',
                        'Produtos',
                        'Auditoria',
                    ];
                }
            },
        );
    }
}

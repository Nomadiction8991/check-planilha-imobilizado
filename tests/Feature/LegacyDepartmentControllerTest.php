<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyDepartmentBrowserServiceInterface;
use App\Contracts\LegacyDepartmentManagementServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\DepartmentFilters;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyDepartmentControllerTest extends TestCase
{
    private const int CHURCH_ID = 7;
    private Dependencia $boundDepartment;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock auth session to prevent view composer from querying DB.
        $this->mock(
            LegacyAuthSessionServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('currentUser')
                    ->andReturn([
                        'id' => 1,
                        'nome' => 'Administrador',
                        'email' => 'ADMIN@LOCALHOST',
                        'comum_id' => null,
                        'administracao_id' => null,
                        'administracoes_permitidas' => [self::CHURCH_ID],
                        'is_admin' => true,
                    ]);
                $mock->shouldReceive('currentChurch')->andReturn(null);
                $mock->shouldReceive('availableChurches')->andReturn(collect());
                $mock->shouldReceive('filterPinStates')->andReturn([]);
            }
        );

        // Mock navigation service so the layout doesn't hit the DB.
        $this->mock(
            LegacyNavigationServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('navigation')->andReturn([]);
                $mock->shouldReceive('editorItems')->andReturn([]);
            }
        );

        $this->createDatabaseTables();

        // Create a church row so the comum relation works for edit page.
        Comum::query()->create([
            'id' => self::CHURCH_ID,
            'codigo' => '12-3456',
            'descricao' => 'Central Cuiabá',
        ]);

        $this->boundDepartment = $this->makeDepartment(
            id: 2,
            churchId: self::CHURCH_ID,
            description: 'SALAO',
        );

        // Pre-load the comum relation so edit() does not fire a query.
        $this->boundDepartment->setRelation('comum', Comum::query()->find(self::CHURCH_ID));

        $this->app['router']->bind('department', fn (): Dependencia => $this->boundDepartment);

        // Default browser service mock (overridden per-test when needed).
        $this->app->instance(
            LegacyDepartmentBrowserServiceInterface::class,
            new class implements LegacyDepartmentBrowserServiceInterface
            {
                public function paginate(DepartmentFilters $filters): LengthAwarePaginator
                {
                    return new Paginator(
                        items: collect(),
                        total: 0,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/departments'],
                    );
                }

                public function churchOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                    ]);
                }

                public function countAll(): int
                {
                    return 0;
                }
            }
        );
    }

    // ─── Index ────────────────────────────────────────────────────────────────

    public function testIndexPageRendersPaginatedList(): void
    {
        $department = $this->makeDepartment(
            id: 5,
            churchId: self::CHURCH_ID,
            description: 'SALAO CENTRAL',
        );

        $this->mockBrowserService(paginatorItems: [$department], totalCount: 1);

        $response = $this->withSession($this->authSession())
            ->get(route('migration.departments.index'));

        $response->assertOk();
        $response->assertSee('SALAO CENTRAL');
    }

    public function testIndexPageRespectsSearchFilter(): void
    {
        $this->mockBrowserService(paginatorItems: [], totalCount: 0);

        $response = $this->withSession($this->authSession())
            ->get(route('migration.departments.index', ['busca' => 'NONEXISTENT']));

        $response->assertOk();
    }

    public function testIndexPageRedirectsGuestsToLogin(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.departments.index'));

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function testCreatePageRendersForm(): void
    {
        $response = $this->withSession($this->authSession())
            ->get(route('migration.departments.create'));

        $response->assertOk();
        $response->assertSee('Nova dependência.');
        $response->assertSee('Salvar dependência');
    }

    public function testCreatePageRedirectsGuestsToLogin(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.departments.create'));

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function testStoreCreatesDepartment(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->withArgs(fn ($dto): bool =>
                        $dto->churchId === self::CHURCH_ID
                        && $dto->description === 'Salao Central'
                    )
                    ->andReturn($this->makeDepartment(
                        id: 15,
                        churchId: self::CHURCH_ID,
                        description: 'SALAO CENTRAL',
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->post(route('migration.departments.store'), [
                'comum_id' => (string) self::CHURCH_ID,
                'descricao' => 'Salao Central',
            ]);

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Dependência criada com sucesso.');
        $response->assertSessionHas('status_detail', 'ID gerado: 15.');
    }

    public function testStoreViaLegacyCreateAlias(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->andReturn($this->makeDepartment(
                        id: 16,
                        churchId: self::CHURCH_ID,
                        description: 'SALAO CENTRAL',
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->post('/departments/create', [
                'comum_id' => (string) self::CHURCH_ID,
                'descricao' => 'Salao Central',
            ]);

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Dependência criada com sucesso.');
    }

    public function testStoreValidatesRequiredFields(): void
    {
        $response = $this->withSession($this->authSession())
            ->from(route('migration.departments.create'))
            ->post(route('migration.departments.store'), [
                'comum_id' => '',
                'descricao' => '',
            ]);

        $response->assertRedirect(route('migration.departments.create'));
        $response->assertSessionHasErrors(['comum_id', 'descricao']);
    }

    public function testStoreValidatesBlankDescription(): void
    {
        $response = $this->withSession($this->authSession())
            ->from(route('migration.departments.create'))
            ->post(route('migration.departments.store'), [
                'comum_id' => (string) self::CHURCH_ID,
                'descricao' => '   ',
            ]);

        $response->assertRedirect(route('migration.departments.create'));
        $response->assertSessionHasErrors(['descricao']);
    }

    public function testStoreShowsRuntimeValidationError(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->andThrow(new RuntimeException(
                        'Já existe uma dependência com essa descrição para a igreja selecionada.'
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->from(route('migration.departments.create'))
            ->post(route('migration.departments.store'), [
                'comum_id' => (string) self::CHURCH_ID,
                'descricao' => 'Salao',
            ]);

        $response->assertRedirect(route('migration.departments.create'));
        $response->assertSessionHas(
            'status',
            'Já existe uma dependência com essa descrição para a igreja selecionada.'
        );
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function testEditPageRendersForm(): void
    {
        $response = $this->withSession($this->authSession())
            ->get(route('migration.departments.edit', ['department' => $this->boundDepartment->id]));

        $response->assertOk();
        $response->assertSee('Editar dependência');
        $response->assertSee('SALAO');
        $response->assertSee('Salvar alterações');
    }

    public function testEditPageRedirectsGuestsToLogin(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.departments.edit', ['department' => 2]));

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function testUpdateChangesDepartment(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(
                        fn (Dependencia $department, $dto): bool =>
                            $department->id === 2
                            && $dto->churchId === 8
                            && $dto->description === 'Novo Salao'
                    )
                    ->andReturn($this->makeDepartment(
                        id: 2,
                        churchId: 8,
                        description: 'NOVO SALAO',
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->put(route('migration.departments.update', ['department' => 2]), [
                'comum_id' => 8,
                'descricao' => 'Novo Salao',
            ]);

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Dependência atualizada com sucesso.');
    }

    public function testUpdateViaLegacyPostAlias(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->andReturn($this->makeDepartment(
                        id: 2,
                        churchId: 8,
                        description: 'NOVO SALAO',
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->post(route('migration.departments.update.post', ['department' => 2]), [
                'comum_id' => 8,
                'descricao' => 'Novo Salao',
            ]);

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Dependência atualizada com sucesso.');
    }

    public function testUpdateValidatesRequiredFields(): void
    {
        $response = $this->withSession($this->authSession())
            ->from(route('migration.departments.edit', ['department' => 2]))
            ->put(route('migration.departments.update', ['department' => 2]), [
                'comum_id' => '',
                'descricao' => '',
            ]);

        $response->assertRedirect(route('migration.departments.edit', ['department' => 2]));
        $response->assertSessionHasErrors(['comum_id', 'descricao']);
    }

    public function testUpdateShowsRuntimeValidationError(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->andThrow(new RuntimeException(
                        'Já existe uma dependência com essa descrição para a igreja selecionada.'
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->from(route('migration.departments.edit', ['department' => 2]))
            ->put(route('migration.departments.update', ['department' => 2]), [
                'comum_id' => 8,
                'descricao' => 'Novo Salao',
            ]);

        $response->assertRedirect(route('migration.departments.edit', ['department' => 2]));
        $response->assertSessionHas(
            'status',
            'Já existe uma dependência com essa descrição para a igreja selecionada.'
        );
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function testDestroyDeletesUnusedDepartment(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->withArgs(fn (Dependencia $department): bool => $department->id === 2);
            }
        );

        $response = $this->withSession($this->authSession())
            ->delete(route('migration.departments.destroy', ['department' => 2]));

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Dependência excluída com sucesso.');
    }

    public function testDestroyViaLegacyPostAlias(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->withArgs(fn (Dependencia $department): bool => $department->id === 2);
            }
        );

        $response = $this->withSession($this->authSession())
            ->post(route('migration.departments.destroy.post', ['department' => 2]));

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas('status', 'Dependência excluída com sucesso.');
    }

    public function testDestroyBlocksDeletionWhenProductsAreLinked(): void
    {
        $this->mock(
            LegacyDepartmentManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->andThrow(new RuntimeException(
                        'Esta dependência não pode ser excluída porque já está vinculada a produtos.'
                    ));
            }
        );

        $response = $this->withSession($this->authSession())
            ->delete(route('migration.departments.destroy', ['department' => 2]));

        $response->assertRedirect(route('migration.departments.index'));
        $response->assertSessionHas(
            'status',
            'Esta dependência não pode ser excluída porque já está vinculada a produtos.'
        );
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createDatabaseTables(): void
    {
        DB::statement('
            CREATE TABLE IF NOT EXISTS "comums" (
                "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                "codigo" VARCHAR(255) NOT NULL,
                "cnpj" VARCHAR(255) DEFAULT NULL,
                "descricao" VARCHAR(255) NOT NULL,
                "administracao_id" INTEGER DEFAULT NULL,
                "estado" VARCHAR(255) DEFAULT NULL,
                "cidade" VARCHAR(255) DEFAULT NULL,
                "setor" VARCHAR(255) DEFAULT NULL
            )
        ');
        DB::statement('
            CREATE TABLE IF NOT EXISTS "dependencias" (
                "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                "comum_id" INTEGER DEFAULT NULL,
                "descricao" VARCHAR(255) NOT NULL
            )
        ');
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function authSession(): array
    {
        return [
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => self::CHURCH_ID,
            'is_admin' => true,
        ];
    }

    private function makeDepartment(
        int $id,
        int $churchId,
        string $description,
    ): Dependencia {
        $department = new Dependencia();
        $department->forceFill([
            'id' => $id,
            'comum_id' => $churchId,
            'descricao' => $description,
        ]);
        $department->exists = true;

        return $department;
    }

    /**
     * @param list<Dependencia> $paginatorItems
     */
    private function mockBrowserService(array $paginatorItems, int $totalCount): void
    {
        $this->app->instance(
            LegacyDepartmentBrowserServiceInterface::class,
            new class($paginatorItems, $totalCount) implements LegacyDepartmentBrowserServiceInterface
            {
                /**
                 * @param list<Dependencia> $paginatorItems
                 */
                public function __construct(
                    private readonly array $paginatorItems,
                    private readonly int $totalCount,
                ) {
                }

                public function paginate(DepartmentFilters $filters): LengthAwarePaginator
                {
                    return new Paginator(
                        items: collect($this->paginatorItems),
                        total: $this->totalCount,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/departments'],
                    );
                }

                public function churchOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                    ]);
                }

                public function countAll(): int
                {
                    return $this->totalCount;
                }
            }
        );
    }
}

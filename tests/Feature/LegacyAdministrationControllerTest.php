<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAdministrationBrowserServiceInterface;
use App\Contracts\LegacyAdministrationManagementServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\AdministrationFilters;
use App\Models\Legacy\Administracao;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyAdministrationControllerTest extends TestCase
{
    private const string ADMIN_DESCRIPTION = 'Administração Central';
    private const string ADMIN_CNPJ = '12345678000190';

    private Administracao $boundAdministration;

    private array $adminSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminSession = [
            'is_admin' => true,
            'legacy_permissions' => [
                'administrations.view' => true,
                'administrations.create' => true,
                'administrations.edit' => true,
                'administrations.delete' => true,
            ],
        ];

        $this->boundAdministration = $this->makeAdministration(
            id: 4,
            description: self::ADMIN_DESCRIPTION,
            cnpj: self::ADMIN_CNPJ,
        );

        $this->app['router']->bind(
            'administration',
            fn (): Administracao => $this->boundAdministration,
        );

        $this->mockAuthSession();
        $this->mockNavigation();
    }

    private function mockAuthSession(): void
    {
        $this->mock(
            LegacyAuthSessionServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('currentUser')->andReturn([
                    'id' => 1,
                    'nome' => 'Administrador',
                    'email' => 'ADMIN@LOCALHOST',
                    'comum_id' => null,
                    'administracao_id' => null,
                    'administracoes_permitidas' => [4],
                    'is_admin' => true,
                ]);
                $mock->shouldReceive('currentChurch')->andReturn(null);
                $mock->shouldReceive('availableChurches')->andReturn(collect());
                $mock->shouldReceive('filterPinStates')->andReturn([]);
            },
        );
    }

    private function mockNavigation(): void
    {
        $this->mock(
            LegacyNavigationServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('navigation')->andReturn([]);
                $mock->shouldReceive('editorItems')->andReturn([]);
            },
        );
    }

    // ─── Index ───────────────────────────────────────────────────────────────

    public function testIndexRendersAdministrationsList(): void
    {
        $this->app->instance(
            LegacyAdministrationBrowserServiceInterface::class,
            new class implements LegacyAdministrationBrowserServiceInterface
            {
                public function paginate(AdministrationFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id' => 4,
                                'descricao' => 'Administração Central',
                                'cnpj' => '12345678000190',
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/administrations'],
                    );
                }

                public function countAll(): int
                {
                    return 1;
                }
            },
        );

        $response = $this->withSession([
            'is_admin' => true,
            'legacy_permissions' => [
                'administrations.view' => true,
                'administrations.create' => true,
                'administrations.edit' => true,
                'administrations.delete' => true,
            ],
        ])->get(route('migration.administrations.index'));

        $response->assertOk();
        $response->assertSee('Administrações do sistema.');
        $response->assertSee('Administração Central');
        $response->assertSee('12345678000190');
        $response->assertSee('Nova administração');
    }

    public function testIndexShowsEmptyStateWhenNoRecords(): void
    {
        $this->app->instance(
            LegacyAdministrationBrowserServiceInterface::class,
            new class implements LegacyAdministrationBrowserServiceInterface
            {
                public function paginate(AdministrationFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect(),
                        total: 0,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/administrations'],
                    );
                }

                public function countAll(): int
                {
                    return 0;
                }
            },
        );

        $response = $this->withSession([
            'is_admin' => true,
            'legacy_permissions' => [
                'administrations.view' => true,
            ],
        ])->get(route('migration.administrations.index'));

        $response->assertOk();
        $response->assertSee('Administrações do sistema.');
        $response->assertSee('Nenhuma administração encontrada para os filtros atuais.');
        $response->assertSee('0 registro(s) cadastrados.');
    }

    public function testIndexPassesSearchFilterToService(): void
    {
        $this->app->instance(
            LegacyAdministrationBrowserServiceInterface::class,
            new class implements LegacyAdministrationBrowserServiceInterface
            {
                public function paginate(AdministrationFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id' => 4,
                                'descricao' => 'Administração Central',
                                'cnpj' => '12345678000190',
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/administrations'],
                    );
                }

                public function countAll(): int
                {
                    return 1;
                }
            },
        );

        $response = $this->withSession([
            'is_admin' => true,
            'legacy_permissions' => [
                'administrations.view' => true,
                'administrations.create' => true,
            ],
        ])->get(route('migration.administrations.index', ['busca' => 'Central']));

        $response->assertOk();
        $response->assertSee('Administração Central');
    }

    // ─── Create ──────────────────────────────────────────────────────────────

    public function testCreateRendersForm(): void
    {
        $response = $this->withSession([
            'is_admin' => true,
            'legacy_permissions' => ['administrations.create' => true],
        ])->get(route('migration.administrations.create'));

        $response->assertOk();
        $response->assertSee('Nova administração.');
        $response->assertSee('Salvar administração');
        $response->assertSee('data-mask="cnpj"', false);
    }

    // ─── Store ───────────────────────────────────────────────────────────────

    public function testStoreCreatesAdministration(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->withArgs(fn ($dto): bool =>
                        $dto->description === 'Administração Regional'
                        && $dto->cnpj === '12345678000191'
                    )
                    ->andReturn($this->makeAdministration(
                        id: 11,
                        description: 'Administração Regional',
                        cnpj: '12345678000191',
                    ));
            },
        );

        $authSession = [
            'is_admin' => true,
            'legacy_permissions' => [
                'administrations.create' => true,
                'administrations.edit' => true,
                'administrations.delete' => true,
            ],
        ];

        $response = $this->withSession($authSession)
            ->post(route('migration.administrations.store'), [
            'descricao' => 'Administração Regional',
            'cnpj' => '12345678000191',
            'estado' => 'MT',
            'cidade' => 'Cuiabá',
        ]);

        $response->assertRedirect(route('migration.administrations.index'));
        $response->assertSessionHas('status', 'Administração criada com sucesso.');
        $response->assertSessionHas('status_detail', 'ID gerado: 11.');
    }

    public function testStoreRejectsMissingFields(): void
    {
        $response = $this->from(route('migration.administrations.create'))
            ->post(route('migration.administrations.store'), [
                'descricao' => '',
                'cnpj' => '',
                'estado' => '',
                'cidade' => '',
            ]);

        $response->assertRedirect(route('migration.administrations.create'));
        $response->assertSessionHasErrors(['descricao', 'cnpj', 'estado', 'cidade']);
    }

    public function testStoreRejectsBlankDescriptionAndCnpj(): void
    {
        $response = $this->from(route('migration.administrations.create'))
            ->post(route('migration.administrations.store'), [
                'descricao' => '   ',
                'cnpj' => '   ',
                'estado' => 'MT',
                'cidade' => 'Cuiabá',
            ]);

        $response->assertRedirect(route('migration.administrations.create'));
        $response->assertSessionHasErrors(['descricao', 'cnpj']);
    }

    public function testStoreRejectsInvalidState(): void
    {
        $response = $this->from(route('migration.administrations.create'))
            ->post(route('migration.administrations.store'), [
                'descricao' => 'Administração Regional',
                'cnpj' => '12345678000191',
                'estado' => 'XX',
                'cidade' => 'Cuiabá',
            ]);

        $response->assertRedirect(route('migration.administrations.create'));
        $response->assertSessionHasErrors(['estado']);
    }

    public function testStoreHandlesBusinessErrorDuringCreate(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('create')
                    ->once()
                    ->andThrow(new RuntimeException('CNPJ já cadastrado para outra administração.'));
            },
        );

        $response = $this->from(route('migration.administrations.create'))
            ->post(route('migration.administrations.store'), [
                'descricao' => 'Administração Regional',
                'cnpj' => '12345678000191',
                'estado' => 'MT',
                'cidade' => 'Cuiabá',
            ]);

        $response->assertRedirect(route('migration.administrations.create'));
        $response->assertSessionHas('status', 'CNPJ já cadastrado para outra administração.');
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Edit ────────────────────────────────────────────────────────────────

    public function testEditRendersFormWithAdministrationData(): void
    {
        $response = $this->withSession([
            'is_admin' => true,
            'legacy_permissions' => ['administrations.edit' => true],
        ])->get(route('migration.administrations.edit', ['administration' => 4]));

        $response->assertOk();
        $response->assertSee('Editar administração.');
        $response->assertSee('Salvar alterações');
        $response->assertSee(self::ADMIN_DESCRIPTION);
        $response->assertSee('data-mask="cnpj"', false);
    }

    // ─── Update ──────────────────────────────────────────────────────────────

    public function testUpdateChangesAdministration(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(
                        fn (Administracao $administration, $dto): bool =>
                            $administration->id === 4
                            && $dto->description === 'Administração Atualizada'
                            && $dto->cnpj === '12345678000192'
                    )
                    ->andReturn($this->makeAdministration(
                        id: 4,
                        description: 'Administração Atualizada',
                        cnpj: '12345678000192',
                    ));
            },
        );

        $response = $this->put(route('migration.administrations.update', ['administration' => 4]), [
            'descricao' => 'Administração Atualizada',
            'cnpj' => '12345678000192',
            'estado' => 'MT',
            'cidade' => 'Cuiabá',
        ]);

        $response->assertRedirect(route('migration.administrations.index'));
        $response->assertSessionHas('status', 'Administração atualizada com sucesso.');
    }

    public function testUpdateRejectsInvalidData(): void
    {
        $response = $this->from(route('migration.administrations.edit', ['administration' => 4]))
            ->put(route('migration.administrations.update', ['administration' => 4]), [
                'descricao' => '   ',
                'cnpj' => '',
                'estado' => 'XX',
                'cidade' => '',
            ]);

        $response->assertRedirect(route('migration.administrations.edit', ['administration' => 4]));
        $response->assertSessionHasErrors(['descricao', 'cnpj', 'estado', 'cidade']);
    }

    public function testUpdateHandlesBusinessErrorDuringUpdate(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->andThrow(new RuntimeException('CNPJ inválido: CNPJ deve conter 14 dígitos.'));
            },
        );

        $response = $this->from(route('migration.administrations.edit', ['administration' => 4]))
            ->put(route('migration.administrations.update', ['administration' => 4]), [
                'descricao' => 'Administração Atualizada',
                'cnpj' => '123',
                'estado' => 'MT',
                'cidade' => 'Cuiabá',
            ]);

        $response->assertRedirect(route('migration.administrations.edit', ['administration' => 4]));
        $response->assertSessionHas('status', 'CNPJ inválido: CNPJ deve conter 14 dígitos.');
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Destroy ─────────────────────────────────────────────────────────────

    public function testDestroyDeletesUnusedAdministration(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->withArgs(fn (Administracao $administration): bool => $administration->id === 4);
            },
        );

        $response = $this->delete(route('migration.administrations.destroy', ['administration' => 4]));

        $response->assertRedirect(route('migration.administrations.index'));
        $response->assertSessionHas('status', 'Administração excluída com sucesso.');
    }

    public function testDestroyHandlesBusinessErrorWhenLinkedToImports(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->andThrow(new RuntimeException('Esta administração não pode ser excluída porque já está vinculada a importações.'));
            },
        );

        $response = $this->delete(route('migration.administrations.destroy', ['administration' => 4]));

        $response->assertRedirect(route('migration.administrations.index'));
        $response->assertSessionHas('status', 'Esta administração não pode ser excluída porque já está vinculada a importações.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testDestroyHandlesBusinessErrorWhenLinkedToUsers(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('delete')
                    ->once()
                    ->andThrow(new RuntimeException('Esta administração não pode ser excluída porque já está vinculada a usuários.'));
            },
        );

        $response = $this->delete(route('migration.administrations.destroy', ['administration' => 4]));

        $response->assertRedirect(route('migration.administrations.index'));
        $response->assertSessionHas('status', 'Esta administração não pode ser excluída porque já está vinculada a usuários.');
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Auth ────────────────────────────────────────────────────────────────

    public function testAuthenticationRequiredForAllRoutes(): void
    {
        $routes = [
            ['method' => 'GET', 'name' => 'migration.administrations.index'],
            ['method' => 'GET', 'name' => 'migration.administrations.create'],
            ['method' => 'GET', 'name' => 'migration.administrations.edit', 'params' => ['administration' => 4]],
        ];

        foreach ($routes as $route) {
            $response = $this->withSession(['_enforce_legacy_auth' => true])
                ->call($route['method'], route($route['name'], $route['params'] ?? []));

            $response->assertRedirect(route('migration.login'));
        }
    }

    public function testPermissionRequiredForStoreUpdateDestroy(): void
    {
        $this->mock(
            LegacyAdministrationManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldNotReceive('create');
                $mock->shouldNotReceive('update');
                $mock->shouldNotReceive('delete');
            },
        );

        $authSession = [
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'is_admin' => false,
            'legacy_permissions' => [],
        ];

        $storeResponse = $this->from(route('migration.administrations.create'))
            ->withSession($authSession)
            ->post(route('migration.administrations.store'), [
                'descricao' => 'Administração',
                'cnpj' => '12345678000191',
                'estado' => 'MT',
                'cidade' => 'Cuiabá',
            ]);
        $storeResponse->assertRedirect(route('migration.dashboard'));

        $updateResponse = $this->from(route('migration.administrations.edit', ['administration' => 4]))
            ->withSession($authSession)
            ->put(route('migration.administrations.update', ['administration' => 4]), [
                'descricao' => 'Administração',
                'cnpj' => '12345678000191',
                'estado' => 'MT',
                'cidade' => 'Cuiabá',
            ]);
        $updateResponse->assertRedirect(route('migration.dashboard'));

        $destroyResponse = $this->withSession($authSession)
            ->delete(route('migration.administrations.destroy', ['administration' => 4]));
        $destroyResponse->assertRedirect(route('migration.dashboard'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeAdministration(int $id, string $description, string $cnpj): Administracao
    {
        $administration = new Administracao();
        $administration->forceFill([
            'id' => $id,
            'descricao' => $description,
            'cnpj' => $cnpj,
        ]);
        $administration->exists = true;

        return $administration;
    }
}

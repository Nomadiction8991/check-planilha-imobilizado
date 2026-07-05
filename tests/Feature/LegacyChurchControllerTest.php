<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyChurchBrowserServiceInterface;
use App\Contracts\LegacyChurchManagementServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\DTO\ChurchFilters;
use App\Models\Legacy\Comum;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyChurchControllerTest extends TestCase
{
    private Comum $boundChurch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->boundChurch = $this->makeChurch();
        $this->app['router']->bind('church', fn (): Comum => $this->boundChurch);
    }

    // ─── Index ───────────────────────────────────────────────────────────────

    public function testIndexReturnsPaginatedChurches(): void
    {
        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id' => 7,
                                'codigo' => '12-3456',
                                'descricao' => 'Central Cuiabá',
                                'cidade' => 'Cuiaba',
                                'estado' => 'MT',
                                'setor' => 'Centro',
                                'active_products_count' => 18,
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/churches'],
                    );
                }

                public function countAll(): int
                {
                    return 5;
                }

                public function administrationOptions(): Collection
                {
                    return collect();
                }
            },
        );

        $response = $this->get(route('migration.churches.index'));

        $response->assertOk();
        $response->assertSee('Igrejas cadastradas no sistema.');
        $response->assertSee('Central Cuiabá');
        $response->assertSee('18');
    }

    public function testIndexHandlesEmptyData(): void
    {
        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect(),
                        total: 0,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/churches'],
                    );
                }

                public function countAll(): int
                {
                    return 0;
                }

                public function administrationOptions(): Collection
                {
                    return collect();
                }
            },
        );

        $response = $this->get(route('migration.churches.index'));

        $response->assertOk();
        $response->assertSee('Igrejas cadastradas no sistema.');
        $response->assertSee('Nenhuma igreja encontrada');
    }

    public function testIndexRespectsSearchFilter(): void
    {
        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id' => 7,
                                'codigo' => '12-3456',
                                'descricao' => 'Central Cuiabá',
                                'cidade' => 'Cuiaba',
                                'estado' => 'MT',
                                'setor' => 'Centro',
                                'active_products_count' => 18,
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/churches'],
                    );
                }

                public function countAll(): int
                {
                    return 5;
                }

                public function administrationOptions(): Collection
                {
                    return collect();
                }
            },
        );

        $response = $this->get(route('migration.churches.index', ['busca' => 'Central']));

        $response->assertOk();
        $response->assertSee('Central Cuiabá');
    }

    // ─── Public access (same controller, public route) ──────────────────────

    public function testPublicRouteRendersUsingPublicSession(): void
    {
        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id' => 7,
                                'codigo' => '12-3456',
                                'descricao' => 'Central Cuiabá',
                                'cidade' => 'Cuiaba',
                                'estado' => 'MT',
                                'setor' => 'Centro',
                                'active_products_count' => 18,
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/churches'],
                    );
                }

                public function countAll(): int
                {
                    return 1;
                }

                public function administrationOptions(): Collection
                {
                    return collect();
                }
            },
        );

        $response = $this->withSession([
            'public_acesso' => true,
            'public_comum_id' => 7,
        ])->get(route('migration.churches.public'));

        $response->assertOk();
        $response->assertSee('Central Cuiabá');
    }

    // ─── Edit ───────────────────────────────────────────────────────────────

    public function testEditPageRendersForm(): void
    {
        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect(),
                        total: 0,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/churches'],
                    );
                }

                public function countAll(): int
                {
                    return 0;
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'descricao' => 'Administração Central'],
                    ]);
                }
            },
        );

        $response = $this->get(route('migration.churches.edit', ['church' => 7]));

        $response->assertOk();
        $response->assertSee('Editar igreja.');
        $response->assertSee('Salvar alterações');
        $response->assertSee('12-3456');
        $response->assertSee('data-mask="cnpj"', false);
    }

    // ─── Update (POST) ──────────────────────────────────────────────────────

    public function testPostUpdateChangesChurchData(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(fn (Comum $church, $dto): bool =>
                        $church->id === 7
                        && $dto->description === 'Central Atualizada'
                        && $dto->cnpj === '12.345.678/0001-90'
                        && $dto->state === 'MT'
                        && $dto->city === 'Cuiaba'
                        && $dto->sector === 'Norte'
                    )
                    ->andReturn($this->makeChurch(description: 'CENTRAL ATUALIZADA'));
            },
        );

        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(items: collect(), total: 0, perPage: 20, currentPage: 1, options: ['path' => '/churches']);
                }

                public function countAll(): int
                {
                    return 0;
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'descricao' => 'Administração Central'],
                    ]);
                }
            },
        );

        $response = $this->post(route('migration.churches.update.post', ['church' => 7]), [
            'administracao_id' => 4,
            'descricao' => 'Central Atualizada',
            'cnpj' => '12.345.678/0001-90',
            'estado' => 'MT',
            'cidade' => 'Cuiaba',
            'setor' => 'Norte',
        ]);

        $response->assertRedirect(route('migration.churches.index'));
        $response->assertSessionHas('status', 'Igreja atualizada com sucesso.');
    }

    public function testPostUpdateShowsValidationErrors(): void
    {
        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(items: collect(), total: 0, perPage: 20, currentPage: 1, options: ['path' => '/churches']);
                }

                public function countAll(): int
                {
                    return 0;
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'descricao' => 'Administração Central'],
                    ]);
                }
            },
        );

        $response = $this->from(route('migration.churches.edit', ['church' => 7]))
            ->post(route('migration.churches.update.post', ['church' => 7]), [
                'administracao_id' => '',
                'descricao' => '   ',
                'cnpj' => '',
                'estado' => '',
                'cidade' => '',
                'setor' => '',
            ]);

        $response->assertRedirect(route('migration.churches.edit', ['church' => 7]));
        $response->assertSessionHasErrors([
            'administracao_id',
            'descricao',
            'cnpj',
            'estado',
            'cidade',
        ]);
    }

    public function testPostUpdateShowsBusinessError(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->andThrow(new RuntimeException('CNPJ inválido: CNPJ deve conter exatamente 14 caracteres.'));
            },
        );

        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(items: collect(), total: 0, perPage: 20, currentPage: 1, options: ['path' => '/churches']);
                }

                public function countAll(): int
                {
                    return 0;
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'descricao' => 'Administração Central'],
                    ]);
                }
            },
        );

        $response = $this->from(route('migration.churches.edit', ['church' => 7]))
            ->post(route('migration.churches.update.post', ['church' => 7]), [
                'administracao_id' => 4,
                'descricao' => 'Central Atualizada',
                'cnpj' => '123',
                'estado' => 'MT',
                'cidade' => 'Cuiaba',
                'setor' => 'Norte',
            ]);

        $response->assertRedirect(route('migration.churches.edit', ['church' => 7]));
        $response->assertSessionHas('status', 'CNPJ inválido: CNPJ deve conter exatamente 14 caracteres.');
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Update (PUT) ───────────────────────────────────────────────────────

    public function testPutUpdateChangesChurchData(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(fn (Comum $church, $dto): bool =>
                        $church->id === 7
                        && $dto->description === 'Central Atualizada'
                    )
                    ->andReturn($this->makeChurch(description: 'CENTRAL ATUALIZADA'));
            },
        );

        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(items: collect(), total: 0, perPage: 20, currentPage: 1, options: ['path' => '/churches']);
                }

                public function countAll(): int
                {
                    return 0;
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'descricao' => 'Administração Central'],
                    ]);
                }
            },
        );

        $response = $this->put(route('migration.churches.update', ['church' => 7]), [
            'administracao_id' => 4,
            'descricao' => 'Central Atualizada',
            'cnpj' => '12.345.678/0001-90',
            'estado' => 'MT',
            'cidade' => 'Cuiaba',
            'setor' => 'Norte',
        ]);

        $response->assertRedirect(route('migration.churches.index'));
        $response->assertSessionHas('status', 'Igreja atualizada com sucesso.');
    }

    public function testPutUpdateShowsValidationErrors(): void
    {
        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(items: collect(), total: 0, perPage: 20, currentPage: 1, options: ['path' => '/churches']);
                }

                public function countAll(): int
                {
                    return 0;
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'descricao' => 'Administração Central'],
                    ]);
                }
            },
        );

        $response = $this->from(route('migration.churches.edit', ['church' => 7]))
            ->put(route('migration.churches.update', ['church' => 7]), [
                'administracao_id' => '',
                'descricao' => '   ',
                'cnpj' => '',
                'estado' => '',
                'cidade' => '',
                'setor' => '',
            ]);

        $response->assertRedirect(route('migration.churches.edit', ['church' => 7]));
        $response->assertSessionHasErrors([
            'administracao_id',
            'descricao',
            'cnpj',
            'estado',
            'cidade',
        ]);
    }

    // ─── Products count ─────────────────────────────────────────────────────

    public function testProductsCountReturnsCountForValidId(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('countProducts')
                    ->once()
                    ->with(7)
                    ->andReturn(14);
            },
        );

        $response = $this->get(route('migration.churches.products-count', ['comum_id' => 7]));

        $response->assertOk();
        $response->assertJson(['count' => 14]);
    }

    public function testProductsCountReturnsErrorForInvalidId(): void
    {
        $response = $this->get(route('migration.churches.products-count', ['comum_id' => 0]));

        $response->assertStatus(400);
        $response->assertJson([
            'count' => 0,
            'error' => 'ID inválido',
        ]);
    }

    public function testProductsCountReturnsErrorForMissingId(): void
    {
        $response = $this->get(route('migration.churches.products-count'));

        $response->assertStatus(400);
        $response->assertJson([
            'count' => 0,
            'error' => 'ID inválido',
        ]);
    }

    public function testProductsCountReturnsErrorWhenServiceThrows(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('countProducts')
                    ->once()
                    ->with(7)
                    ->andThrow(new RuntimeException('Database connection failed'));
            },
        );

        $response = $this->get(route('migration.churches.products-count', ['comum_id' => 7]));

        $response->assertStatus(500);
        $response->assertJson([
            'count' => 0,
            'error' => 'Database connection failed',
        ]);
    }

    // ─── Delete products ────────────────────────────────────────────────────

    public function testDeleteProductsSucceeds(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('findChurch')
                    ->once()
                    ->with(7)
                    ->andReturn($this->makeChurch());
                $mock->shouldReceive('deleteProducts')
                    ->once()
                    ->withArgs(fn (Comum $church): bool => $church->id === 7)
                    ->andReturn(5);
            },
        );

        $response = $this->post(route('migration.churches.delete-products'), [
            'comum_id' => 7,
        ]);

        $response->assertRedirect(route('migration.churches.index'));
        $response->assertSessionHas('status', 'Todos os 5 produto(s) da igreja CENTRAL CUIABÁ foram excluídos.');
        $response->assertSessionHas('status_type', 'success');
    }

    public function testDeleteProductsFailsForInvalidId(): void
    {
        $response = $this->post(route('migration.churches.delete-products'), [
            'comum_id' => 0,
        ]);

        $response->assertRedirect(route('migration.churches.index'));
        $response->assertSessionHas('status', 'ID de igreja inválido.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testDeleteProductsFailsForMissingChurch(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('findChurch')
                    ->once()
                    ->with(999)
                    ->andReturn(null);
            },
        );

        $response = $this->post(route('migration.churches.delete-products'), [
            'comum_id' => 999,
        ]);

        $response->assertRedirect(route('migration.churches.index'));
        $response->assertSessionHas('status', 'Igreja não encontrada.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testDeleteProductsFailsWhenServiceThrows(): void
    {
        $this->mock(
            LegacyChurchManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('findChurch')
                    ->once()
                    ->with(7)
                    ->andReturn($this->makeChurch());
                $mock->shouldReceive('deleteProducts')
                    ->once()
                    ->andThrow(new RuntimeException('Erro interno'));
            },
        );

        $response = $this->post(route('migration.churches.delete-products'), [
            'comum_id' => 7,
        ]);

        $response->assertRedirect(route('migration.churches.index'));
        $response->assertSessionHas('status', 'Erro ao excluir produtos: Erro interno');
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Compat route: GET /churches/edit ───────────────────────────────────

    public function testCompatChurchesEditRedirectsToEditWithValidId(): void
    {
        $response = $this->get(route('migration.compat.churches.edit', ['id' => 7]));

        $response->assertRedirect(route('migration.churches.edit', ['church' => 7]));
    }

    public function testCompatChurchesEditFailsForMissingId(): void
    {
        $response = $this->get(route('migration.compat.churches.edit'));

        $response->assertRedirect(route('migration.churches.index'));
        $response->assertSessionHas('status', 'Igreja não informada para edição.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testCompatChurchesEditFailsForInvalidId(): void
    {
        $response = $this->get(route('migration.compat.churches.edit', ['id' => -1]));

        $response->assertRedirect(route('migration.churches.index'));
        $response->assertSessionHas('status', 'Igreja não informada para edição.');
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Authentication enforcement ─────────────────────────────────────────

    public function testIndexRequiresAuthWhenEnforced(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 9,
                'nome' => 'Maria Silva',
                'email' => 'MARIA@EXEMPLO.COM',
                'comum_id' => 7,
                'administracao_id' => 4,
                'is_admin' => false,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn([
                'id' => 7,
                'codigo' => '12-3456',
                'descricao' => 'Central Cuiabá',
            ]);
            $mock->shouldReceive('availableChurches')->andReturn(collect([
                (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
            ]));
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });

        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('can')->with('churches.view')->andReturn(true);
            $mock->shouldReceive('currentPermissions')->andReturn([
                'churches.view' => true,
            ]);
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });

        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id' => 7,
                                'codigo' => '12-3456',
                                'descricao' => 'Central Cuiabá',
                                'cidade' => 'Cuiaba',
                                'estado' => 'MT',
                                'setor' => 'Centro',
                                'active_products_count' => 18,
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/churches'],
                    );
                }

                public function countAll(): int
                {
                    return 5;
                }

                public function administrationOptions(): Collection
                {
                    return collect();
                }
            },
        );

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get(route('migration.churches.index'));

        $response->assertOk();
    }

    public function testIndexRedirectsToLoginWhenUnauthenticated(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.churches.index'));

        $response->assertRedirect(route('migration.login'));
    }

    public function testEditRequiresAdminWhenEnforced(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 9,
                'nome' => 'Maria Silva',
                'email' => 'MARIA@EXEMPLO.COM',
                'comum_id' => 7,
                'administracao_id' => 4,
                'is_admin' => true,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn([
                'id' => 7,
                'codigo' => '12-3456',
                'descricao' => 'Central Cuiabá',
            ]);
            $mock->shouldReceive('availableChurches')->andReturn(collect([
                (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
            ]));
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });

        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('can')->with('churches.edit')->andReturn(true);
            $mock->shouldReceive('currentPermissions')->andReturn([
                'churches.edit' => true,
            ]);
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });

        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(items: collect(), total: 0, perPage: 20, currentPage: 1, options: ['path' => '/churches']);
                }

                public function countAll(): int
                {
                    return 0;
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'descricao' => 'Administração Central'],
                    ]);
                }
            },
        );

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ])->get(route('migration.churches.edit', ['church' => 7]));

        $response->assertOk();
    }

    public function testEditRedirectsToLoginWhenUnauthenticated(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.churches.edit', ['church' => 7]));

        $response->assertRedirect(route('migration.login'));
    }

    public function testEditRedirectsToDashboardWhenNotAdmin(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get(route('migration.churches.edit', ['church' => 7]));

        $response->assertRedirect(route('migration.dashboard'));
        $response->assertSessionHas('status', 'Seu perfil não tem permissão para executar esta ação.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testDeleteProductsRedirectsToLoginWhenUnauthenticated(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->post(route('migration.churches.delete-products'));

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeChurch(string $description = 'Central Cuiabá'): Comum
    {
        $church = new Comum();
        $church->forceFill([
            'id' => 7,
            'codigo' => '12-3456',
            'descricao' => $description,
            'cnpj' => '12.345.678/0001-90',
            'estado' => 'MT',
            'cidade' => 'Cuiaba',
            'estado_administracao' => 'SP',
            'cidade_administracao' => 'Campinas',
            'setor' => 'Centro',
        ]);
        $church->exists = true;

        return $church;
    }
}

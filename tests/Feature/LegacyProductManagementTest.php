<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyProductBrowserServiceInterface;
use App\Contracts\LegacyProductManagementServiceInterface;
use App\Contracts\LegacyProductUtilityServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\ProductFilters;
use App\DTO\ProductVerificationItemData;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Produto;
use App\Models\Legacy\TipoBem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyProductManagementTest extends TestCase
{
    private Produto $boundProduct;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });

        $this->boundProduct = $this->makeProduct();
        $this->app['router']->bind('product', fn (): Produto => $this->boundProduct);
        $this->app->instance(
            LegacyProductBrowserServiceInterface::class,
            new class implements LegacyProductBrowserServiceInterface
            {
                public function paginate(ProductFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect(),
                        total: 0,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/products']
                    );
                }

                public function churchOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                    ]);
                }

                public function dependencyOptions(?int $comumId): Collection
                {
                    return collect([
                        (object) ['id' => 2, 'comum_id' => 7, 'descricao' => 'SALAO'],
                        (object) ['id' => 3, 'comum_id' => 7, 'descricao' => 'SECRETARIA'],
                    ]);
                }

                public function assetTypeOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'codigo' => '4', 'descricao' => 'CADEIRA/CADEIRA GIRATORIA'],
                        (object) ['id' => 7, 'codigo' => '7', 'descricao' => 'MESA/BANCADA'],
                    ]);
                }

                public function statusOptions(): array
                {
                    return [
                        'com_nota' => 'Com nota fiscal',
                    ];
                }
            }
        );
    }

    public function testCreatePageRendersForm(): void
    {
        $response = $this->get(route('migration.products.create', ['comum_id' => 7]));

        $response->assertOk();
        $response->assertSee('Novo produto.');
        $response->assertSee('Salvar produto');
    }

    public function testVerificationPageRendersChecklist(): void
    {
        $this->app->instance(
            LegacyProductBrowserServiceInterface::class,
            new class implements LegacyProductBrowserServiceInterface
            {
                public function paginate(ProductFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id_produto' => 19,
                                'comum_id' => 7,
                                'codigo' => 'A-101',
                                'bem' => 'CADEIRA',
                                'complemento' => 'METALICA',
                                'imprimir_etiqueta' => 1,
                                'observacao' => 'RISCO NO ENCOSTO',
                                'checado' => 1,
                                'tipoBem' => (object) ['codigo' => '4', 'descricao' => 'CADEIRA'],
                                'comum' => (object) ['codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                                'dependencia' => (object) ['descricao' => 'SALAO'],
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/products/verificacao']
                    );
                }

                public function churchOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                    ]);
                }

                public function dependencyOptions(?int $comumId): Collection
                {
                    return collect([
                        (object) ['id' => 2, 'descricao' => 'SALAO'],
                    ]);
                }

                public function assetTypeOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'codigo' => '4', 'descricao' => 'CADEIRA'],
                    ]);
                }

                public function statusOptions(): array
                {
                    return ['com_nota' => 'Com nota fiscal'];
                }
            }
        );

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get(route('migration.products.verification', ['comum_id' => 7]));

        $response->assertOk();
        $response->assertSee('Verificação de produtos para impressão.');
        $response->assertSee('Busca geral');
        $response->assertSee('Verificado');
        $response->assertSee('Identificação');
        $response->assertSee('A-101');
    }

    public function testVerificationPageMarksCheckedWhenLabelIsSelected(): void
    {
        $this->app->instance(
            LegacyProductBrowserServiceInterface::class,
            new class implements LegacyProductBrowserServiceInterface
            {
                public function paginate(ProductFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id_produto' => 19,
                                'comum_id' => 7,
                                'codigo' => 'A-101',
                                'bem' => 'CADEIRA',
                                'complemento' => 'METALICA',
                                'imprimir_etiqueta' => 1,
                                'observacao' => 'RISCO NO ENCOSTO',
                                'checado' => 0,
                                'tipoBem' => (object) ['codigo' => '4', 'descricao' => 'CADEIRA'],
                                'comum' => (object) ['codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                                'dependencia' => (object) ['descricao' => 'SALAO'],
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/products/verificacao']
                    );
                }

                public function churchOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                    ]);
                }

                public function dependencyOptions(?int $comumId): Collection
                {
                    return collect([
                        (object) ['id' => 2, 'descricao' => 'SALAO'],
                    ]);
                }

                public function assetTypeOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'codigo' => '4', 'descricao' => 'CADEIRA'],
                    ]);
                }

                public function statusOptions(): array
                {
                    return ['com_nota' => 'Com nota fiscal'];
                }
            }
        );

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get(route('migration.products.verification', ['comum_id' => 7]));

        $response->assertOk();
        $this->assertMatchesRegularExpression('/name="verificado"[^>]*checked/i', $response->getContent());
        $this->assertMatchesRegularExpression('/name="imprimir_etiqueta"[^>]*checked/i', $response->getContent());
    }

    public function testVerificationPageMarksCheckedWhenObservationIsFilled(): void
    {
        $this->app->instance(
            LegacyProductBrowserServiceInterface::class,
            new class implements LegacyProductBrowserServiceInterface
            {
                public function paginate(ProductFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id_produto' => 19,
                                'comum_id' => 7,
                                'codigo' => 'A-101',
                                'bem' => 'CADEIRA',
                                'complemento' => 'METALICA',
                                'imprimir_etiqueta' => 0,
                                'observacao' => 'AJUSTE DE ETIQUETA',
                                'checado' => 0,
                                'tipoBem' => (object) ['codigo' => '4', 'descricao' => 'CADEIRA'],
                                'comum' => (object) ['codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                                'dependencia' => (object) ['descricao' => 'SALAO'],
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/products/verificacao']
                    );
                }

                public function churchOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                    ]);
                }

                public function dependencyOptions(?int $comumId): Collection
                {
                    return collect([
                        (object) ['id' => 2, 'descricao' => 'SALAO'],
                    ]);
                }

                public function assetTypeOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'codigo' => '4', 'descricao' => 'CADEIRA'],
                    ]);
                }

                public function statusOptions(): array
                {
                    return ['com_nota' => 'Com nota fiscal'];
                }
            }
        );

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get(route('migration.products.verification', ['comum_id' => 7]));

        $response->assertOk();
        $this->assertMatchesRegularExpression('/name="verificado"[^>]*checked/i', $response->getContent());
    }

    public function testStoreCreatesProducts(): void
    {
        $this->mock(
            LegacyProductManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('createMany')
                    ->once()
                    ->withArgs(fn ($dto): bool =>
                        $dto->churchId === 7
                        && $dto->assetTypeId === 4
                        && $dto->itemName === 'CADEIRA'
                        && $dto->dependencyId === 2
                        && $dto->multiplier === 2
                        && $dto->printReport141 === true
                    )
                    ->andReturn(2);
            }
        );

        $response = $this->post(route('migration.products.store'), [
            'comum_id' => 7,
            'codigo' => 'ABC-1',
            'id_tipo_ben' => 4,
            'tipo_ben' => 'CADEIRA',
            'complemento' => 'Metalica',
            'id_dependencia' => 2,
            'multiplicador' => 2,
            'imprimir_14_1' => 1,
            'condicao_14_1' => '2',
        ]);

        $response->assertRedirect(route('migration.products.index', ['comum_id' => 7]));
        $response->assertSessionHas('status', '2 produtos cadastrados com sucesso.');
    }

    public function testVerificationStoreUpdatesChecklist(): void
    {
        $this->mock(
            LegacyProductUtilityServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('saveVerificationChecklist')
                    ->once()
                    ->withArgs(static function (int $churchId, array $items): bool {
                        return $churchId === 7
                            && count($items) === 2
                            && $items[0] instanceof ProductVerificationItemData
                            && $items[0]->productId === 19
                            && $items[0]->printLabel === true
                            && $items[0]->observation === 'risco no encosto'
                            && $items[1] instanceof ProductVerificationItemData
                            && $items[1]->productId === 20
                            && $items[1]->printLabel === false;
                    })
                    ->andReturn(2);
            }
        );

        $response = $this->post(route('migration.products.verification.store'), [
            'comum_id' => 7,
            'pagina' => 2,
            'busca' => 'CADEIRA',
            'dependencia_id' => 2,
            'tipo_bem_id' => 4,
            'status' => 'com_nota',
            'somente_novos' => 0,
            'itens' => [
                [
                    'produto_id' => 19,
                    'imprimir_etiqueta' => 1,
                    'observacao' => 'risco no encosto',
                ],
                [
                    'produto_id' => 20,
                    'imprimir_etiqueta' => 0,
                    'observacao' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('migration.products.verification', [
            'comum_id' => 7,
            'pagina' => 2,
            'busca' => 'CADEIRA',
            'dependencia_id' => 2,
            'tipo_bem_id' => 4,
            'status' => 'com_nota',
        ]));
        $response->assertSessionHas('status', 'Checklist salvo com sucesso.');
    }

    public function testVerificationAutosaveUpdatesSingleRow(): void
    {
        $productBefore = $this->makeProduct([
            'checado' => 0,
            'imprimir_etiqueta' => 0,
            'observacao' => '',
        ]);
        $productAfter = $this->makeProduct([
            'checado' => 1,
            'imprimir_etiqueta' => 0,
            'observacao' => 'RISCO NO ENCOSTO',
        ]);

        $this->mock(
            LegacyProductUtilityServiceInterface::class,
            function (MockInterface $mock) use ($productBefore, $productAfter): void {
                $mock->shouldReceive('findForChurch')
                    ->twice()
                    ->with(19, 7)
                    ->andReturn($productBefore, $productAfter);

                $mock->shouldReceive('saveVerificationChecklist')
                    ->once()
                    ->withArgs(static function (int $churchId, array $items): bool {
                        return $churchId === 7
                            && count($items) === 1
                            && $items[0] instanceof ProductVerificationItemData
                            && $items[0]->productId === 19
                            && $items[0]->verified === true
                            && $items[0]->printLabel === false
                            && $items[0]->observation === 'risco no encosto';
                    })
                    ->andReturn(1);
            }
        );

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->postJson(route('migration.products.verification.sync'), [
            'comum_id' => 7,
            'produto_id' => 19,
            'verificado' => 1,
            'imprimir_etiqueta' => 0,
            'observacao' => 'risco no encosto',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Produto atualizado automaticamente.',
            'product_id' => 19,
            'checked' => true,
            'print_label' => false,
        ]);
    }

    public function testVerificationObservationMarksProductAsChecked(): void
    {
        $productBefore = $this->makeProduct([
            'checado' => 0,
            'imprimir_etiqueta' => 0,
            'observacao' => '',
        ]);
        $productAfter = $this->makeProduct([
            'checado' => 1,
            'imprimir_etiqueta' => 0,
            'observacao' => 'AJUSTE DE ETIQUETA',
        ]);

        $this->mock(
            LegacyProductUtilityServiceInterface::class,
            function (MockInterface $mock) use ($productBefore, $productAfter): void {
                $mock->shouldReceive('findForChurch')
                    ->twice()
                    ->with(19, 7)
                    ->andReturn($productBefore, $productAfter);

                $mock->shouldReceive('saveVerificationChecklist')
                    ->once()
                    ->withArgs(static function (int $churchId, array $items): bool {
                        return $churchId === 7
                            && count($items) === 1
                            && $items[0] instanceof ProductVerificationItemData
                            && $items[0]->productId === 19
                            && $items[0]->verified === false
                            && $items[0]->printLabel === false
                            && $items[0]->observation === 'ajuste de etiqueta';
                    })
                    ->andReturn(1);
            }
        );

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->postJson(route('migration.products.verification.sync'), [
            'comum_id' => 7,
            'produto_id' => 19,
            'verificado' => 0,
            'imprimir_etiqueta' => 0,
            'observacao' => 'ajuste de etiqueta',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Produto atualizado automaticamente.',
            'product_id' => 19,
            'checked' => true,
            'print_label' => false,
        ]);
    }

    public function testLegacyCreateAliasStoresProducts(): void
    {
        $this->mock(
            LegacyProductManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('createMany')
                    ->once()
                    ->withArgs(fn ($dto): bool =>
                        $dto->churchId === 7
                        && $dto->assetTypeId === 4
                        && $dto->itemName === 'CADEIRA'
                        && $dto->dependencyId === 2
                    )
                    ->andReturn(1);
            }
        );

        $response = $this->post('/products/create', [
            'comum_id' => 7,
            'id_tipo_ben' => 4,
            'tipo_ben' => 'CADEIRA',
            'complemento' => 'Metalica',
            'id_dependencia' => 2,
            'multiplicador' => 1,
            'condicao_14_1' => '2',
        ]);

        $response->assertRedirect(route('migration.products.index', ['comum_id' => 7]));
        $response->assertSessionHas('status', 'Produto cadastrado com sucesso.');
    }

    public function testEditPageRendersForm(): void
    {
        $response = $this->get(route('migration.products.edit', ['product' => 19]));

        $response->assertOk();
        $response->assertSee('Editar produto.');
        $response->assertSee('Valores atuais');
        $response->assertSee('Novos valores');
        $response->assertSee('Marca atual');
        $response->assertSee('Nova marca');
        $response->assertSee('Salvar alterações');
    }

    public function testUpdateChangesProduct(): void
    {
        $this->mock(
            LegacyProductManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(fn (Produto $product, $dto): bool =>
                        $product->id_produto === 19
                        && $dto->editedAssetTypeId === 7
                        && $dto->editedItemName === 'MESA'
                        && $dto->editedDependencyId === 3
                        && $dto->editedBrand === 'Marca X'
                        && $dto->printReport141 === true
                    )
                    ->andReturn($this->makeProduct());
            }
        );

        $response = $this->put(route('migration.products.update', ['product' => 19]), [
            'novo_tipo_bem_id' => 7,
            'novo_bem' => 'MESA',
            'novo_complemento' => 'Madeira',
            'novo_marca' => 'Marca X',
            'nova_dependencia_id' => 3,
            'imprimir_14_1' => 1,
            'condicao_14_1' => '2',
        ]);

        $response->assertRedirect(route('migration.products.index', ['comum_id' => 7]));
        $response->assertSessionHas('status', 'Produto atualizado com sucesso.');
    }

    public function testUpdateReturnsToVerificationWhenRequested(): void
    {
        $this->mock(
            LegacyProductManagementServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->andReturn($this->makeProduct());
            }
        );

        $response = $this->put(route('migration.products.update', ['product' => 19]), [
            'novo_tipo_bem_id' => 7,
            'novo_bem' => 'MESA',
            'novo_complemento' => 'Madeira',
            'novo_marca' => 'Marca X',
            'nova_dependencia_id' => 3,
            'imprimir_14_1' => 1,
            'condicao_14_1' => '2',
            'return_url' => route('migration.products.verification', [
                'comum_id' => 7,
                'busca' => 'cadeira',
            ]),
        ]);

        $response->assertRedirect(route('migration.products.verification', [
            'comum_id' => 7,
            'busca' => 'cadeira',
        ]));
        $response->assertSessionHas('status', 'Produto atualizado com sucesso.');
    }

    public function testFilterPinPreferenceIsSavedPerUser(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('storeFilterPinState')
                ->once()
                ->with('/products/verificacao', 0, true);
        });

        $response = $this->postJson(route('migration.session.filters-pin'), [
            'scope' => '/products/verificacao',
            'index' => 0,
            'pinned' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function testIndexAndVerificationShowEditButtonWithoutEditPermission(): void
    {
        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([
                'products.view' => true,
                'products.edit' => false,
            ]);
        });

        $this->app->instance(
            LegacyProductBrowserServiceInterface::class,
            new class implements LegacyProductBrowserServiceInterface
            {
                public function paginate(ProductFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id_produto' => 19,
                                'codigo' => 'A-101',
                                'bem' => 'CADEIRA',
                                'complemento' => 'METALICA',
                                'dependencia' => (object) ['descricao' => 'SALAO'],
                                'tipoBem' => (object) ['codigo' => '4', 'descricao' => 'CADEIRA'],
                                'comum' => (object) ['codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                                'imprimir_14_1' => 0,
                                'nota_numero' => null,
                                'novo' => 0,
                                'editado' => 0,
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/products']
                    );
                }

                public function churchOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                    ]);
                }

                public function dependencyOptions(?int $comumId): Collection
                {
                    return collect([
                        (object) ['id' => 2, 'comum_id' => 7, 'descricao' => 'SALAO'],
                    ]);
                }

                public function assetTypeOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 4, 'codigo' => '4', 'descricao' => 'CADEIRA'],
                    ]);
                }

                public function statusOptions(): array
                {
                    return ['com_nota' => 'Com nota fiscal'];
                }
            }
        );

        $response = $this->get(route('migration.products.index', ['comum_id' => 7]));

        $response->assertOk();
        $response->assertSee('Editar');

        $verificationResponse = $this->get(route('migration.products.verification', ['comum_id' => 7]));

        $verificationResponse->assertOk();
        $verificationResponse->assertSee('Editar cadastro');
    }

    public function testUpdateMarksProductAsVerifiedAndLabeled(): void
    {
        $product = $this->makeProduct();

        $this->mock(
            LegacyProductManagementServiceInterface::class,
            function (MockInterface $mock) use ($product): void {
                $mock->shouldReceive('update')
                    ->once()
                    ->withArgs(fn (Produto $boundProduct, $dto): bool =>
                        $boundProduct->id_produto === 19
                        && $dto->editedAssetTypeId === 7
                        && $dto->editedItemName === 'MESA'
                        && $dto->editedDependencyId === 3
                    )
                    ->andReturn($product);
            }
        );

        $response = $this->put(route('migration.products.update', ['product' => 19]), [
            'novo_tipo_bem_id' => 7,
            'novo_bem' => 'MESA',
            'novo_complemento' => 'Madeira',
            'novo_marca' => 'Marca X',
            'nova_dependencia_id' => 3,
            'observacao' => 'ajuste de cadastro',
            'verificado' => 0,
            'imprimir_etiqueta' => 0,
            'imprimir_14_1' => 0,
            'condicao_14_1' => '2',
        ]);

        $response->assertRedirect(route('migration.products.index', ['comum_id' => 7]));
        $response->assertSessionHas('status', 'Produto atualizado com sucesso.');
    }

    public function testDeleteProductRouteIsNotAvailable(): void
    {
        $response = $this->delete('/products/19');

        $response->assertMethodNotAllowed();
    }

    public function testStoreRejectsMissingFields(): void
    {
        $response = $this->from(route('migration.products.create'))
            ->post(route('migration.products.store'), [
                'comum_id' => '',
                'id_tipo_ben' => '',
                'tipo_ben' => '',
                'complemento' => '',
                'id_dependencia' => '',
                'multiplicador' => 0,
                'condicao_14_1' => '3',
                'nota_numero' => '',
                'nota_data' => '',
                'nota_valor' => '',
                'nota_fornecedor' => '',
            ]);

        $response->assertRedirect(route('migration.products.create'));
        $response->assertSessionHasErrors([
            'comum_id',
            'id_tipo_ben',
            'tipo_ben',
            'complemento',
            'id_dependencia',
            'multiplicador',
            'nota_numero',
            'nota_data',
            'nota_valor',
            'nota_fornecedor',
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeProduct(array $overrides = []): Produto
    {
        $product = new class extends Produto {
            public function loadMissing($relations = null): static
            {
                return $this;
            }
        };
        $product->forceFill(array_merge([
            'id_produto' => 19,
            'comum_id' => 7,
            'codigo' => 'A-101',
            'tipo_bem_id' => 4,
            'bem' => 'CADEIRA',
            'complemento' => 'METALICA',
            'dependencia_id' => 2,
            'checado' => 0,
            'imprimir_etiqueta' => 0,
            'observacao' => '',
            'imprimir_14_1' => 1,
            'condicao_14_1' => '2',
        ], $overrides));
        $product->exists = true;
        $product->setRelation('comum', new Comum([
            'id' => 7,
            'codigo' => '12-3456',
            'descricao' => 'Central Cuiabá',
        ]));
        $product->setRelation('dependencia', new Dependencia([
            'id' => 2,
            'descricao' => 'SALAO',
        ]));
        $product->setRelation('tipoBem', new TipoBem([
            'id' => 4,
            'codigo' => '4',
            'descricao' => 'CADEIRA/CADEIRA GIRATORIA',
        ]));

        return $product;
    }
}

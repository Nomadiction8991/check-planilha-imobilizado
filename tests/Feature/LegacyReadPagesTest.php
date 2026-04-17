<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAssetTypeBrowserServiceInterface;
use App\Contracts\LegacyChurchBrowserServiceInterface;
use App\Contracts\LegacyDepartmentBrowserServiceInterface;
use App\Contracts\LegacyProductBrowserServiceInterface;
use App\Contracts\LegacyUserBrowserServiceInterface;
use App\DTO\AssetTypeFilters;
use App\DTO\ChurchFilters;
use App\DTO\DepartmentFilters;
use App\DTO\ProductFilters;
use App\DTO\UserFilters;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class LegacyReadPagesTest extends TestCase
{
    public function testChurchesPageRendersPaginatedLegacyChurches(): void
    {
        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
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
                        options: ['path' => '/churches']
                    );
                }

                public function countAll(): int
                {
                    return 1;
                }
            }
        );

        $response = $this->get('/churches');

        $response->assertOk();
        $response->assertSee('Igrejas cadastradas no sistema.');
        $response->assertSee('Central Cuiabá');
        $response->assertSee('18');
    }

    public function testProductsPageRendersPaginatedLegacyProducts(): void
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
                                'imprimir_14_1' => 1,
                                'nota_numero' => 55,
                                'novo' => 1,
                                'editado' => 0,
                                'tipoBem' => (object) ['codigo' => '4', 'descricao' => 'CADEIRA'],
                                'comum' => (object) ['codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                                'dependencia' => (object) ['descricao' => 'SALAO'],
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

        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSee('Produtos ativos com filtro e manutenção.');
        $response->assertSee('Busca geral');
        $response->assertSee('CADEIRA METALICA');
        $response->assertSee('Central Cuiabá');
        $response->assertSee('Nota fiscal');
    }

    public function testProductsNewFilterRendersLegacyProducts(): void
    {
        $this->app->instance(
            LegacyProductBrowserServiceInterface::class,
            new class implements LegacyProductBrowserServiceInterface
            {
                public function paginate(ProductFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    if ($filters->onlyNew !== true) {
                        throw new \RuntimeException('Filtro de novos não aplicado.');
                    }

                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id_produto' => 22,
                                'comum_id' => 7,
                                'codigo' => 'N-22',
                                'bem' => 'MESA',
                                'complemento' => 'DOBRAVEL',
                                'imprimir_14_1' => 0,
                                'nota_numero' => null,
                                'novo' => 1,
                                'editado' => 0,
                                'tipoBem' => (object) ['codigo' => '7', 'descricao' => 'MESA'],
                                'comum' => (object) ['codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                                'dependencia' => (object) ['descricao' => 'ANEXO'],
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
                        (object) ['id' => 3, 'descricao' => 'ANEXO'],
                    ]);
                }

                public function assetTypeOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '7', 'descricao' => 'MESA'],
                    ]);
                }

                public function statusOptions(): array
                {
                    return ['novos' => 'Somente novos'];
                }
            }
        );

        $response = $this->get('/products?status=novos&somente_novos=1');

        $response->assertOk();
        $response->assertSee('Produtos novos filtrados.');
        $response->assertSee('Busca geral');
        $response->assertSee('MESA DOBRAVEL');
        $response->assertSee('Novo');
    }

    public function testDepartmentsPageRendersPaginatedLegacyDepartments(): void
    {
        $this->app->instance(
            LegacyDepartmentBrowserServiceInterface::class,
            new class implements LegacyDepartmentBrowserServiceInterface
            {
                public function paginate(DepartmentFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id' => 2,
                                'comum_id' => 7,
                                'descricao' => 'SALAO',
                                'active_products_count' => 12,
                                'comum' => (object) ['codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/departments']
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
                    return 1;
                }
            }
        );

        $response = $this->get('/departments');

        $response->assertOk();
        $response->assertSee('Dependências disponíveis no sistema.');
        $response->assertSee('SALAO');
        $response->assertSee('Central Cuiabá');
    }

    public function testAssetTypesPageRendersPaginatedLegacyAssetTypes(): void
    {
        $this->app->instance(
            LegacyAssetTypeBrowserServiceInterface::class,
            new class implements LegacyAssetTypeBrowserServiceInterface
            {
                public function paginate(AssetTypeFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id' => 4,
                                'codigo' => 4,
                                'descricao' => 'CADEIRA',
                                'active_products_count' => 24,
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/asset-types']
                    );
                }

                public function countAll(): int
                {
                    return 1;
                }
            }
        );

        $response = $this->get('/asset-types');

        $response->assertOk();
        $response->assertSee('Tipos de bem disponíveis no sistema.');
        $response->assertSee('CADEIRA');
        $response->assertSee('24');
    }

    public function testUsersPageRendersPaginatedLegacyUsers(): void
    {
        $this->app->instance(
            LegacyUserBrowserServiceInterface::class,
            new class implements LegacyUserBrowserServiceInterface
            {
                public function paginate(UserFilters $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect([
                            (object) [
                                'id' => 9,
                                'nome' => 'Maria Silva',
                                'email' => 'MARIA@EXEMPLO.COM',
                                'ativo' => 1,
                                'administracao' => (object) ['id' => 7, 'descricao' => 'Administração Central'],
                            ],
                        ]),
                        total: 1,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/users']
                    );
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'descricao' => 'Administração Central'],
                    ]);
                }

                public function statusOptions(): array
                {
                    return [
                        '1' => 'Ativos',
                        '0' => 'Inativos',
                    ];
                }

                public function countAll(): int
                {
                    return 1;
                }
            }
        );

        $response = $this->get('/users');

        $response->assertOk();
        $response->assertSee('Usuários vinculados a administrações.');
        $response->assertSee('Administração Central');
        $response->assertSee('Maria Silva');
        $response->assertSee('MARIA@EXEMPLO.COM');
        $response->assertSee('Ativo');
    }
}

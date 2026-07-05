<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyProductBrowserServiceInterface;
use App\Contracts\LegacyProductManagementServiceInterface;
use App\Contracts\LegacyProductUtilityServiceInterface;
use App\DTO\ProductFilters;
use App\Models\Legacy\Produto;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyProductControllerTest extends TestCase
{
    private LegacyProductBrowserServiceInterface&MockInterface $products;

    private LegacyProductManagementServiceInterface&MockInterface $productManager;

    private LegacyProductUtilityServiceInterface&MockInterface $productUtility;

    private Produto $boundProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->products = $this->mock(LegacyProductBrowserServiceInterface::class);
        $this->productManager = $this->mock(LegacyProductManagementServiceInterface::class);
        $this->productUtility = $this->mock(LegacyProductUtilityServiceInterface::class);

        $this->boundProduct = $this->makeProduct(
            id: 42,
            comumId: 7,
            code: 'PROD-001',
            description: 'CADEIRA ESCRITÓRIO',
        );

        $this->app['router']->bind('product', fn (): Produto => $this->boundProduct);
    }

    // ─── Index ───────────────────────────────────────────────────────────────

    public function testIndexReturnsPaginatedProducts(): void
    {
        $this->products
            ->shouldReceive('paginate')
            ->once()
            ->withArgs(fn (ProductFilters $filters): bool => true)
            ->andReturn(new LengthAwarePaginator(
                items: collect(),
                total: 0,
                perPage: 20,
                currentPage: 1,
                options: ['path' => '/products'],
            ));

        $this->products
            ->shouldReceive('churchOptions')
            ->once()
            ->andReturn(collect());

        $this->products
            ->shouldReceive('dependencyOptions')
            ->once()
            ->withSomeOfArgs()
            ->andReturn(collect());

        $this->products
            ->shouldReceive('assetTypeOptions')
            ->once()
            ->andReturn(collect());

        $this->products
            ->shouldReceive('statusOptions')
            ->once()
            ->andReturn([]);

        $response = $this->get(route('migration.products.index'));

        $response->assertOk();
    }

    public function testIndexRedirectsGuestsToLogin(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.products.index'));

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Edit ────────────────────────────────────────────────────────────────

    public function testEditShowsProductForm(): void
    {
        $this->products
            ->shouldReceive('assetTypeOptions')
            ->once()
            ->andReturn(collect([
                (object) ['id' => 99, 'codigo' => '99', 'descricao' => 'MÓVEIS'],
            ]));

        $this->products
            ->shouldReceive('dependencyOptions')
            ->once()
            ->with(7)
            ->andReturn(collect([
                (object) ['id' => 1, 'comum_id' => 7, 'descricao' => 'SALÃO'],
            ]));

        $response = $this->get(route('migration.products.edit', ['product' => $this->boundProduct->id_produto]));

        $response->assertOk();
        $response->assertSee('Editar Produto');
        $response->assertSee('PROD-001');
        $response->assertSee('Cancelar');
    }

    public function testEditRedirectsGuestsToLogin(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.products.edit', ['product' => $this->boundProduct->id_produto]));

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeProduct(
        int $id,
        int $comumId,
        string $code,
        string $description,
    ): Produto {
        $product = new Produto();
        $product->forceFill([
            'id_produto' => $id,
            'comum_id' => $comumId,
            'codigo' => $code,
            'bem' => $description,
            'tipo_bem_id' => 99,
            'dependencia_id' => 0,
            'novo' => 0,
            'importado' => 0,
            'checado' => 0,
            'editado' => 0,
            'imprimir_etiqueta' => 0,
            'imprimir_14_1' => 0,
            'observacao' => '',
            'ativo' => 1,
        ]);
        $product->exists = true;

        // Pre-load relations so loadMissing() in the controller skips DB queries.
        $product->setRelation('comum', (object) [
            'id' => $comumId,
            'codigo' => '12-3456',
            'descricao' => 'Igreja Central',
        ]);
        $product->setRelation('dependencia', (object) [
            'id' => 0,
            'descricao' => '',
        ]);
        $product->setRelation('tipoBem', (object) [
            'id' => 99,
            'codigo' => '99',
            'descricao' => 'MÓVEIS',
        ]);

        return $product;
    }
}

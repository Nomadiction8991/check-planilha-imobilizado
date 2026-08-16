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
use RuntimeException;
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

    // ─── Create ──────────────────────────────────────────────────────────────

    public function testCreateRendersForm(): void
    {
        $this->products
            ->shouldReceive('churchOptions')
            ->once()
            ->andReturn(collect([
                (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
            ]));

        $this->products
            ->shouldReceive('dependencyOptions')
            ->once()
            ->with(null)
            ->andReturn(collect([
                (object) ['id' => 1, 'comum_id' => 7, 'descricao' => 'SALÃO'],
                (object) ['id' => 2, 'comum_id' => 8, 'descricao' => 'SECRETARIA'],
            ]));

        $this->products
            ->shouldReceive('assetTypeOptions')
            ->once()
            ->andReturn(collect([
                (object) ['id' => 99, 'codigo' => '99', 'descricao' => 'MÓVEIS'],
            ]));

        $response = $this->get(route('migration.products.create'));

        $response->assertOk();
        $response->assertSee('Novo produto.');
        $response->assertSee('Salvar produto');
        $response->assertSee('Central Cuiabá');
        $response->assertSee('MÓVEIS');
    }

    // ─── Store ───────────────────────────────────────────────────────────────

    public function testStoreCreatesProduct(): void
    {
        $this->productManager
            ->shouldReceive('createMany')
            ->once()
            ->andReturn(1);

        $response = $this->post(route('migration.products.store'), [
            'comum_id' => '7',
            'id_tipo_ben' => '99',
            'tipo_ben' => 'CADEIRA ESCRITÓRIO',
            'id_dependencia' => '1',
            'multiplicador' => '1',
        ]);

        $response->assertRedirect(route('migration.products.index', ['comum_id' => 7]));
        $response->assertSessionHas('status', 'Produto cadastrado com sucesso.');
        $response->assertSessionHas('status_type', 'success');
    }

    public function testStoreShowsPluralMessageWhenMultiple(): void
    {
        $this->productManager
            ->shouldReceive('createMany')
            ->once()
            ->andReturn(3);

        $response = $this->post(route('migration.products.store'), [
            'comum_id' => '7',
            'id_tipo_ben' => '99',
            'tipo_ben' => 'CADEIRA ESCRITÓRIO',
            'id_dependencia' => '1',
            'multiplicador' => '3',
        ]);

        $response->assertRedirect(route('migration.products.index', ['comum_id' => 7]));
        $response->assertSessionHas('status', '3 produtos cadastrados com sucesso.');
        $response->assertSessionHas('status_type', 'success');
    }

    public function testStoreRejectsMissingRequiredFields(): void
    {
        $response = $this->from(route('migration.products.create'))
            ->post(route('migration.products.store'), [
                'comum_id' => '0',
                'id_tipo_ben' => '0',
                'tipo_ben' => '',
                'id_dependencia' => '0',
                'multiplicador' => '0',
            ]);

        $response->assertRedirect(route('migration.products.create'));
        $response->assertSessionHasErrors(['comum_id', 'id_tipo_ben', 'tipo_ben', 'id_dependencia', 'multiplicador']);
    }

    public function testStoreHandlesRuntimeException(): void
    {
        $this->productManager
            ->shouldReceive('createMany')
            ->once()
            ->andThrow(new RuntimeException('Erro ao cadastrar produto.'));

        $response = $this->from(route('migration.products.create'))
            ->post(route('migration.products.store'), [
                'comum_id' => '7',
                'id_tipo_ben' => '99',
                'tipo_ben' => 'CADEIRA ESCRITÓRIO',
                'id_dependencia' => '1',
                'multiplicador' => '1',
            ]);

        $response->assertRedirect(route('migration.products.create', ['comum_id' => 7]));
        $response->assertSessionHas('status', 'Erro ao cadastrar produto.');
        $response->assertSessionHas('status_type', 'error');
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
        $response->assertSee('Editar produto.');
        $response->assertSee('PROD-001');
        $response->assertSee('99 - MÓVEIS');
    }

    public function testEditRedirectsGuestsToLogin(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.products.edit', ['product' => $this->boundProduct->id_produto]));

        $response->assertRedirect(route('migration.login'));
    }

    // ─── Update ──────────────────────────────────────────────────────────────

    public function testUpdateChangesProduct(): void
    {
        $this->productManager
            ->shouldReceive('update')
            ->once()
            ->withArgs(
                fn (Produto $product, $dto): bool =>
                    $product->id_produto === 42
                    && $dto->editedAssetTypeId === 99
                    && $dto->editedItemName === 'MESA ESCRITÓRIO'
                    && $dto->editedDependencyId === 1
            )
            ->andReturn($this->boundProduct);

        $response = $this->put(route('migration.products.update', ['product' => 42]), [
            'novo_tipo_bem_id' => '99',
            'novo_bem' => 'MESA ESCRITÓRIO',
            'nova_dependencia_id' => '1',
        ]);

        $response->assertRedirect(route('migration.products.index', ['comum_id' => 7]));
        $response->assertSessionHas('status', 'Produto atualizado com sucesso.');
        $response->assertSessionHas('status_type', 'success');
    }

    public function testUpdateWithReturnUrlRedirectsThere(): void
    {
        $this->productManager
            ->shouldReceive('update')
            ->once()
            ->andReturn($this->boundProduct);

        $response = $this->from(route('migration.products.edit', ['product' => 42]))
            ->put(route('migration.products.update', ['product' => 42]), [
                'novo_tipo_bem_id' => '99',
                'novo_bem' => 'MESA ESCRITÓRIO',
                'nova_dependencia_id' => '1',
                'return_url' => '/products?comum_id=7',
            ]);

        $response->assertRedirect('/products?comum_id=7');
        $response->assertSessionHas('status', 'Produto atualizado com sucesso.');
        $response->assertSessionHas('status_type', 'success');
    }

    public function testUpdateRejectsMissingRequiredFields(): void
    {
        $response = $this->from(route('migration.products.edit', ['product' => 42]))
            ->put(route('migration.products.update', ['product' => 42]), [
                'novo_tipo_bem_id' => '0',
                'novo_bem' => '',
                'nova_dependencia_id' => '0',
            ]);

        $response->assertRedirect(route('migration.products.edit', ['product' => 42]));
        $response->assertSessionHasErrors(['novo_tipo_bem_id', 'novo_bem', 'nova_dependencia_id']);
    }

    public function testUpdateHandlesRuntimeException(): void
    {
        $this->productManager
            ->shouldReceive('update')
            ->once()
            ->andThrow(new RuntimeException('Erro ao atualizar produto.'));

        $response = $this->from(route('migration.products.edit', ['product' => 42]))
            ->put(route('migration.products.update', ['product' => 42]), [
                'novo_tipo_bem_id' => '99',
                'novo_bem' => 'MESA ESCRITÓRIO',
                'nova_dependencia_id' => '1',
            ]);

        $response->assertRedirect(route('migration.products.edit', ['product' => 42]));
        $response->assertSessionHas('status', 'Erro ao atualizar produto.');
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Destroy (not implemented in controller) ─────────────────────────────
    // Note: LegacyProductController does NOT have a destroy() method and there
    // is no delete/destroy route defined for products. The management service
    // interface (LegacyProductManagementServiceInterface) only exposes
    // createMany() and update() — no delete method exists.
    // These placeholder tests document the expected flow if destroy were added.

    public function testDestroyNotImplemented(): void
    {
        $this->markTestSkipped(
            'LegacyProductController has no destroy() method. '
            . 'No route or service method exists for product deletion.'
        );
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

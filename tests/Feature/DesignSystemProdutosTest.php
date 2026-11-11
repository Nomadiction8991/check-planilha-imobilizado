<?php

namespace Tests\Feature;

use App\Contracts\LegacyProductBrowserServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class DesignSystemProdutosTest extends TestCase
{
    public function test_listagem_com_produto_mostra_dados_e_acao(): void
    {
        $product = new \App\Models\Legacy\Produto();
        $product->forceFill(['id_produto' => 42, 'codigo' => 'PROD-001', 'bem' => 'CADEIRA', 'novo' => 1]);
        $product->setRelation('dependencia', (object) ['descricao' => 'SALÃO']);
        $product->setRelation('tipoBem', null);
        $service = $this->mock(LegacyProductBrowserServiceInterface::class);
        $service->shouldReceive('paginate')->once()->andReturn(new LengthAwarePaginator([$product], 1, 20));
        foreach (['churchOptions', 'administrationOptions', 'dependencyOptions', 'assetTypeOptions'] as $method) {
            $service->shouldReceive($method)->once()->andReturn(collect());
        }
        $service->shouldReceive('statusOptions')->once()->andReturn([]);
        $this->withSession(['is_admin' => true])->get(route('migration.products.index'))
            ->assertOk()->assertSee('PROD-001')->assertSee('CADEIRA')->assertSee('Editar')
            ->assertSee('Transferir')
            ->assertSee(route('migration.products.transfer', ['product' => 42]))
            ->assertSee('aria-label="Lista de produtos"', false)
            ->assertSee('@media (max-width: 639px)', false)
            ->assertSee('.product-listing tbody tr', false)
            ->assertSee('@media (min-width: 640px) and (max-width: 860px)', false);
    }
    public function test_edicao_exibe_erro_no_campo(): void
    {
        $product = new \App\Models\Legacy\Produto();
        $product->forceFill(['id_produto' => 42, 'comum_id' => 7, 'codigo' => 'PROD-001', 'bem' => 'CADEIRA', 'tipo_bem_id' => 99, 'dependencia_id' => 0, 'editado' => 0]);
        $product->exists = true;
        $product->setRelation('comum', (object) ['id' => 7, 'codigo' => '12', 'descricao' => 'Central']);
        $product->setRelation('dependencia', (object) ['id' => 0, 'descricao' => '']);
        $product->setRelation('tipoBem', (object) ['id' => 99, 'codigo' => '99', 'descricao' => 'Móveis']);
        $this->app['router']->bind('product', fn () => $product);
        $service = $this->mock(LegacyProductBrowserServiceInterface::class);
        $service->shouldReceive('assetTypeOptions')->once()->andReturn(collect());
        $service->shouldReceive('dependencyOptions')->once()->andReturn(collect());

        $response = $this->get(route('migration.products.edit', ['product' => 42]));
        $response->assertOk()->assertSee('Novo tipo de bem')->assertSee('Salvar alterações');
        $errors = new \Illuminate\Support\ViewErrorBag();
        $errors->put('default', new \Illuminate\Support\MessageBag(['novo_tipo_bem_id' => 'Escolha um tipo.']));
        $html = $response->original->with('errors', $errors)->render();
        $this->assertStringContainsString('id="novo_tipo_bem_id-error"', $html);
        $this->assertStringContainsString('Escolha um tipo.', $html);
    }
    public function test_criacao_mostra_erro_junto_ao_campo(): void
    {
        $service = $this->mock(LegacyProductBrowserServiceInterface::class);
        foreach (['churchOptions', 'dependencyOptions', 'assetTypeOptions'] as $method) {
            $service->shouldReceive($method)->once()->andReturn(collect());
        }

        $response = $this->get(route('migration.products.create'));
        $response->assertOk()->assertSee('Igreja')->assertSee('Salvar produto');
        $errors = new \Illuminate\Support\ViewErrorBag();
        $errors->put('default', new \Illuminate\Support\MessageBag(['comum_id' => 'Escolha uma igreja.']));
        $html = $response->original->with('errors', $errors)->render();
        $this->assertStringContainsString('id="comum_id-error"', $html);
        $this->assertStringContainsString('Escolha uma igreja.', $html);
    }
    public function test_listagem_apresenta_acoes_e_estado_vazio(): void
    {
        $service = $this->mock(LegacyProductBrowserServiceInterface::class);
        $service->shouldReceive('paginate')->once()->andReturn(new LengthAwarePaginator([], 0, 20));
        foreach (['churchOptions', 'administrationOptions', 'dependencyOptions', 'assetTypeOptions'] as $method) {
            $service->shouldReceive($method)->once()->andReturn(collect());
        }
        $service->shouldReceive('statusOptions')->once()->andReturn([]);

        $this->get(route('migration.products.index'))
            ->assertOk()
            ->assertSee('Busca geral')
            ->assertSee('Filtros')
            ->assertSee('Nenhum produto ativo encontrado')
            ->assertSee('product-listing', false);
    }

    public function test_listagem_apresenta_filtros_em_popup_no_mesmo_formulario(): void
    {
        $service = $this->mock(LegacyProductBrowserServiceInterface::class);
        $service->shouldReceive('paginate')->once()->andReturn(new LengthAwarePaginator([], 0, 20));
        foreach (['churchOptions', 'administrationOptions', 'dependencyOptions', 'assetTypeOptions'] as $method) {
            $service->shouldReceive($method)->once()->andReturn(collect());
        }
        $service->shouldReceive('statusOptions')->once()->andReturn([]);

        $this->get(route('migration.products.index'))
            ->assertOk()
            ->assertSee('data-filter-open', false)
            ->assertSee('aria-label="Buscar produtos"', false)
            ->assertSee('aria-label="Filtros"', false)
            ->assertDontSee('<span class="mobile-action-text">Buscar</span>', false)
            ->assertDontSee('<span class="mobile-action-text">Filtros</span>', false)
            ->assertSee('<dialog id="filtros-produtos"', false)
            ->assertSee('Aplicar filtros')
            ->assertDontSee('class="product-filters-toggle"', false);
    }

    public function test_listagem_mostra_atalho_de_transferencias_quando_ha_igreja_atual(): void
    {
        $this->mockListagemVazia();

        $this->withSession(['is_admin' => true, 'comum_id' => 7])->get(route('migration.products.index'))
            ->assertOk()
            ->assertSee(route('migration.transfers.index'));
    }

    public function test_listagem_sem_igreja_atual_nao_mostra_atalho_de_transferencias(): void
    {
        $this->mockListagemVazia();

        $this->withSession(['is_admin' => true])->get(route('migration.products.index'))
            ->assertOk()
            ->assertDontSee('href="' . route('migration.transfers.index') . '"', false);
    }

    private function mockListagemVazia(): void
    {
        $service = $this->mock(LegacyProductBrowserServiceInterface::class);
        $service->shouldReceive('paginate')->andReturn(new LengthAwarePaginator([], 0, 20));
        foreach (['churchOptions', 'administrationOptions', 'dependencyOptions', 'assetTypeOptions'] as $method) {
            $service->shouldReceive($method)->andReturn(collect());
        }
        $service->shouldReceive('statusOptions')->andReturn([]);
    }
}

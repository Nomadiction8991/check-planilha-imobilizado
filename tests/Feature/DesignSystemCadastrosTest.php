<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\AssetTypeFilters;
use App\DTO\DepartmentFilters;
use App\DTO\LegacyMailConfigurationData;
use App\DTO\LegacyNavigationOrderData;
use App\Models\Legacy\TipoBem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use Tests\TestCase;

final class DesignSystemCadastrosTest extends TestCase
{
    public function test_cadastro_de_administracao_apresenta_campos_e_erros_proximos(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn(['is_admin' => true, 'nome' => 'Administrador', 'email' => 'admin@localhost']);
            $mock->shouldReceive('currentChurch')->andReturnNull();
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(LegacyNavigationServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('navigation')->andReturn([]));

        $response = $this->withSession(['is_admin' => true, 'legacy_permissions' => ['administrations.create' => true]])
            ->get(route('migration.administrations.create'));

        $response->assertOk();
        $response->assertSee('Salvar administração');
        $response->assertSee('cadastro-form', false);

        $assetType = new TipoBem();
        $assetType->forceFill(['id' => 1, 'codigo' => '1', 'descricao' => 'Cadeira', 'administracao_id' => 1]);
        $emptyPage = new LengthAwarePaginator([], 0, 20);
        $mail = new LegacyMailConfigurationData('smtp.example.com', 587, 'tls', 'teste@example.com', null, 'teste@example.com', 'Sistema');

        Route::view('/teste-design/tipos', 'asset-types.index', [
            'assetTypes' => $emptyPage, 'filters' => new AssetTypeFilters(null, '', null, 1, 20),
            'administrations' => collect(), 'states' => [], 'totalAll' => 0,
        ]);
        Route::view('/teste-design/tipos/novo', 'asset-types.create', ['administrations' => collect()]);
        Route::view('/teste-design/tipos/editar', 'asset-types.edit', ['assetType' => $assetType, 'administrations' => collect()]);
        Route::view('/teste-design/dependencias', 'departments.index', [
            'departments' => $emptyPage, 'filters' => new DepartmentFilters(null, null, '', null, 1, 20),
            'administrations' => collect(), 'churches' => collect(), 'states' => [], 'totalAll' => 0,
        ]);
        Route::view('/teste-design/dependencias/nova', 'departments.create', ['churches' => collect()]);
        Route::view('/teste-design/dependencias/editar', 'departments.edit', [
            'department' => (object) ['id' => 1, 'comum_id' => 1, 'descricao' => 'Salão'], 'churches' => collect(),
        ]);
        Route::view('/teste-design/configuracoes', 'configurations.index', [
            'mailConfiguration' => $mail, 'menuOrder' => new LegacyNavigationOrderData([]), 'menuItems' => [],
        ]);

        $this->get('/teste-design/tipos')->assertOk()->assertSee('Novo tipo de bem')->assertSee('Nenhum tipo de bem encontrado');
        $this->get('/teste-design/tipos/novo')->assertOk()->assertSee('Administração')->assertSee('Salvar tipo de bem');
        $this->get('/teste-design/tipos/editar')->assertOk()->assertSee('Descrição')->assertSee('Salvar alterações');
        $this->get('/teste-design/dependencias')->assertOk()->assertSee('Nova dependência')->assertSee('Nenhuma dependência encontrada');
        $this->get('/teste-design/dependencias/nova')->assertOk()->assertSee('Igreja')->assertSee('Salvar dependência');
        $this->get('/teste-design/dependencias/editar')->assertOk()->assertSee('Descrição')->assertSee('Salvar alterações');
        $this->get('/teste-design/configuracoes')->assertOk()->assertSee('Host SMTP')->assertSee('Salvar e-mail')->assertSee('Ordem dos menus');
    }

    public function test_configuracoes_disponibiliza_estilos_para_tema_escuro(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn(['is_admin' => true, 'nome' => 'Administrador', 'email' => 'admin@localhost']);
            $mock->shouldReceive('currentChurch')->andReturnNull();
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(LegacyNavigationServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('navigation')->andReturn([]));
        Route::view('/teste-design/configuracoes-tema', 'configurations.index', [
            'mailConfiguration' => new LegacyMailConfigurationData('smtp.example.com', 587, 'tls', 'teste@example.com', null, 'teste@example.com', 'Sistema'),
            'menuOrder' => new LegacyNavigationOrderData([]), 'menuItems' => [],
        ])->middleware('web');

        $this->withSession(['is_admin' => true])->get('/teste-design/configuracoes-tema')->assertOk()
            ->assertSee("html[data-theme='dark'] .config-card {", false)
            ->assertSee("html[data-theme='dark'] .config-card::before {", false)
            ->assertSee("html[data-theme='dark'] .config-badge--mail {", false)
            ->assertSee("html[data-theme='dark'] .config-card--menu::before {", false);
    }
}

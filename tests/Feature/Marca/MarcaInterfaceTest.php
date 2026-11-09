<?php

declare(strict_types=1);

namespace Tests\Feature\Marca;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\LegacyMailConfigurationData;
use App\DTO\LegacyNavigationOrderData;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use Tests\TestCase;

final class MarcaInterfaceTest extends TestCase
{
    public function test_mostra_marca_e_formulario_de_configuracao(): void
    {
        config(['app.name' => 'Marca Exemplo']);
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn(['is_admin' => true, 'nome' => 'Administrador', 'email' => 'admin@localhost']);
            $mock->shouldReceive('currentChurch')->andReturnNull();
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(LegacyNavigationServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('navigation')->andReturn([]));
        Route::view('/teste-marca', 'configurations.index', [
            'mailConfiguration' => new LegacyMailConfigurationData('smtp.example.com', 587, 'tls', 'teste@example.com', null, 'teste@example.com', 'Sistema'),
            'menuOrder' => new LegacyNavigationOrderData([]), 'menuItems' => [],
        ])->middleware('web');

        $response = $this->withSession(['is_admin' => true])->get('/teste-marca');
        $response->assertOk()->assertSee('<strong id="mobile-menu-title">Marca Exemplo</strong>', false)
            ->assertSee('<img src="'.route('marca.logo', ['v' => 'padrao']).'" alt=""', false)
            ->assertSee('<link rel="icon" href="'.route('marca.logo', ['v' => 'padrao']).'"', false)
            ->assertSee('action="'.url('/configuracoes/marca').'"', false)
            ->assertSee('name="app_name"', false)->assertSee('name="app_logo"', false)
            ->assertSee('accept="image/png,image/jpeg,image/webp"', false);
    }

    public function test_mostra_erros_junto_aos_campos(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn(['is_admin' => true, 'nome' => 'Administrador', 'email' => 'admin@localhost']);
            $mock->shouldReceive('currentChurch')->andReturnNull();
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(LegacyNavigationServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('navigation')->andReturn([]));
        Route::view('/teste-marca-erros', 'configurations.index', [
            'mailConfiguration' => new LegacyMailConfigurationData('smtp.example.com', 587, 'tls', 'teste@example.com', null, 'teste@example.com', 'Sistema'),
            'menuOrder' => new LegacyNavigationOrderData([]), 'menuItems' => [],
        ]);

        $html = $this->withSession(['is_admin' => true])->withViewErrors(['app_name' => 'Nome inválido.', 'app_logo' => 'Logo inválida.'])->get('/teste-marca-erros')->assertOk()->getContent();
        self::assertMatchesRegularExpression('/name="app_name"[^>]*>\s*<span[^>]*>Nome inválido\.<\/span>/s', $html);
        self::assertMatchesRegularExpression('/name="app_logo"[^>]*>\s*<span[^>]*>Logo inválida\.<\/span>/s', $html);
    }
}

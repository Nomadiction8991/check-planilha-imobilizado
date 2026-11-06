<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Support\Modo\EstadoModo;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use Tests\TestCase;

final class ModoIgrejaFaixaTest extends TestCase
{
    private string $arquivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arquivo = sys_get_temp_dir().'/estado-faixa-'.uniqid().'/estado.json';
        $this->app->instance(EstadoModo::class, new EstadoModo($this->arquivo));
        config(['modo_igreja.ativo' => true, 'modo_igreja.servidor_url' => 'https://servidor-secreto.test']);
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn(['nome' => 'Maria Silva', 'email' => 'MARIA@EXEMPLO.COM']);
            $mock->shouldReceive('currentChurch')->andReturnNull();
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(LegacyPermissionServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('currentPermissions')->andReturn([]));
        $this->mock(LegacyNavigationServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('navigation')->andReturn([]));
        Route::get('/teste-faixa-modo', fn () => view('layouts.migration'))->middleware(['web', 'legacy.auth']);
    }

    protected function tearDown(): void
    {
        if (is_file($this->arquivo)) {
            unlink($this->arquivo);
            unlink($this->arquivo.'.lock');
            rmdir(dirname($this->arquivo));
        }
        parent::tearDown();
    }

    public function test_mostra_modo_offline(): void
    {
        $estado = app(EstadoModo::class);
        $estado->registrarResultado(false);
        $estado->registrarResultado(false);
        $this->pagina()->assertOk()->assertSee('Modo offline')->assertSee('Você está trabalhando na cópia deste computador.');
    }

    public function test_mostra_modo_online(): void
    {
        $this->pagina()->assertOk()->assertSee('Modo online')->assertSee('Você está trabalhando com o servidor online.');
    }

    public function test_nao_mostra_faixa_quando_modo_desligado(): void
    {
        config(['modo_igreja.ativo' => false]);
        $this->pagina()->assertOk()->assertDontSee('Modo online')->assertDontSee('Modo offline');
    }

    public function test_mostra_origem_atual_sem_url_do_servidor(): void
    {
        $this->pagina()->assertOk()->assertSee('http://localhost')->assertDontSee('https://servidor-secreto.test');
    }

    private function pagina(): \Illuminate\Testing\TestResponse
    {
        return $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get('http://localhost/teste-faixa-modo');
    }
}

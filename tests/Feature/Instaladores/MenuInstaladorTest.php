<?php

declare(strict_types=1);

namespace Tests\Feature\Instaladores;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use Mockery\MockInterface;

final class MenuInstaladorTest extends TestCase
{
    public function test_usuario_comum_ve_os_dois_instaladores_no_menu(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn(['nome' => 'Maria Silva', 'email' => 'MARIA@EXEMPLO.COM']);
            $mock->shouldReceive('currentChurch')->andReturnNull();
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(LegacyPermissionServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('currentPermissions')->andReturn([]));
        $this->mock(LegacyNavigationServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('navigation')->andReturn([]));
        Route::get('/teste-menu-instalador', fn () => view('layouts.migration'))
            ->middleware(['web', 'legacy.auth']);

        $resposta = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get('/teste-menu-instalador');

        $resposta->assertOk();
        $resposta->assertSee('Instalador do modo igreja');
        $resposta->assertSee('Windows (.exe)');
        $resposta->assertSee('Linux (.deb)');
        $resposta->assertSee('href="'.route('migration.instaladores.baixar', 'windows').'"', false);
        $resposta->assertSee('href="'.route('migration.instaladores.baixar', 'linux').'"', false);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use Mockery\MockInterface;

final class AppShellDesignSystemTest extends TestCase
{
    public function test_destaque_claro_nao_usa_sombra_pesada(): void
    {
        $html = view('layouts.migration')->render();

        $this->assertDoesNotMatchRegularExpression("/html\\[data-theme='light'\\]\\s+\\.topbar,\\s*html\\[data-theme='light'\\]\\s+\\.hero\\s*\\{[^}]*box-shadow:\\s*var\\(--shadow-strong\\)/", $html);
        $this->assertMatchesRegularExpression("/html\\[data-theme='light'\\]\\s+\\.hero\\s*\\{[^}]*box-shadow:\\s*var\\(--shadow-card\\)/", $html);
    }

    public function test_casca_clara_usa_cores_solidas_e_fonte_da_interface(): void
    {
        $html = view('layouts.migration')->render();

        $this->assertMatchesRegularExpression('/button,\s*input,\s*select,\s*textarea\s*\{\s*font:\s*inherit\s*;/', $html);
        $this->assertMatchesRegularExpression("/html\\[data-theme='light'\\]\\s+\\.btn\\.primary\\s*\\{[^}]*background:\\s*var\\(--product-primary\\)/", $html);
        $this->assertMatchesRegularExpression("/html\\[data-theme='light'\\]\\s+\\.page-quick-actions-toggle\\s*\\{[^}]*background:\\s*var\\(--product-primary\\)/", $html);
        $this->assertDoesNotMatchRegularExpression('/(?:^|\})\s*body\s*\{[^}]*background:\s*[^;]*gradient/m', $html);
        $this->assertDoesNotMatchRegularExpression("/html\\[data-theme='light'\\]\\s+body\\s*\\{[^}]*background:\\s*[^;]*gradient/", $html);
    }

    public function test_tema_escuro_disponibiliza_tokens_de_superficie(): void
    {
        $html = view('layouts.migration')->render();

        $this->assertMatchesRegularExpression("/html\\[data-theme='dark'\\]\\s*\\{[^}]*--ui-surface\\s*:/", $html);
    }

    public function test_casca_disponibiliza_tokens_e_navegacao_lateral(): void
    {
        $route = new \Illuminate\Routing\Route('GET', '/products', []);
        $route->name('migration.products.index');
        request()->setRouteResolver(fn () => $route);
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn(['nome' => 'Maria', 'email' => 'maria@example.com']);
            $mock->shouldReceive('currentChurch')->andReturnNull();
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(LegacyPermissionServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('currentPermissions')->andReturn([]));
        $this->mock(LegacyNavigationServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('navigation')->andReturn([
            ['route' => '/products', 'label' => 'Produtos', 'active_patterns' => ['migration.products.*']],
        ]));
        $html = view('layouts.migration')->render();

        $this->assertStringContainsString('--product-primary: #176DA9', $html);
        $this->assertStringContainsString('class="mobile-menu-panel app-sidebar"', $html);
        $this->assertStringContainsString('@media (min-width: 1024px)', $html);
        $this->assertStringContainsString('aria-label="Navegação principal"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('href="#main-content"', $html);
    }
}

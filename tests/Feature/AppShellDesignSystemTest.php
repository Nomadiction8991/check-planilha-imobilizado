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
    public function test_atalhos_ficam_ocultos_quando_um_dialogo_esta_aberto(): void
    {
        $source = file_get_contents(resource_path('views/layouts/migration.blade.php'));
        $this->assertMatchesRegularExpression('/body:has\(dialog\[open\]\)\s+\.page-quick-actions\s*\{[^}]*display:\s*none/s', $source);
        $this->assertStringContainsString('data-page-quick-actions', $source);
    }

    public function test_casca_organiza_sair_tema_e_dialogo_de_filtros(): void
    {
        $source = file_get_contents(resource_path('views/layouts/migration.blade.php'));
        preg_match('~<header class="topbar">(.*?)</header>~s', $source, $topbar);
        preg_match('~<div class="mobile-menu-footer">(.*?)</div>~s', $source, $footer);
        $this->assertStringNotContainsString('session-logout', $topbar[1]);
        $this->assertStringNotContainsString('data-theme-toggle', $topbar[1]);
        $this->assertStringContainsString('logout', $footer[1]);
        $this->assertStringContainsString('data-theme-toggle', $footer[1]);
        $this->assertStringContainsString('data-filter-open', $source);
        $this->assertStringContainsString('showModal()', $source);
        $autosubmit = file_get_contents(resource_path('views/partials/filter-autosubmit.blade.php'));
        $this->assertStringContainsString("closest('[data-filter-dialog]')", $autosubmit);
    }

    public function test_destaque_claro_nao_usa_sombra_pesada(): void
    {
        $html = view('layouts.migration')->render();

        $this->assertDoesNotMatchRegularExpression("/html\\[data-theme='light'\\]\\s+\\.topbar,\\s*html\\[data-theme='light'\\]\\s+\\.hero\\s*\\{[^}]*box-shadow:\\s*var\\(--shadow-strong\\)/", $html);
        $this->assertStringNotContainsString('html[data-theme=\'light\'] .hero', $html);
    }

    public function test_casca_clara_usa_cores_solidas_e_fonte_da_interface(): void
    {
        $html = view('layouts.migration')->render();

        $this->assertMatchesRegularExpression('/button,\s*input,\s*select,\s*textarea\s*\{\s*font:\s*inherit\s*;/', $html);
        $this->assertMatchesRegularExpression('/\.btn\.primary\s*\{[^}]*background:\s*var\(--product-primary\)/', $html);
        $this->assertMatchesRegularExpression('/\.page-quick-actions-toggle\s*\{[^}]*background:\s*var\(--product-primary\)/', $html);
        $this->assertDoesNotMatchRegularExpression('/(?:^|\})\s*body\s*\{[^}]*background:\s*[^;]*gradient/m', $html);
        $this->assertDoesNotMatchRegularExpression("/html\\[data-theme='light'\\]\\s+body\\s*\\{[^}]*background:\\s*[^;]*gradient/", $html);
    }

    public function test_casca_nao_define_cores_fixas_ou_regras_por_tema(): void
    {
        foreach (['layouts/migration', 'partials/quick-actions', 'partials/request-loading'] as $view) {
            $source = file_get_contents(resource_path('views/'.$view.'.blade.php'));
            preg_match_all('~<style>(.*?)</style>~s', $source, $styles);
            $css = implode("\n", $styles[1]);
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?)\s*\(|(?:linear|radial|conic)-gradient\s*\(|html\[data-theme=/i', $css, $view);
        }
    }

    public function test_menu_lateral_apresenta_navegacao_compacta_sem_setas(): void
    {
        $source = file_get_contents(resource_path('views/layouts/migration.blade.php'));
        $this->assertStringNotContainsString('chevron_right', $source);
        $this->assertStringNotContainsString('Acesse cadastros e rotinas administrativas.', $source);
        $this->assertMatchesRegularExpression('/\.mobile-menu-nav\s*\{[^}]*gap:\s*2px/s', $source);
        $this->assertMatchesRegularExpression('/\.mobile-menu-link\s*\{[^}]*min-height:\s*44px/s', $source);
    }

    public function test_filtros_de_produtos_mantem_acoes_e_campos_compactos(): void
    {
        $source = file_get_contents(resource_path('views/layouts/migration.blade.php'));
        $this->assertMatchesRegularExpression('/\.filters-primary:has\(\.filters-query\):has\(\.filters-principal\)\s*\{[^}]*repeat\(4,\s*minmax\(0,\s*1fr\)\)/s', $source);
        $this->assertMatchesRegularExpression('/\.filters\s+label\s*\{[^}]*gap:\s*4px/s', $source);
        $this->assertMatchesRegularExpression('/\.mobile-menu-user\s*\{[^}]*display:\s*none/s', $source);
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
        $this->assertStringContainsString('<div id="toast-region" class="toast-region" role="status" aria-live="polite"></div>', $html);
        $this->assertStringContainsString('window.showToast', $html);
        $this->assertStringNotContainsString('<small>Área restrita</small>', $html);
        $this->assertMatchesRegularExpression('~<div class="brand">.*?Produtos.*?</div>~s', $html);
        $this->assertMatchesRegularExpression('~class="mobile-menu-logout"[^>]*>.*?logout~s', $html);
    }
}

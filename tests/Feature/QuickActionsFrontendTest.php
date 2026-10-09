<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class QuickActionsFrontendTest extends TestCase
{
    public function test_quick_actions_partial_can_be_rendered(): void
    {
        $view = $this->view('partials.quick-actions');

        $view->assertSee('data-quick-actions-bar', false);
        $view->assertSee('data-quick-action="search"', false);
        $view->assertSee('data-quick-action="top"', false);
        $view->assertSee('data-quick-action="shortcuts"', false);
    }

    public function test_quick_actions_has_accessible_keyboard_shortcuts_modal(): void
    {
        $view = $this->view('partials.quick-actions');

        $view->assertSee('Atalhos de Teclado');
        $view->assertSee('role="dialog"', false);
        $view->assertSee('aria-modal="true"', false);
        $view->assertSee('data-quick-shortcuts-modal', false);
    }
}

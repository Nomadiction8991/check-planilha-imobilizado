<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class TemaGlobalDesignSystemTest extends TestCase
{
    public function test_tema_escuro_usa_tokens_sem_cores_antigas_ou_gradiente_no_fundo(): void
    {
        $html = view('layouts.migration')->render();

        $this->assertMatchesRegularExpression("/html\\[data-theme='dark'\\]\\s*\\{[^}]*--product-primary\\s*:/", $html);
        $this->assertStringNotContainsString('#91ddd1', strtolower($html));
        $this->assertDoesNotMatchRegularExpression("/html\\[data-theme='dark'\\]\\s+body\\s*\\{[^}]*background\\s*:[^;]*gradient/", $html);
        $this->assertStringContainsString('--product-primary-text: #171613', $html);
        $this->assertStringContainsString('--bg: var(--ui-bg)', $html);
        $this->assertStringContainsString('Ativar tema claro', $html);
        $this->assertStringContainsString('Ativar tema escuro', $html);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class DesignSystemUsuariosTest extends TestCase
{
    public function test_formulario_apresenta_erros_junto_aos_campos(): void
    {
        $this->withViewErrors(['nome' => 'Informe o nome.']);

        $html = view('users.create', [
            'administrations' => collect([(object) ['id' => 1, 'descricao' => 'Central']]),
            'states' => [],
            'legacyPermissions' => [],
        ])->render();

        $this->assertStringContainsString('id="erro-nome"', $html);
        $this->assertStringContainsString('aria-describedby="erro-nome"', $html);
        $this->assertStringContainsString('Informe o nome.', $html);
        $this->assertStringContainsString('Salvar usuário', $html);
    }
}

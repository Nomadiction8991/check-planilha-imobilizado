<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaginaErro503Test extends TestCase
{
    public function test_exibe_mensagem_e_link_para_o_inicio(): void
    {
        Route::post('/teste-erro-503', fn () => abort(503, 'Mensagem de teste para a tela'))->middleware('web');

        $this->post('/teste-erro-503')
            ->assertStatus(503)
            ->assertSee('Sem resposta do servidor')
            ->assertSee('Mensagem de teste para a tela')
            ->assertSee('Voltar ao início')
            ->assertSee(route('migration.dashboard'));
    }
}

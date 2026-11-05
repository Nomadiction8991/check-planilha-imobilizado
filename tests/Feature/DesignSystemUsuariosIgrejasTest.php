<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class DesignSystemUsuariosIgrejasTest extends TestCase
{
    public function test_edicao_de_igreja_mostra_erro_junto_ao_cnpj(): void
    {
        $church = new \App\Models\Legacy\Comum();
        $church->forceFill(['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central', 'cnpj' => '123', 'estado' => 'MT', 'cidade' => 'Cuiabá']);
        $church->exists = true;
        $this->app['router']->bind('church', fn () => $church);
        $this->mock(\App\Contracts\LegacyChurchBrowserServiceInterface::class, function (\Mockery\MockInterface $mock): void {
            $mock->shouldReceive('administrationOptions')->andReturn(collect());
        });
        $url = route('migration.churches.edit', ['church' => 7]);
        $this->from($url)->put(route('migration.churches.update', ['church' => 7]), [])->assertRedirect($url);
        $response = $this->get($url);

        $response->assertOk();
        $response->assertSee('Salvar alterações');
        $response->assertSee('aria-describedby="church-cnpj-error"', false);
        $response->assertSee('O CNPJ é obrigatório.');
    }

    public function test_lista_de_igrejas_orienta_quando_filtro_nao_traz_resultados(): void
    {
        $this->mock(\App\Contracts\LegacyChurchBrowserServiceInterface::class, function (\Mockery\MockInterface $mock): void {
            $mock->shouldReceive('paginate')->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20));
            $mock->shouldReceive('countAll')->andReturn(0);
            $mock->shouldReceive('administrationOptions')->andReturn(collect());
        });

        $response = $this->get(route('migration.churches.index'));

        $response->assertOk();
        $response->assertSee('Nenhuma igreja encontrada para o filtro informado.');
        $response->assertSee('Ajuste os filtros ou limpe a busca para ver outras igrejas.');
        $response->assertSee('Aplicar filtros');
    }

    public function test_etiquetas_apresenta_listas_identificadas_e_estado_vazio(): void
    {
        \Illuminate\Support\Facades\Route::get('/teste-design-etiquetas', fn () => view('labels.index', [
            'data' => ['selected_dependency_id' => null, 'dependencies' => [], 'products' => []],
            'churches' => [], 'churchId' => null,
        ]));

        $response = $this->get('/teste-design-etiquetas');
        $response->assertOk();
        $response->assertSee('Selecione uma igreja acima para carregar as etiquetas.');
        $response->assertSee('aria-label="Etiquetas manuais"', false);
        $response->assertSee('aria-label="Etiquetas verificadas"', false);
        $response->assertSee('aria-label="Todas as etiquetas"', false);
    }

    public function test_cadastro_de_usuario_oferece_campos_de_telefone_adequados(): void
    {
        \Illuminate\Support\Facades\Route::get('/teste-design-usuario', fn () => view('users.create', [
            'administrations' => collect(), 'states' => [], 'errors' => new \Illuminate\Support\ViewErrorBag(),
        ]));

        $response = $this->get('/teste-design-usuario');

        $response->assertOk();
        $response->assertSee('Salvar usuário');
        $response->assertSee('type="tel" name="telefone"', false);
        $response->assertSee('type="tel" name="telefone_conjuge"', false);
    }

    public function test_acesso_publico_apresenta_erro_proximo_a_igreja(): void
    {
        \Illuminate\Support\Facades\Route::get('/teste-design-acesso', fn () => view('public-access.create', [
            'churches' => collect(),
        ])->withErrors(['comum_id' => 'Selecione uma igreja.']));

        $response = $this->get('/teste-design-acesso');

        $response->assertOk();
        $response->assertSee('Buscar igreja');
        $response->assertSee('Continuar');
        $response->assertSee('aria-describedby="public-church-error"', false);
        $response->assertSee('Selecione uma igreja.');
    }

    public function test_recuperacao_de_senha_apresenta_campos_e_acao_principal(): void
    {
        $response = $this->get(route('migration.password.request'));

        $response->assertOk();
        $response->assertSee('Esqueci minha senha');
        $response->assertSee('Gerar nova senha');
        $response->assertSee('type="tel" name="telefone"', false);
    }
}

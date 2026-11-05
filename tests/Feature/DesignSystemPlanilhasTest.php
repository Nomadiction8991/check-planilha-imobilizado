<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class DesignSystemPlanilhasTest extends TestCase
{
    public function test_erros_oferece_nova_importacao_sem_retorno_a_produtos(): void
    {
        Route::middleware('web')->get('/teste-erros-acao/{modo}', fn (string $modo) => view('spreadsheets.errors', [
            'modo' => $modo, 'comum' => ['codigo' => '1', 'descricao' => 'Central'], 'comumId' => 7,
            'importacao' => null, 'importacaoId' => null, 'resumo' => ['pendentes' => 0],
            'erros' => new LengthAwarePaginator([], 0, 10),
        ]));

        $this->get('/teste-erros-acao/geral')->assertOk()
            ->assertSee('<a class="btn" href="'.route('migration.spreadsheets.create').'">Nova importação</a>', false)
            ->assertDontSee('Voltar para produtos');
        $this->get('/teste-erros-acao/comum')->assertOk()
            ->assertDontSee('Voltar para produtos')
            ->assertDontSee('Nova importação');
    }

    public function test_importacao_apresenta_aviso_e_acao_principal(): void
    {
        Route::middleware('web')->get('/teste-design-planilhas', fn () => view('spreadsheets.import', [
            'administrations' => [(object) ['id' => 3, 'descricao' => 'Administração Central']],
        ]));

        $this->get('/teste-design-planilhas')
            ->assertOk()
            ->assertSee('Escopo da importação')
            ->assertSee('Enviar e analisar')
            ->assertSee('Aviso importante')
            ->assertSee('action="'.route('migration.spreadsheets.store').'"', false)
            ->assertSee('name="arquivo_csv"', false)
            ->assertSee('type="submit"', false);
    }

    public function test_previa_mostra_confirmacao_com_formulario_de_envio(): void
    {
        Route::middleware('web')->get('/teste-design-previa', fn () => view('spreadsheets.preview', [
            'analise' => [], 'importacao' => [], 'importacaoId' => 15,
        ]));

        $this->get('/teste-design-previa')->assertOk()
            ->assertSee('Confirmar igrejas selecionadas')
            ->assertSee('action="'.route('migration.spreadsheets.confirm', ['importacao' => 15]).'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_previa_nao_mostra_linha_lateral_no_painel_de_setores(): void
    {
        Route::middleware('web')->get('/teste-design-previa-borda', fn () => view('spreadsheets.preview', [
            'analise' => [], 'importacao' => [], 'importacaoId' => 15,
        ]));

        $html = $this->get('/teste-design-previa-borda')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/\.dependency-panel\s*\{[^}]*border-left/', $html);
    }

    public function test_previa_nao_pinta_de_azul_a_linha_de_setores_ao_passar_o_mouse(): void
    {
        Route::middleware('web')->get('/teste-design-previa-hover', fn () => view('spreadsheets.preview', [
            'analise' => [], 'importacao' => [], 'importacaoId' => 15,
        ]));

        $html = $this->get('/teste-design-previa-hover')->assertOk()->getContent();

        $this->assertStringNotContainsString('tbody tr:hover td {', $html);
        $this->assertStringContainsString('tbody tr:not(.dependency-row):hover td {', $html);
    }

    public function test_erros_mostra_orientacao_e_lista_com_informacoes_para_celular(): void
    {
        Route::middleware('web')->get('/teste-design-erros', fn () => view('spreadsheets.errors', [
            'modo' => 'importacao', 'importacao' => ['arquivo_nome' => 'arquivo.csv'], 'importacaoId' => 15,
            'resumo' => ['pendentes' => 1],
            'erros' => new LengthAwarePaginator([(object) [
                'id' => 1, 'resolvido' => 0, 'codigo' => '123', 'linha_csv' => 42,
                'descricao_csv' => 'Mesa', 'mensagem_erro' => "linha 42: coluna 'Patrimônio' vazia",
            ]], 1, 10),
        ]));

        $this->get('/teste-design-erros')->assertOk()
            ->assertSee('Corrija os dados indicados e acompanhe a situação de cada erro.')
            ->assertSee("linha 42: coluna 'Patrimônio' vazia")
            ->assertSee('Linha 42')
            ->assertSee('Pendente')
            ->assertSee('data-label="Erro"', false)
            ->assertSee('aria-label="Lista de erros de importação"', false)
            ->assertSee('Baixar CSV de correção');
    }

    public function test_processamento_mantem_estilos_do_progresso_no_tema_escuro(): void
    {
        Route::middleware('web')->get('/teste-design-processamento', fn () => view('spreadsheets.processing', [
            'importacao' => [], 'importacaoId' => 15,
        ]));

        $this->get('/teste-design-processamento')->assertOk()
            ->assertSee("html[data-theme='dark'] .processing-track", false)
            ->assertSee("html[data-theme='dark'] .processing-shell.is-complete .processing-track", false)
            ->assertSee('id="progress-bar"', false);
    }
}

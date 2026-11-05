<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\AssetTypeFilters;
use App\Models\Legacy\TipoBem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use Tests\TestCase;

final class CartaoListaMobileTest extends TestCase
{
    public function test_listas_identificam_titulo_e_acoes_dos_cartoes(): void
    {
        foreach ([
            'asset-types/index.blade.php' => ['Descrição', 'Ações'],
            'users/index.blade.php' => ['Usuário', 'Ações'],
            'spreadsheets/preview.blade.php' => ['Igreja', 'Ação'],
        ] as $view => [$title, $actions]) {
            $html = file_get_contents(resource_path('views/'.$view));
            self::assertMatchesRegularExpression('/<td\b[^>]*data-label="'.$title.'"[^>]*data-card-title/', $html, "O cartão de {$view} precisa destacar {$title}.");
            self::assertMatchesRegularExpression('/<td\b[^>]*data-label="'.$actions.'"[^>]*data-card-actions/', $html, "O cartão de {$view} precisa separar {$actions}.");
        }
    }

    public function test_estilo_mobile_organiza_cartoes_sem_transformar_tabela_aninhada(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/migration.blade.php'));
        self::assertStringContainsString('table:not(.dependency-table, .verification-table) > tbody > tr', $layout, 'A tabela de dependências deve continuar uma tabela.');
        self::assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr))', $layout, 'Os dados devem caber em duas colunas.');
        self::assertStringContainsString('data-card-title', $layout, 'O título deve receber destaque.');
        self::assertStringContainsString('data-card-actions', $layout, 'As ações devem ficar separadas.');
        self::assertStringContainsString('min-height: 44px', $layout, 'As ações devem ter altura suficiente para toque.');
        self::assertMatchesRegularExpression('/\.inline-actions\s*>\s*:last-child:nth-child\(odd\)\s*\{\s*grid-column:\s*1\s*\/\s*-1/s', $layout, 'A última ação de uma quantidade ímpar deve ocupar a linha inteira.');
    }

    public function test_textos_longos_da_importacao_ocupam_a_linha_inteira(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/migration.blade.php'));
        foreach (['Responsável', 'Arquivo'] as $campo) {
            self::assertMatchesRegularExpression('/td\[data-label="'.$campo.'"\][^{]*\{\s*grid-column:\s*1\s*\/\s*-1;/s', $layout, "{$campo} precisa ocupar a largura inteira no cartão.");
        }
    }

    public function test_erros_da_previa_identificam_cada_valor_no_celular(): void
    {
        $view = file_get_contents(resource_path('views/spreadsheets/preview.blade.php'));
        preg_match('/<table class="import-errors-table">(.*?)<\/table>/s', $view, $table);
        self::assertNotEmpty($table, 'A tabela de erros deve existir.');
        foreach (['Linha', 'Código', 'Nome', 'Motivo'] as $label) {
            self::assertMatchesRegularExpression('/<td\b[^>]*data-label="'.$label.'"/', $table[1], "O valor de {$label} deve ter rótulo no celular.");
        }
        self::assertMatchesRegularExpression('/<td\b(?=[^>]*data-label="Linha")(?=[^>]*data-card-title)/', $table[1], 'A linha com erro deve identificar o cartão.');
    }

    public function test_verificacao_preserva_layout_proprio_sem_marcacao_inutil(): void
    {
        $view = file_get_contents(resource_path('views/products/verification.blade.php'));
        self::assertStringContainsString('class="verification-table"', $view, 'A verificação deve manter seu layout próprio.');
        self::assertStringNotContainsString('data-card-title', $view, 'A verificação não deve usar marcação ignorada pelo seu layout.');
        self::assertStringNotContainsString('data-card-actions', $view, 'A verificação não deve usar marcação ignorada pelo seu layout.');
    }

    public function test_dependencias_ficam_em_painel_ligado_a_igreja(): void
    {
        $view = file_get_contents(resource_path('views/spreadsheets/preview.blade.php'));
        self::assertMatchesRegularExpression('/tr\.dependency-row\s*\{[^}]*border:\s*0;[^}]*background:\s*transparent;[^}]*box-shadow:\s*none;/s', $view, 'As dependências não devem parecer outro cartão.');
        self::assertStringNotContainsString('colspan="9" data-label="Dependências"', $view, 'O título do painel já identifica as dependências.');
    }

    public function test_tipo_de_bem_exibe_dados_e_acoes_na_tabela(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn(['is_admin' => true, 'nome' => 'Administrador', 'email' => 'admin@localhost']);
            $mock->shouldReceive('currentChurch')->andReturnNull();
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(LegacyNavigationServiceInterface::class, fn (MockInterface $mock) => $mock->shouldReceive('navigation')->andReturn([]));
        $tipo = new TipoBem();
        $tipo->forceFill(['id' => 1, 'codigo' => '1', 'descricao' => 'Banco de madeira', 'active_products_count' => 0]);
        Route::view('/teste-cartao/tipos', 'asset-types.index', [
            'assetTypes' => new LengthAwarePaginator([$tipo], 1, 20),
            'filters' => new AssetTypeFilters(null, '', null, 1, 20),
            'administrations' => collect(), 'states' => [], 'totalAll' => 1,
        ])->middleware('web');

        $this->withSession(['is_admin' => true])->get('/teste-cartao/tipos')->assertOk()
            ->assertSee('<table', false)->assertSee('Banco de madeira')->assertSee('Ver produtos')
            ->assertSee('Editar')->assertSee('Excluir')->assertSee('data-label="Descrição" data-card-title', false)
            ->assertSee('aria-label="Editar" title="Editar"', false)
            ->assertSee('aria-label="Excluir" title="Excluir"', false)
            ->assertSee('aria-label="Ver produtos" title="Ver produtos"', false)
            ->assertSee('>edit</span>', false)->assertSee('>delete</span>', false)->assertSee('>visibility</span>', false)
            ->assertSee('>search</span><span class="mobile-action-text">Buscar</span>', false)
            ->assertSee('>tune</span><span class="mobile-action-text">Filtros</span>', false);
    }
}

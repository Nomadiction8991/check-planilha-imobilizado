<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ControlesMobileTest extends TestCase
{
    public function test_acoes_dos_cartoes_tem_icone_e_nome_acessivel(): void
    {
        foreach ([
            'administrations/index' => ['Editar' => 'edit', 'Excluir' => 'delete'],
            'asset-types/index' => ['Editar' => 'edit', 'Excluir' => 'delete', 'Ver produtos' => 'visibility'],
            'churches/index' => ['Editar' => 'edit', 'Produtos' => 'visibility'],
            'departments/index' => ['Editar' => 'edit', 'Excluir' => 'delete', 'Ver produtos' => 'visibility'],
            'products/index' => ['Editar' => 'edit'],
            'users/index' => ['Editar' => 'edit', 'Excluir' => 'delete'],
            'spreadsheets/import' => ['Ver' => 'visibility'],
        ] as $view => $acoes) {
            $html = file_get_contents(resource_path('views/'.$view.'.blade.php'));
            foreach ($acoes as $texto => $icone) {
                self::assertMatchesRegularExpression('/<(?:a|button)\b(?=[^>]*aria-label="'.preg_quote($texto, '/').'")(?=[^>]*title="'.preg_quote($texto, '/').'")(?:=>|->|[^>])*>\s*<span class="material-symbols-outlined mobile-action-icon" aria-hidden="true">'.$icone.'<\/span>\s*<span class="mobile-action-text">'.preg_quote($texto, '/').'<\/span>/s', $html, "Ação {$texto} em {$view}");
            }
        }
        foreach (['reports/index', 'spreadsheets/errors', 'spreadsheets/preview'] as $view) {
            self::assertStringContainsString('data-card-actions', file_get_contents(resource_path('views/'.$view.'.blade.php')));
        }
    }

    public function test_barras_de_filtro_tem_icones_e_texto_acessivel(): void
    {
        foreach (['administrations/index', 'asset-types/index', 'audits/index', 'churches/index', 'departments/index', 'labels/index', 'products/index', 'products/verification', 'reports/index', 'users/index'] as $view) {
            $html = file_get_contents(resource_path('views/'.$view.'.blade.php'));
            foreach (['Buscar' => 'search', 'Filtros' => 'tune'] as $texto => $icone) {
                self::assertStringContainsString('>'.$icone.'</span><span class="mobile-action-text">'.$texto.'</span>', $html, "Controle {$texto} em {$view}");
            }
        }
    }

    public function test_layout_compacta_cartoes_e_filtros_no_celular(): void
    {
        $css = file_get_contents(resource_path('views/layouts/migration.blade.php'));
        self::assertMatchesRegularExpression('/\.table-shell:has\(> table:not\(\.dependency-table, \.verification-table\)\)[^{]*\{[^}]*border:\s*0;[^}]*border-radius:\s*0;[^}]*background:\s*transparent;[^}]*box-shadow:\s*none;[^}]*padding:\s*0;/s', $css);
        self::assertMatchesRegularExpression('/table:not\(\.dependency-table, \.verification-table\)\s*\{[^}]*border:\s*0;[^}]*border-radius:\s*0;[^}]*background:\s*transparent;[^}]*box-shadow:\s*none;[^}]*padding:\s*0;/s', $css);
        self::assertMatchesRegularExpression('/table:not\(\.dependency-table, \.verification-table\) > tbody\s*\{[^}]*gap:\s*8px;[^}]*padding:\s*0;[^}]*border:\s*0;[^}]*border-radius:\s*0;[^}]*background:\s*transparent;[^}]*box-shadow:\s*none;/s', $css);
        self::assertMatchesRegularExpression('/table:not\(\.dependency-table, \.verification-table\) > tbody > tr\s*\{[^}]*gap:\s*8px;[^}]*padding:\s*12px;/s', $css);
        self::assertMatchesRegularExpression('/td::before\s*\{[^}]*margin-bottom:\s*2px;/s', $css);
        self::assertMatchesRegularExpression('/\.filters-bar\s*\{\s*display:\s*flex;\s*gap:\s*8px;/s', $css);
        self::assertStringContainsString('width: 44px;', $css);
        self::assertStringContainsString('clip-path: inset(50%)', $css);
        self::assertMatchesRegularExpression('/\.inline-actions > \.btn:not\(:has\(\.mobile-action-icon\)\)\s*\{\s*width:\s*auto;/', $css, 'Ações só de texto devem ter largura automática no celular.');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class DesignSystemRelatoriosAuditoriaTest extends TestCase
{
    public function test_editor_permite_recolher_registro_com_botao_acessivel(): void
    {
        $this->withoutMiddleware();
        $response = $this->get(route('migration.reports.editor', ['formulario' => '14.1']));

        $response->assertOk();
        $response->assertSee('<button type="button" id="log-header" aria-expanded="true" aria-controls="log-body">', false);
        $response->assertSee('Registro de posições');
        $response->assertSee("logHeader.setAttribute('aria-expanded', String(logBody.style.display !== 'none'))", false);
    }

    public function test_editor_apresenta_titulo_e_controles_acessiveis(): void
    {
        $response = \Illuminate\Testing\TestResponse::fromBaseResponse(response()->view('reports.editor', [
            'formulario' => '14.1',
            'editorBackUrl' => '/reports',
            'bgUrl' => '/formulario.png',
            'cellEditorBaseUrl' => '/cell-reports',
            'cellEditorQuerySuffix' => '',
        ]));

        $response->assertOk();
        $response->assertSee('Cell Editor — 14.1');
        $response->assertSee('aria-label="Diminuir zoom"', false);
        $response->assertSee('aria-label="Fechar documentos"', false);
        $response->assertSee('aria-label="Ajustar zoom à tela"', false);
        $response->assertSee('Salvar');
    }

    public function test_telas_apresentam_regioes_identificadas(): void
    {
        $this->mock(\App\Contracts\LegacyAuthSessionServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('currentUser')->andReturn(null);
            $mock->shouldReceive('currentChurch')->andReturn(null);
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('availableAdministrations')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(\App\Contracts\LegacyPermissionServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([]);
            $mock->shouldReceive('can')->andReturnFalse();
        });
        $this->mock(\App\Contracts\LegacyNavigationServiceInterface::class, fn ($mock) => $mock->shouldReceive('navigation')->andReturn([]));
        $route = new \Illuminate\Routing\Route('GET', '/', []);
        $route->name('migration.reports.index');
        request()->setRouteResolver(fn () => $route);

        $summary = array_fill_keys([
            'total_geral', 'total_pendentes', 'total_checados', 'total_observacao', 'total_etiqueta',
            'total_alteracoes', 'total_novos', 'total_backup', 'total_checados_observacao',
            'total_checados_etiqueta', 'total_checados_observacao_etiqueta', 'total_editados_checados',
            'total_editados_observacao', 'total_editados_etiqueta', 'total_editados_checados_etiqueta',
            'total_editados_observacao_etiqueta', 'total_editados_checados_observacao',
            'total_editados_checados_observacao_etiqueta',
        ], 0);
        $pages = [
            ['reports.index', ['administrations' => [], 'states' => [], 'churches' => [], 'selectedChurchId' => null, 'reports' => []], 'Relatórios disponíveis'],
            ['reports.position', ['report' => ['planilha' => [], 'resumo' => $summary, 'itens' => []], 'selectedChurchId' => 1], 'Itens da posição'],
            ['reports.show', ['preview' => ['formulario' => '14.1', 'planilha' => [], 'background_image_url' => '', 'total_paginas' => 0, 'style_content' => '', 'paginas' => []], 'selectedChurchId' => 1], 'Pré-visualização do relatório'],
            ['audits.index', ['errors' => new \Illuminate\Support\ViewErrorBag(), 'administrations' => [], 'filters' => ['search' => '', 'module' => '', 'date_from' => '', 'date_to' => ''], 'selectedAdministrationId' => null, 'modules' => [], 'scopeLabel' => 'Todos', 'audits' => collect()], 'Eventos de auditoria'],
            ['migration-dashboard', ['snapshot' => (object) ['modules' => [], 'architectureCounts' => [], 'databaseReachable' => false, 'databaseError' => null]], 'Indicadores rápidos'],
        ];
        foreach ($pages as [$view, $data, $label]) {
            $response = \Illuminate\Testing\TestResponse::fromBaseResponse(response()->view($view, $data));
            $response->assertOk();
            $response->assertSee('aria-label="'.$label.'"', false);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyReportServiceInterface;
use Illuminate\Support\Collection;
use RuntimeException;
use Tests\TestCase;

final class LegacyReportPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            LegacyReportServiceInterface::class,
            new class implements LegacyReportServiceInterface
            {
                public function churchOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                    ]);
                }

                public function listAvailableReports(int $churchId): array
                {
                    return [
                        [
                            'codigo' => '14.1',
                            'titulo' => 'Relatório 14.1',
                            'descricao' => 'Formulário de doação e aquisição de bens',
                            'rota' => '/reports/14.1?comum_id=7',
                            'quantidade' => 3,
                        ],
                    ];
                }

                public function buildReportPreview(int $churchId, string $formulario): array
                {
                    if ($churchId !== 7) {
                        throw new RuntimeException('Igreja não encontrada para gerar o relatório.');
                    }

                    return [
                        'formulario' => '14.1',
                        'planilha' => ['descricao' => 'Central Cuiabá', 'cidade' => 'Cuiabá'],
                        'paginas' => [
                            [
                                'numero' => 1,
                                'html' => '<div class="pixel-root"><div class="a4" data-layout="pixel"><textarea name="n_relatorio">REL-01</textarea></div></div>',
                            ],
                        ],
                        'total_paginas' => 1,
                        'background_image_url' => '/assets/images/reports/secao14/secao14-36.png',
                        'style_content' => '.a4 { width: 1654px; height: 2339px; }',
                        'legacy_change_url' => 'http://localhost/reports/alteracoes',
                    ];
                }

                public function buildChangeHistory(int $churchId, array $filters): array
                {
                    if ($churchId !== 7) {
                        throw new RuntimeException('Igreja não encontrada para abrir o histórico de alterações.');
                    }

                    return [
                        'planilha' => ['descricao' => 'Central Cuiabá', 'cidade' => 'Cuiabá'],
                        'filtros' => [
                            'mostrar_pendentes' => true,
                            'mostrar_checados' => false,
                            'mostrar_observacao' => false,
                            'mostrar_checados_observacao' => false,
                            'mostrar_etiqueta' => false,
                            'mostrar_alteracoes' => true,
                            'mostrar_novos' => false,
                            'dependencia' => null,
                        ],
                        'dependencias' => [
                            ['id' => 3, 'descricao' => 'SALA'],
                        ],
                        'secoes' => [
                            'pendentes' => [
                                'titulo' => 'Pendentes',
                                'itens' => [
                                    ['codigo' => '12-3456 / 0001', 'nome_atual' => 'CADEIRA {SALA}', 'dependencia' => 'SALA'],
                                ],
                                'total' => 1,
                            ],
                            'checados' => ['titulo' => 'Checados', 'itens' => [], 'total' => 0],
                            'observacao' => ['titulo' => 'Com observação', 'itens' => [], 'total' => 0],
                            'checados_observacao' => ['titulo' => 'Checados com observação', 'itens' => [], 'total' => 0],
                            'etiqueta' => ['titulo' => 'Para impressão de etiquetas', 'itens' => [], 'total' => 0],
                            'alteracoes' => [
                                'titulo' => 'Editados',
                                'itens' => [
                                    ['codigo' => '12-3456 / 0002', 'nome_original' => 'MESA {SALA}', 'nome_atual' => 'MESA GRANDE {SALA}'],
                                ],
                                'total' => 1,
                            ],
                            'novos' => ['titulo' => 'Novos', 'itens' => [], 'total' => 0],
                        ],
                        'resumo' => [
                            'total_geral' => 2,
                            'total_pendentes' => 1,
                            'total_checados' => 0,
                            'total_observacao' => 0,
                            'total_checados_observacao' => 0,
                            'total_etiqueta' => 0,
                            'total_alteracoes' => 1,
                            'total_novos' => 0,
                            'total_mostrar' => 2,
                        ],
                    ];
                }
            }
        );
    }

    public function testReportsIndexRendersFilterAndCards(): void
    {
        $response = $this->get(route('migration.reports.index', ['comum_id' => 7]));

        $response->assertOk();
        $response->assertSee('Relatórios 14.x já navegam no novo app.');
        $response->assertSee('Relatório 14.1');
        $response->assertSee('3 item(ns)');
    }

    public function testReportsShowRendersPreview(): void
    {
        $response = $this->get(route('migration.reports.show', ['formulario' => '14.1', 'comum_id' => 7]));

        $response->assertOk();
        $response->assertSee('Relatório 14.1 renderizado no novo app.');
        $response->assertSee('Central Cuiabá');
        $response->assertSee('REL-01', escape: false);
    }

    public function testReportsShowRedirectsWithoutChurch(): void
    {
        $response = $this->get(route('migration.reports.show', ['formulario' => '14.1']));

        $response->assertRedirect(route('migration.reports.index'));
    }

    public function testReportsChangesRendersSummaryAndSections(): void
    {
        $response = $this->get(route('migration.reports.changes', [
            'comum_id' => 7,
            'mostrar_pendentes' => 1,
            'mostrar_alteracoes' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Histórico de alterações no Laravel.');
        $response->assertSee('Central Cuiabá');
        $response->assertSee('Pendentes (1)');
        $response->assertSee('MESA GRANDE {SALA}');
    }

    public function testReportsCellEditorRendersLocalEditorAssets(): void
    {
        $response = $this->get(route('migration.reports.editor', [
            'formulario' => '14.1',
            'comum_id' => 7,
        ]));

        $response->assertOk();
        $response->assertSee('Editor de Células');
        $response->assertSee('Cell Editor');
        $response->assertSee('report-cell-editor.js');
        $response->assertSee('secao14-templates.css');
    }
}

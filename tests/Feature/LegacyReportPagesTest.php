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
                        [
                            'codigo' => 'POS',
                            'titulo' => 'Posição de estoque',
                            'descricao' => 'Backup da posição de verificação e dos itens conferidos',
                            'rota' => '/reports/changes?comum_id=7',
                            'quantidade' => 2,
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

                public function buildVerificationPositionReport(int $churchId): array
                {
                    if ($churchId !== 7) {
                        throw new RuntimeException('Igreja não encontrada para abrir a posição de estoque.');
                    }

                    return [
                        'planilha' => [
                            'codigo' => '12-3456',
                            'descricao' => 'Central Cuiabá',
                            'cidade' => 'Cuiabá',
                            'estado' => 'MT',
                            'administracao' => 'Cuiabá',
                        ],
                        'itens' => [
                            [
                                'codigo' => '12-3456 / 0001',
                                'nome_original' => 'CADEIRA {SALA}',
                                'nome_atual' => 'CADEIRA {SALA}',
                                'dependencia' => 'SALA',
                                'observacoes' => '',
                                'checado' => true,
                                'imprimir_etiqueta' => false,
                                'editado' => false,
                                'novo' => false,
                                'pendente' => false,
                                'status_key' => 'checado',
                                'status_label' => 'Checado',
                            ],
                            [
                                'codigo' => '12-3456 / 0002',
                                'nome_original' => 'MESA {SALA}',
                                'nome_atual' => 'MESA GRANDE {SALA}',
                                'dependencia' => 'SALA',
                                'observacoes' => 'AJUSTE',
                                'checado' => true,
                                'imprimir_etiqueta' => true,
                                'editado' => true,
                                'novo' => false,
                                'pendente' => false,
                                'status_key' => 'editado_checado_observacao_etiqueta',
                                'status_label' => 'Editado, checado, observação e etiqueta',
                            ],
                        ],
                        'resumo' => [
                            'total_geral' => 2,
                            'total_pendentes' => 0,
                            'total_checados' => 2,
                            'total_observacao' => 1,
                            'total_etiqueta' => 1,
                            'total_alteracoes' => 1,
                            'total_novos' => 0,
                            'total_checados_observacao' => 1,
                            'total_checados_etiqueta' => 1,
                            'total_observacao_etiqueta' => 1,
                            'total_checados_observacao_etiqueta' => 1,
                            'total_editados_checados' => 1,
                            'total_editados_observacao' => 1,
                            'total_editados_etiqueta' => 1,
                            'total_editados_checados_etiqueta' => 1,
                            'total_editados_observacao_etiqueta' => 1,
                            'total_editados_checados_observacao' => 1,
                            'total_editados_checados_observacao_etiqueta' => 1,
                            'total_backup' => 2,
                        ],
                        'backup' => [
                            'filename' => 'posicao_verificacao_12-3456_20260421_120000.csv',
                            'content' => "Código;Situação;Descrição original;Descrição atual;Dependência;Checado;Etiqueta;Observação;Editado;Novo\n12-3456 / 0001;Checado;CADEIRA {SALA};CADEIRA {SALA};SALA;1;0;;0;0\n12-3456 / 0002;Editado, checado, observação e etiqueta;MESA {SALA};MESA GRANDE {SALA};SALA;1;1;AJUSTE;1;0\n",
                        ],
                    ];
                }

                public function downloadVerificationPositionCsv(int $churchId): array
                {
                    return [
                        'filename' => 'posicao_verificacao_12-3456_20260421_120000.csv',
                        'content' => "Código;Situação;Descrição original;Descrição atual;Dependência;Checado;Etiqueta;Observação;Editado;Novo\n12-3456 / 0001;Checado;CADEIRA {SALA};CADEIRA {SALA};SALA;1;0;;0;0\n12-3456 / 0002;Editado, checado, observação e etiqueta;MESA {SALA};MESA GRANDE {SALA};SALA;1;1;AJUSTE;1;0\n",
                    ];
                }
            }
        );
    }

    public function testReportsIndexRendersFilterAndCards(): void
    {
        $response = $this->get(route('migration.reports.index', ['comum_id' => 7]));

        $response->assertOk();
        $response->assertSee('Relatórios 14.x e posição de estoque já navegam no novo app.');
        $response->assertSee('Relatório 14.1');
        $response->assertSee('3 item(ns)');
        $response->assertSee('Posição de estoque');
        $response->assertSee('2 item(ns)');
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
        ]));

        $response->assertOk();
        $response->assertSee('Posição de verificação do estoque.');
        $response->assertSee('Central Cuiabá');
        $response->assertSee('Baixar backup CSV');
        $response->assertSee('Editado, checado, observação e etiqueta');
        $response->assertSee('Total de produtos');
    }

    public function testReportsChangesCsvExportDownloadsBackup(): void
    {
        $response = $this->get(route('migration.reports.changes.export', [
            'comum_id' => 7,
        ]));

        $response->assertOk();
        $response->assertDownload('posicao_verificacao_12-3456_20260421_120000.csv');
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
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

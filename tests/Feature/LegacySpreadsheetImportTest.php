<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacySpreadsheetImportServiceInterface;
use App\DTO\SpreadsheetImportUploadData;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacySpreadsheetImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            LegacySpreadsheetImportServiceInterface::class,
            new class implements LegacySpreadsheetImportServiceInterface
            {
                public function responsibleUserOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 9, 'nome' => 'Maria Silva', 'email' => 'MARIA@EXEMPLO.COM'],
                    ]);
                }

                public function churchOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                    ]);
                }

                public function administrationOptions(): Collection
                {
                    return collect([
                        (object) ['id' => 3, 'descricao' => 'Administração Central'],
                    ]);
                }

                public function uploadAndAnalyze(SpreadsheetImportUploadData $data, UploadedFile $file): int
                {
                    return 15;
                }

                public function recentImports(?int $churchId, int $limit = 5): array
                {
                    $imports = [
                        [
                            'id' => 15,
                            'comum_id' => 7,
                            'administracao_id' => 3,
                            'arquivo_nome' => 'Relatório de Bens Imobilizado.csv',
                            'total_linhas' => 2099,
                            'linhas_processadas' => 2099,
                            'linhas_sucesso' => 2099,
                            'linhas_erro' => 0,
                            'porcentagem' => 100,
                            'status' => 'concluida',
                            'iniciada_em' => '2026-04-15 08:30:00',
                            'concluida_em' => '2026-04-15 08:35:00',
                            'created_at' => '2026-04-15 08:30:00',
                            'usuario_responsavel_nome' => 'Maria Silva',
                            'usuario_responsavel_email' => 'maria@exemplo.com',
                            'comum_codigo' => '09-0565',
                            'comum_descricao' => 'Comum 09-0565',
                            'administracao_descricao' => 'Administração Central',
                            'administracao_label' => 'Administração Central',
                            'comum_label' => '09-0565 - Comum 09-0565',
                            'data_referencia' => '2026-04-15 08:35:00',
                        ],
                        [
                            'id' => 14,
                            'comum_id' => 7,
                            'administracao_id' => 3,
                            'arquivo_nome' => 'Importação Antiga.csv',
                            'total_linhas' => 120,
                            'linhas_processadas' => 120,
                            'linhas_sucesso' => 118,
                            'linhas_erro' => 2,
                            'porcentagem' => 100,
                            'status' => 'concluida',
                            'iniciada_em' => '2026-04-14 09:00:00',
                            'concluida_em' => '2026-04-14 09:10:00',
                            'created_at' => '2026-04-14 09:00:00',
                            'usuario_responsavel_nome' => 'Maria Silva',
                            'usuario_responsavel_email' => 'maria@exemplo.com',
                            'comum_codigo' => '09-0565',
                            'comum_descricao' => 'Comum 09-0565',
                            'administracao_descricao' => 'Administração Central',
                            'administracao_label' => 'Administração Central',
                            'comum_label' => '09-0565 - Comum 09-0565',
                            'data_referencia' => '2026-04-14 09:10:00',
                        ],
                        [
                            'id' => 13,
                            'comum_id' => 7,
                            'administracao_id' => 3,
                            'arquivo_nome' => 'Arquivo Antigo 1.csv',
                            'total_linhas' => 88,
                            'linhas_processadas' => 88,
                            'linhas_sucesso' => 88,
                            'linhas_erro' => 0,
                            'porcentagem' => 100,
                            'status' => 'concluida',
                            'iniciada_em' => '2026-04-13 09:00:00',
                            'concluida_em' => '2026-04-13 09:10:00',
                            'created_at' => '2026-04-13 09:00:00',
                            'usuario_responsavel_nome' => 'Maria Silva',
                            'usuario_responsavel_email' => 'maria@exemplo.com',
                            'comum_codigo' => '09-0565',
                            'comum_descricao' => 'Comum 09-0565',
                            'administracao_descricao' => 'Administração Central',
                            'administracao_label' => 'Administração Central',
                            'comum_label' => '09-0565 - Comum 09-0565',
                            'data_referencia' => '2026-04-13 09:10:00',
                        ],
                        [
                            'id' => 12,
                            'comum_id' => 7,
                            'administracao_id' => 3,
                            'arquivo_nome' => 'Arquivo Antigo 2.csv',
                            'total_linhas' => 60,
                            'linhas_processadas' => 60,
                            'linhas_sucesso' => 59,
                            'linhas_erro' => 1,
                            'porcentagem' => 100,
                            'status' => 'erro',
                            'iniciada_em' => '2026-04-12 09:00:00',
                            'concluida_em' => '2026-04-12 09:10:00',
                            'created_at' => '2026-04-12 09:00:00',
                            'usuario_responsavel_nome' => 'Maria Silva',
                            'usuario_responsavel_email' => 'maria@exemplo.com',
                            'comum_codigo' => '09-0565',
                            'comum_descricao' => 'Comum 09-0565',
                            'administracao_descricao' => 'Administração Central',
                            'administracao_label' => 'Administração Central',
                            'comum_label' => '09-0565 - Comum 09-0565',
                            'data_referencia' => '2026-04-12 09:10:00',
                        ],
                        [
                            'id' => 11,
                            'comum_id' => 7,
                            'administracao_id' => 3,
                            'arquivo_nome' => 'Arquivo Antigo 3.csv',
                            'total_linhas' => 45,
                            'linhas_processadas' => 45,
                            'linhas_sucesso' => 45,
                            'linhas_erro' => 0,
                            'porcentagem' => 100,
                            'status' => 'concluida',
                            'iniciada_em' => '2026-04-11 09:00:00',
                            'concluida_em' => '2026-04-11 09:10:00',
                            'created_at' => '2026-04-11 09:00:00',
                            'usuario_responsavel_nome' => 'Maria Silva',
                            'usuario_responsavel_email' => 'maria@exemplo.com',
                            'comum_codigo' => '09-0565',
                            'comum_descricao' => 'Comum 09-0565',
                            'administracao_descricao' => 'Administração Central',
                            'administracao_label' => 'Administração Central',
                            'comum_label' => '09-0565 - Comum 09-0565',
                            'data_referencia' => '2026-04-11 09:10:00',
                        ],
                        [
                            'id' => 10,
                            'comum_id' => 7,
                            'administracao_id' => 3,
                            'arquivo_nome' => 'Arquivo Antigo 4.csv',
                            'total_linhas' => 33,
                            'linhas_processadas' => 33,
                            'linhas_sucesso' => 33,
                            'linhas_erro' => 0,
                            'porcentagem' => 100,
                            'status' => 'concluida',
                            'iniciada_em' => '2026-04-10 09:00:00',
                            'concluida_em' => '2026-04-10 09:10:00',
                            'created_at' => '2026-04-10 09:00:00',
                            'usuario_responsavel_nome' => 'Maria Silva',
                            'usuario_responsavel_email' => 'maria@exemplo.com',
                            'comum_codigo' => '09-0565',
                            'comum_descricao' => 'Comum 09-0565',
                            'administracao_descricao' => 'Administração Central',
                            'administracao_label' => 'Administração Central',
                            'comum_label' => '09-0565 - Comum 09-0565',
                            'data_referencia' => '2026-04-10 09:10:00',
                        ],
                    ];

                    return array_slice($imports, 0, max(1, $limit));
                }

                public function loadPreview(int $importacaoId): ?array
                {
                    return [
                        'importacao' => [
                            'id' => $importacaoId,
                            'arquivo_nome' => 'bens.csv',
                            'administracao_label' => 'Administração Central',
                        ],
                        'analise' => [
                            'resumo' => [
                                'total' => 10,
                                'novos' => 4,
                                'atualizar' => 3,
                                'sem_alteracao' => 2,
                                'exclusoes' => 1,
                            ],
                            'registros' => [
                                [
                                    'linha_csv' => 30,
                                    'status' => 'novo',
                                    'acao_sugerida' => 'importar',
                                    'dados_csv' => [
                                        'codigo_comum' => '12-3456',
                                        'codigo' => '12-3456 / 0001',
                                        'bem' => 'CADEIRA',
                                        'complemento' => 'METALICA',
                                    ],
                                ],
                            ],
                        ],
                        'igrejas_detectadas' => [
                            [
                                'chave' => '12-3456',
                                'codigo' => '12-3456',
                                'descricao' => 'Central Cuiabá',
                                'total' => 1,
                                'novos' => 1,
                                'atualizar' => 0,
                                'sem_alteracao' => 0,
                                'exclusoes' => 0,
                                'erros' => 0,
                                'status' => 'com_alteracoes',
                            ],
                        ],
                        'igrejas_salvas' => ['12-3456' => 'importar'],
                        'status_por_comum' => ['12-3456' => 'novo'],
                    ];
                }

                public function savePreviewActions(int $importacaoId, array $acoes, array $igrejas): array
                {
                    return [
                        'total_salvas' => count($acoes) + count($igrejas),
                        'igrejas_salvas' => count($igrejas),
                    ];
                }

                public function applyBulkPreviewAction(int $importacaoId, string $acao): array
                {
                    return ['acao' => $acao, 'total_aplicadas' => 1];
                }

                public function confirmImport(int $importacaoId, bool $importAll = true, array $acoes = [], array $igrejas = []): array
                {
                    return ['sucesso' => 9, 'erro' => 1];
                }

                public function loadProgress(int $importacaoId): ?array
                {
                    return [
                        'id' => $importacaoId,
                        'usuario_id' => 9,
                        'usuario_responsavel_nome' => 'Maria Silva',
                        'status' => 'aguardando',
                        'total_linhas' => 10,
                        'linhas_processadas' => 0,
                        'linhas_sucesso' => 0,
                        'linhas_erro' => 0,
                        'porcentagem' => 0,
                        'arquivo_nome' => 'bens.csv',
                        'administracao_label' => 'Administração Central',
                        'mensagem_erro' => '',
                    ];
                }

                public function loadImportErrors(?int $churchId, ?int $importacaoId, int $page = 1, int $perPage = 30): array
                {
                    $mode = $importacaoId !== null
                        ? 'importacao'
                        : ($churchId !== null
                            ? 'comum'
                            : (Session::has('administracao_id') ? 'administracao' : 'geral'));

                    return [
                        'modo' => $mode,
                        'comum' => $churchId !== null ? ['id' => $churchId, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'] : null,
                        'administracao' => $mode === 'administracao' ? ['id' => 3, 'descricao' => 'Administração Central'] : null,
                        'importacao' => $importacaoId !== null ? [
                            'id' => $importacaoId,
                            'arquivo_nome' => 'bens.csv',
                            'administracao_label' => 'Administração Central',
                            'usuario_responsavel_nome' => 'Maria Silva',
                            'usuario_responsavel_email' => 'maria@exemplo.com',
                        ] : null,
                        'resumo' => ['pendentes' => 2, 'resolvidos' => 1],
                        'erros' => new LengthAwarePaginator(
                            collect([
                                (object) [
                                    'id' => 99,
                                    'linha_csv' => 30,
                                    'codigo' => '12-3456 / 0001',
                                    'descricao_csv' => 'CADEIRA METALICA',
                                    'bem' => 'CADEIRA',
                                    'complemento' => 'METALICA',
                                    'mensagem_erro' => 'Dependência inválida.',
                                    'resolvido' => 0,
                                ],
                            ]),
                            1,
                            $perPage,
                            $page,
                            ['path' => route('migration.spreadsheets.errors'), 'pageName' => 'pagina'],
                        ),
                    ];
                }

                public function downloadImportErrorsCsv(?int $churchId, ?int $importacaoId): array
                {
                    return [
                        'filename' => 'correcao_erros_imp_15_20260408_120000.csv',
                        'content' => "Codigo;Nome\r\n12-3456 / 0001;CADEIRA METALICA\r\n",
                    ];
                }

                public function markImportErrorResolved(int $errorId, bool $resolved): array
                {
                    return ['pendentes' => $resolved ? 1 : 2, 'resolvido' => $resolved];
                }
            }
        );
    }

    public function testImportPageRendersForm(): void
    {
        $response = $this->get(route('migration.spreadsheets.create'));

        $response->assertOk();
        $response->assertSee('Importe uma planilha para análise.');
        $response->assertSee('Esta importação é por igreja, não por dependência.');
        $response->assertSee('Enviar e analisar');
        $response->assertDontSee('Igreja base');
        $response->assertSee('detecta as igrejas diretamente do CSV');
        $response->assertSee('Importações recentes');
        $response->assertSee('5 registro(s)');
        $response->assertSee('Relatório de Bens Imobilizado.csv');
        $response->assertSee('Administração');
        $response->assertSee('Administração Central');
        $response->assertDontSee('name="usuario_id"');
        $response->assertDontSee('Arquivo Antigo 4.csv');
    }

    public function testStoreRedirectsToPreview(): void
    {
        Session::put('usuario_id', 9);

        $this->mock(
            LegacySpreadsheetImportServiceInterface::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('uploadAndAnalyze')
                    ->once()
                    ->withArgs(fn (SpreadsheetImportUploadData $dto, UploadedFile $file): bool =>
                        $dto->responsibleUserId === 9
                        && $dto->churchId === null
                        && $dto->administrationId === 3
                        && $file->getClientOriginalName() === 'bens.csv'
                    )
                    ->andReturn(15);

                $mock->shouldReceive('churchOptions')->andReturn(collect());
                $mock->shouldReceive('administrationOptions')->andReturn(collect());
            }
        );

        $response = $this->post(route('migration.spreadsheets.store'), [
                'administracao_id' => 3,
                'arquivo_csv' => UploadedFile::fake()->create('bens.csv', 12, 'text/csv'),
            ]);

        $response->assertRedirect(route('migration.spreadsheets.preview', ['importacao' => 15]));
    }

    public function testPreviewPageRendersSummary(): void
    {
        $response = $this->get(route('migration.spreadsheets.preview', ['importacao' => 15]));

        $response->assertOk();
        $response->assertSee('Escolha as igrejas que devem entrar na importação.');
        $response->assertSee('Importação por dependência não é suportada.');
        $response->assertSee('Central Cuiabá');
        $response->assertSee('Administração: Administração Central');
        $response->assertSee('Confirmar igrejas selecionadas');
        $response->assertDontSee('bens.csv');
        $response->assertDontSee('CADEIRA METALICA');
        $response->assertDontSee('Salvar ações');
        $response->assertDontSee('Importar tudo');
    }

    public function testConfirmRedirectsToProductsWithResultMessage(): void
    {
        $response = $this->post(route('migration.spreadsheets.confirm', ['importacao' => 15]));

        $response->assertRedirect(route('migration.spreadsheets.processing', ['importacao' => 15]));
    }

    public function testPreviewActionsEndpointReturnsJson(): void
    {
        $response = $this->postJson(route('migration.spreadsheets.preview.actions', ['importacao' => 15]), [
            'igrejas' => ['12-3456' => 'importar'],
        ]);

        $response->assertOk();
        $response->assertJson(['sucesso' => true, 'total_salvas' => 1, 'igrejas_salvas' => 1]);
    }

    public function testLegacyPreviewActionsEndpointReturnsJson(): void
    {
        $response = $this->postJson('/spreadsheets/preview/save-actions', [
            'importacao_id' => 15,
            'acoes' => ['30' => 'importar'],
            'igrejas' => ['12-3456' => 'importar'],
        ]);

        $response->assertOk();
        $response->assertJson(['sucesso' => true, 'total_salvas' => 2, 'igrejas_salvas' => 1]);
    }

    public function testPreviewBulkEndpointReturnsJson(): void
    {
        $response = $this->postJson(route('migration.spreadsheets.preview.bulk', ['importacao' => 15]), [
            'acao' => 'importar',
        ]);

        $response->assertOk();
        $response->assertJson(['sucesso' => true, 'acao' => 'importar', 'total_aplicadas' => 1]);
    }

    public function testLegacyPreviewBulkEndpointReturnsJson(): void
    {
        $response = $this->postJson('/spreadsheets/preview/bulk-action', [
            'importacao_id' => 15,
            'acao' => 'importar',
        ]);

        $response->assertOk();
        $response->assertJson(['sucesso' => true, 'acao' => 'importar', 'total_aplicadas' => 1]);
    }

    public function testLegacyConfirmRedirectsToProcessing(): void
    {
        $response = $this->post('/spreadsheets/confirm', [
            'importacao_id' => 15,
        ]);

        $response->assertRedirect(route('migration.spreadsheets.processing', ['importacao' => 15]));
    }

    public function testProcessingPageRendersProgressShell(): void
    {
        $response = $this->get(route('migration.spreadsheets.processing', ['importacao' => 15]));

        $response->assertOk();
        $response->assertSee('Importação em processamento.');
        $response->assertSee('bens.csv');
        $response->assertSee('Administração Central');
        $response->assertSee('id="arquivo-nome"', false);
        $response->assertSee('id="linhas-sucesso"', false);
    }

    public function testErrorsPageRendersAdministrationScope(): void
    {
        $response = $this->withSession([
            'administracao_id' => 3,
        ])->get(route('migration.spreadsheets.errors'));

        $response->assertOk();
        $response->assertSee('Administração: Administração Central');
        $response->assertSee('Baixar CSV de correção');
        $response->assertSee('Itens com falha');
    }

    public function testStartProcessingEndpointReturnsJson(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->post(route('migration.spreadsheets.start', ['importacao' => 15]));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function testProgressEndpointReturnsJson(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get(route('migration.spreadsheets.progress', ['importacao' => 15]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'aguardando',
            'arquivo_nome' => 'bens.csv',
        ]);
    }

    public function testLegacyProcessFileEndpointReturnsLegacyCompatiblePayload(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->post('/spreadsheets/process-file', [
            'id' => 15,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Importação processada com sucesso.',
        ]);
    }

    public function testLegacyProgressEndpointReturnsLegacyCompatiblePayload(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get('/spreadsheets/api/progress?id=15');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'aguardando',
            'arquivo_nome' => 'bens.csv',
        ]);
    }

    public function testErrorsPageRendersRows(): void
    {
        $response = $this->get(route('migration.spreadsheets.errors', ['importacao_id' => 15]));

        $response->assertOk();
        $response->assertSee('Erros de importação podem ser tratados aqui.');
        $response->assertSee('Administração Central');
        $response->assertSee('Dependência inválida.');
        $response->assertSee('Baixar CSV de correção');
    }

    public function testLegacyErrorsPageRendersRows(): void
    {
        $response = $this->get('/spreadsheets/import-errors?importacao_id=15');

        $response->assertOk();
        $response->assertSee('Erros de importação podem ser tratados aqui.');
        $response->assertSee('Administração Central');
        $response->assertSee('Dependência inválida.');
        $response->assertSee('Baixar CSV de correção');
    }

    public function testErrorsDownloadReturnsCsv(): void
    {
        $response = $this->get(route('migration.spreadsheets.errors.download', ['importacao_id' => 15]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function testLegacyErrorsDownloadReturnsCsv(): void
    {
        $response = $this->get('/spreadsheets/import-errors/download?importacao_id=15');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function testResolveErrorEndpointReturnsJson(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Administrador',
            'usuario_email' => 'ADMIN@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ])->postJson(route('migration.spreadsheets.errors.resolve', ['erro' => 99]), [
            'resolvido' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['sucesso' => true, 'pendentes' => 1, 'resolvido' => true]);
    }

    public function testLegacyResolveErrorEndpointReturnsJson(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Administrador',
            'usuario_email' => 'ADMIN@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ])->postJson('/spreadsheets/import-errors/resolver', [
            'erro_id' => 99,
            'resolvido' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['sucesso' => true, 'pendentes' => 1, 'resolvido' => true]);
    }

    public function testStoreRejectsMissingFields(): void
    {
        $response = $this->from(route('migration.spreadsheets.create'))
            ->post(route('migration.spreadsheets.store'), []);

        $response->assertRedirect(route('migration.spreadsheets.create'));
        $response->assertSessionHasErrors(['administracao_id', 'arquivo_csv']);
    }
}

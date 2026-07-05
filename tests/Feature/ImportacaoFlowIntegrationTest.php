<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacySpreadsheetImportServiceInterface;
use App\DTO\SpreadsheetImportUploadData;
use App\Services\AnalysisPersistenceService;
use App\Services\ImportacaoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Teste de integração do fluxo completo de importação de planilha.
 *
 * @note Verificação de conflito de autoload (Jul/2026): o nome desta classe
 *       é único em todo o código-fonte e vendor. Não há outra classe com o
 *       nome 'ImportacaoFlowIntegrationTest' em nenhum pacote ou diretório, e
 *       o classmap do Composer contém apenas uma entrada. Um eventual erro de
 *       "constant visibility" NÃO é causado por conflito de autoload nesta
 *       classe. (Investigação: task t_c531dad4)
 *
 * Cenário:
 *  1. Upload de CSV com 2 produtos novos (linhas 30 e 31)
 *  2. Preview da análise
 *  3. Salvar ações do preview (marcar 2 registros como 'importar')
 *  4. Confirmar e iniciar processamento
 *  5. Verificar persistência dos dados na tabela produtos
 *
 * Utiliza um test double de LegacySpreadsheetImportServiceInterface que
 * persiste dados reais no banco SQLite :memory:, permitindo assertions
 * de banco no final do fluxo.
 */
final class ImportacaoFlowIntegrationTest extends TestCase
{
    public const USER_ID = 42;
    public const ADMIN_ID = 1;
    public const CHURCH_ID = 100;
    public const CHURCH_CODE = '09-0565';
    public const CHURCH_DESC = 'Igreja Matriz Central';

    protected function setUp(): void
    {
        parent::setUp();

        $this->criarTabelas();
        $this->semearDados();

        $this->app->instance(
            LegacySpreadsheetImportServiceInterface::class,
            $this->criarTestDouble(),
        );
    }

    // ──────────────────────────────────────────────
    //  Criação das tabelas (SQLite-compatible)
    // ──────────────────────────────────────────────

    private function criarTabelas(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS administracoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao VARCHAR(255) NOT NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS comums (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo VARCHAR(50) NOT NULL UNIQUE,
            cnpj VARCHAR(255) DEFAULT NULL,
            descricao VARCHAR(255) DEFAULT NULL,
            administracao VARCHAR(255) DEFAULT NULL,
            cidade VARCHAR(255) DEFAULT NULL,
            setor VARCHAR(255) DEFAULT NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            ativo TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            administracao_id INTEGER DEFAULT NULL,
            comum_id INTEGER DEFAULT NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS tipos_bens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo INTEGER NOT NULL,
            descricao VARCHAR(255) NOT NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS importacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            comum_id INTEGER DEFAULT NULL,
            administracao_id INTEGER DEFAULT NULL,
            arquivo_nome VARCHAR(255) NOT NULL,
            arquivo_caminho VARCHAR(500) NOT NULL,
            total_linhas INTEGER DEFAULT 0,
            linhas_processadas INTEGER DEFAULT 0,
            linhas_sucesso INTEGER DEFAULT 0,
            linhas_erro INTEGER DEFAULT 0,
            porcentagem DECIMAL(5,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT \'aguardando\',
            mensagem_erro TEXT DEFAULT NULL,
            iniciada_em TIMESTAMP DEFAULT NULL,
            concluida_em TIMESTAMP DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS import_erros (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            importacao_id INTEGER NOT NULL,
            linha_csv VARCHAR(50) DEFAULT NULL,
            codigo VARCHAR(50) DEFAULT NULL,
            localidade VARCHAR(255) DEFAULT NULL,
            codigo_comum VARCHAR(50) DEFAULT NULL,
            descricao_csv TEXT DEFAULT NULL,
            bem VARCHAR(255) DEFAULT NULL,
            complemento VARCHAR(255) DEFAULT NULL,
            dependencia VARCHAR(255) DEFAULT NULL,
            mensagem_erro TEXT NOT NULL,
            resolvido TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS dependencias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            comum_id INTEGER DEFAULT NULL,
            descricao VARCHAR(255) NOT NULL,
            UNIQUE (comum_id, descricao)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS produtos (
            comum_id INTEGER NOT NULL,
            id_produto INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo VARCHAR(50) DEFAULT NULL,
            tipo_bem_id INTEGER DEFAULT 99,
            bem TEXT NOT NULL,
            complemento TEXT DEFAULT NULL,
            dependencia_id INTEGER DEFAULT 0,
            editado_tipo_bem_id INTEGER DEFAULT NULL,
            editado_bem VARCHAR(255) DEFAULT NULL,
            editado_complemento VARCHAR(255) DEFAULT NULL,
            editado_dependencia_id INTEGER DEFAULT NULL,
            novo INTEGER DEFAULT 0,
            importado TINYINT DEFAULT 0,
            checado INTEGER DEFAULT 0,
            editado INTEGER DEFAULT 0,
            imprimir_etiqueta INTEGER DEFAULT 0,
            imprimir_14_1 INTEGER DEFAULT 0,
            observacao VARCHAR(255) DEFAULT \'\',
            ativo INTEGER DEFAULT 1
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS configuracoes (
            posicao_data VARCHAR(255) DEFAULT \'D13\',
            pulo_linhas VARCHAR(255) DEFAULT \'25\',
            mapeamento_colunas VARCHAR(255) DEFAULT \'codigo=A;complemento=D;dependencia=P;localidade=K\',
            data_importacao DATE DEFAULT NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS import_job_processed (
            job_id VARCHAR(128) NOT NULL,
            id_produto INTEGER NOT NULL,
            comum_id INTEGER NOT NULL,
            PRIMARY KEY (job_id, id_produto)
        )');
    }

    // ──────────────────────────────────────────────
    //  Dados de referência
    // ──────────────────────────────────────────────

    private function semearDados(): void
    {
        // Administração
        DB::table('administracoes')->insert([
            'id' => self::ADMIN_ID,
            'descricao' => 'Administração Central',
        ]);

        // Igreja (comum)
        DB::table('comums')->insert([
            'id' => self::CHURCH_ID,
            'codigo' => self::CHURCH_CODE,
            'descricao' => self::CHURCH_DESC,
        ]);

        // Usuário responsável
        DB::table('usuarios')->insert([
            'id' => self::USER_ID,
            'nome' => 'Maria Silva',
            'email' => 'maria@exemplo.com',
            'senha' => bcrypt('senha123'),
            'ativo' => 1,
            'administracao_id' => self::ADMIN_ID,
        ]);

        // Tipos de bens necessários
        DB::table('tipos_bens')->insert([
            ['codigo' => 4, 'descricao' => 'CADEIRA'],
            ['codigo' => 7, 'descricao' => 'MESA'],
            ['codigo' => 99, 'descricao' => 'DIVERSOS'],
        ]);
    }

    // ──────────────────────────────────────────────
    //  Test double que persiste no banco
    // ──────────────────────────────────────────────

    private function criarTestDouble(): LegacySpreadsheetImportServiceInterface
    {
        $churchCode = self::CHURCH_CODE;
        $churchId = self::CHURCH_ID;
        $churchDesc = self::CHURCH_DESC;
        $userId = self::USER_ID;
        $adminId = self::ADMIN_ID;

        return new class($churchCode, $churchId, $churchDesc, $userId, $adminId) implements LegacySpreadsheetImportServiceInterface
        {
            private AnalysisPersistenceService $analysisStorage;
            private int $nextImportacaoId = 1000;

            public function __construct(
                private readonly string $churchCode,
                private readonly int $churchId,
                private readonly string $churchDesc,
                private readonly int $userId,
                private readonly int $adminId,
            ) {}

            public function previewActionsKey(int $importacaoId): string
            {
                return 'preview-actions-' . $importacaoId;
            }

            public function previewChurchesKey(int $importacaoId): string
            {
                return 'preview-churches-' . $importacaoId;
            }

            public function previewDependenciesKey(int $importacaoId): string
            {
                return 'preview-dependencies-' . $importacaoId;
            }

            public function responsibleUserOptions(): Collection
            {
                return collect([
                    (object) ['id' => $this->userId, 'nome' => 'Maria Silva', 'email' => 'maria@exemplo.com'],
                ]);
            }

            public function churchOptions(): Collection
            {
                return collect([
                    (object) ['id' => $this->churchId, 'codigo' => $this->churchCode, 'descricao' => $this->churchDesc],
                ]);
            }

            public function administrationOptions(): Collection
            {
                return collect([
                    (object) ['id' => $this->adminId, 'descricao' => 'Administração Central'],
                ]);
            }

            public function uploadAndAnalyze(SpreadsheetImportUploadData $data, UploadedFile $file): int
            {
                $importacaoId = DB::table('importacoes')->insertGetId([
                    'usuario_id' => $data->responsibleUserId,
                    'comum_id' => $data->churchId,
                    'administracao_id' => $data->administrationId,
                    'arquivo_nome' => $file->getClientOriginalName(),
                    'arquivo_caminho' => $file->getRealPath() ?: '/tmp/test/' . $file->getClientOriginalName(),
                    'total_linhas' => 2,
                    'status' => 'aguardando',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Simula análise: 2 registros novos
                $analise = [
                    'resumo' => [
                        'total' => 2,
                        'novos' => 2,
                        'atualizar' => 0,
                        'sem_alteracao' => 0,
                        'exclusoes' => 0,
                    ],
                    'registros' => [
                        [
                            'linha_csv' => 30,
                            'status' => 'novo',
                            'acao_sugerida' => 'importar',
                            'dados_csv' => [
                                'codigo_comum' => $this->churchCode,
                                'codigo' => '09-0565 / 0001',
                                'tipo_bem_codigo' => '4',
                                'bem' => 'CADEIRA',
                                'complemento' => 'METALICA',
                                'dependencia_descricao' => 'SALA 01',
                                'localidade' => $this->churchCode,
                            ],
                        ],
                        [
                            'linha_csv' => 31,
                            'status' => 'novo',
                            'acao_sugerida' => 'importar',
                            'dados_csv' => [
                                'codigo_comum' => $this->churchCode,
                                'codigo' => '09-0565 / 0002',
                                'tipo_bem_codigo' => '7',
                                'bem' => 'MESA',
                                'complemento' => 'ESCRITORIO',
                                'dependencia_descricao' => 'SALA 01',
                                'localidade' => $this->churchCode,
                            ],
                        ],
                    ],
                    'igrejas_detectadas' => [
                        [
                            'chave' => $this->churchCode,
                            'codigo' => $this->churchCode,
                            'descricao' => $this->churchDesc,
                            'total' => 2,
                            'novos' => 2,
                            'atualizar' => 0,
                            'sem_alteracao' => 0,
                            'exclusoes' => 0,
                            'erros' => 0,
                            'status' => 'com_alteracoes',
                        ],
                    ],
                ];

                // Salva análise em disco (mesmo mecanismo do serviço real)
                $this->analysisStorage = new AnalysisPersistenceService();
                $this->analysisStorage->salvarAnalise($importacaoId, $analise);

                return $importacaoId;
            }

            public function recentImports(?int $churchId, int $limit = 5): array
            {
                return [];
            }

            public function loadPreview(int $importacaoId): ?array
            {
                $importacao = DB::table('importacoes')->where('id', $importacaoId)->first();
                if ($importacao === null) {
                    return null;
                }

                $this->analysisStorage = new AnalysisPersistenceService();
                $analise = $this->analysisStorage->carregarAnalise($importacaoId);
                if ($analise === null) {
                    return null;
                }

                $churchDescription = DB::table('comums')
                    ->where('codigo', $this->churchCode)
                    ->value('descricao') ?? '';

                $igrejasDetectadas = [
                    [
                        'chave' => $this->churchCode,
                        'codigo' => $this->churchCode,
                        'descricao' => $churchDescription,
                        'total' => 2,
                        'novos' => 2,
                        'atualizar' => 0,
                        'sem_alteracao' => 0,
                        'exclusoes' => 0,
                        'erros' => 0,
                        'status' => 'com_alteracoes',
                    ],
                ];

                return [
                    'importacao' => [
                        'id' => $importacaoId,
                        'arquivo_nome' => $importacao->arquivo_nome,
                        'administracao_label' => 'Administração Central',
                    ],
                    'analise' => $analise,
                    'acoes_salvas' => Session::get($this->previewActionsKey($importacaoId), []),
                    'igrejas_salvas' => Session::get($this->previewChurchesKey($importacaoId), []),
                    'dependencias_salvas' => Session::get($this->previewDependenciesKey($importacaoId), []),
                    'status_por_comum' => [$this->churchCode => 'novo'],
                    'igrejas_detectadas' => $igrejasDetectadas,
                ];
            }

            public function savePreviewActions(int $importacaoId, array $acoes, array $igrejas, array $dependencias = []): array
            {
                $savedActions = Session::get($this->previewActionsKey($importacaoId), []);
                foreach ($acoes as $line => $action) {
                    if (in_array($action, ['importar', 'pular', 'excluir'], true)) {
                        $savedActions[(string) $line] = $action;
                    }
                }

                $savedChurches = Session::get($this->previewChurchesKey($importacaoId), []);
                foreach ($igrejas as $churchCode => $action) {
                    if (in_array($action, ['', 'importar', 'pular', 'personalizado'], true)) {
                        $savedChurches[(string) $churchCode] = $action;
                    }
                }

                $savedDependencies = Session::get($this->previewDependenciesKey($importacaoId), []);
                foreach ($dependencias as $churchDepKey => $action) {
                    if (in_array($action, ['', 'importar', 'pular'], true)) {
                        $savedDependencies[(string) $churchDepKey] = $action;
                    }
                }

                Session::put($this->previewActionsKey($importacaoId), $savedActions);
                Session::put($this->previewChurchesKey($importacaoId), $savedChurches);
                Session::put($this->previewDependenciesKey($importacaoId), $savedDependencies);

                return [
                    'total_salvas' => count($savedActions) + count($savedChurches) + count($savedDependencies),
                    'igrejas_salvas' => count($savedChurches),
                    'dependencias_salvas' => count($savedDependencies),
                ];
            }

            public function applyBulkPreviewAction(int $importacaoId, string $acao): array
            {
                return ['acao' => $acao, 'total_aplicadas' => 2];
            }

            /**
             * Processa a importação: insere produtos na tabela real.
             */
            public function confirmImport(int $importacaoId, bool $importAll = true, array $acoes = [], array $igrejas = [], array $dependencias = []): array
            {
                $importacao = DB::table('importacoes')->where('id', $importacaoId)->first();
                if ($importacao === null) {
                    return ['sucesso' => 0, 'erro' => 0, 'status' => 'erro'];
                }

                // Carrega análise
                $this->analysisStorage = new AnalysisPersistenceService();
                $analise = $this->analysisStorage->carregarAnalise($importacaoId);
                if ($analise === null) {
                    return ['sucesso' => 0, 'erro' => 0, 'status' => 'erro'];
                }

                // Marca como processando
                DB::table('importacoes')->where('id', $importacaoId)->update([
                    'status' => 'processando',
                    'iniciada_em' => now(),
                ]);

                $sucesso = 0;
                $erro = 0;

                DB::beginTransaction();
                try {
                    foreach ($analise['registros'] as $registro) {
                        $dados = $registro['dados_csv'];

                        // Busca ou cria dependência
                        $depDescricao = trim(strtoupper($dados['dependencia_descricao'] ?? 'SEM DEPENDÊNCIA'));
                        $depId = DB::table('dependencias')
                            ->where('comum_id', $this->churchId)
                            ->where('descricao', $depDescricao)
                            ->value('id');

                        if ($depId === null) {
                            $depId = DB::table('dependencias')->insertGetId([
                                'comum_id' => $this->churchId,
                                'descricao' => $depDescricao,
                            ]);
                        }

                        // Resolve tipo_bem_id
                        $tipoBemCodigo = (int) ($dados['tipo_bem_codigo'] ?? 99);
                        $tipoBemId = DB::table('tipos_bens')
                            ->where('codigo', $tipoBemCodigo)
                            ->value('id') ?? 1;

                        // Church ID resolve pelo código
                        $churchRow = DB::table('comums')
                            ->where('codigo', $dados['codigo_comum'] ?? $this->churchCode)
                            ->first();
                        $comumId = $churchRow ? (int) $churchRow->id : $this->churchId;

                        DB::table('produtos')->insert([
                            'comum_id' => $comumId,
                            'codigo' => $dados['codigo'],
                            'tipo_bem_id' => $tipoBemId,
                            'bem' => $dados['bem'],
                            'complemento' => $dados['complemento'] ?? '',
                            'dependencia_id' => $depId,
                            'importado' => 1,
                            'novo' => 0,
                            'checado' => 0,
                            'editado' => 0,
                            'imprimir_etiqueta' => 0,
                            'imprimir_14_1' => 0,
                            'ativo' => 1,
                            'observacao' => '',
                        ]);
                        $sucesso++;
                    }

                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $erro = count($analise['registros']);
                }

                // Finaliza
                DB::table('importacoes')->where('id', $importacaoId)->update([
                    'status' => 'concluida',
                    'linhas_processadas' => $sucesso + $erro,
                    'linhas_sucesso' => $sucesso,
                    'linhas_erro' => $erro,
                    'porcentagem' => 100.0,
                    'concluida_em' => now(),
                ]);

                // Limpa análise e sessão
                $this->analysisStorage->limparAnalise($importacaoId);
                Session::forget($this->previewActionsKey($importacaoId));
                Session::forget($this->previewChurchesKey($importacaoId));
                Session::forget($this->previewDependenciesKey($importacaoId));

                return [
                    'sucesso' => $sucesso,
                    'erro' => $erro,
                    'status' => 'concluida',
                    'reutilizada' => false,
                ];
            }

            public function loadProgress(int $importacaoId): ?array
            {
                $row = DB::table('importacoes')->where('id', $importacaoId)->first();
                if ($row === null) {
                    return null;
                }

                return [
                    'id' => (int) $row->id,
                    'usuario_id' => (int) $row->usuario_id,
                    'usuario_responsavel_nome' => 'Maria Silva',
                    'status' => $row->status,
                    'total_linhas' => (int) ($row->total_linhas ?? 0),
                    'linhas_processadas' => (int) ($row->linhas_processadas ?? 0),
                    'linhas_sucesso' => (int) ($row->linhas_sucesso ?? 0),
                    'linhas_erro' => (int) ($row->linhas_erro ?? 0),
                    'porcentagem' => (float) ($row->porcentagem ?? 0),
                    'arquivo_nome' => (string) ($row->arquivo_nome ?? ''),
                    'administracao_label' => 'Administração Central',
                    'mensagem_erro' => (string) ($row->mensagem_erro ?? ''),
                ];
            }

            public function loadImportErrors(?int $churchId, ?int $importacaoId, int $page = 1, int $perPage = 30): array
            {
                $mode = $importacaoId !== null ? 'importacao' : 'geral';

                $erros = DB::table('import_erros')
                    ->when($importacaoId !== null, fn ($q) => $q->where('importacao_id', $importacaoId))
                    ->get()
                    ->map(fn ($e) => (object) [
                        'id' => (int) $e->id,
                        'linha_csv' => $e->linha_csv,
                        'codigo' => $e->codigo,
                        'descricao_csv' => $e->descricao_csv,
                        'bem' => $e->bem,
                        'complemento' => $e->complemento,
                        'mensagem_erro' => $e->mensagem_erro,
                        'resolvido' => (int) ($e->resolvido ?? 0),
                    ])
                    ->values()
                    ->all();

                return [
                    'modo' => $mode,
                    'comum' => $churchId !== null ? ['id' => $churchId, 'codigo' => $this->churchCode, 'descricao' => $this->churchDesc] : null,
                    'administracao' => ['id' => $this->adminId, 'descricao' => 'Administração Central'],
                    'importacao' => $importacaoId !== null ? [
                        'id' => $importacaoId,
                        'arquivo_nome' => 'bens.csv',
                        'administracao_label' => 'Administração Central',
                        'usuario_responsavel_nome' => 'Maria Silva',
                        'usuario_responsavel_email' => 'maria@exemplo.com',
                    ] : null,
                    'resumo' => ['pendentes' => 0, 'resolvidos' => 0],
                    'erros' => new LengthAwarePaginator(
                        collect($erros),
                        count($erros),
                        $perPage,
                        $page,
                        ['path' => '/spreadsheets/errors', 'pageName' => 'pagina'],
                    ),
                ];
            }

            public function downloadImportErrorsCsv(?int $churchId, ?int $importacaoId): array
            {
                return [
                    'filename' => 'erros_imp_' . ($importacaoId ?? 0) . '.csv',
                    'content' => "Codigo;Nome\r\n",
                ];
            }

            public function markImportErrorResolved(int $errorId, bool $resolved): array
            {
                return ['pendentes' => 0, 'resolvido' => $resolved];
            }
        };
    }

    // ──────────────────────────────────────────────
    //  TESTE PRINCIPAL: Fluxo completo
    // ──────────────────────────────────────────────

    public function testFluxoCompletoImportacao(): void
    {
        // ── Sessão autenticada ──
        $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => self::USER_ID,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'maria@exemplo.com',
            'comum_id' => self::CHURCH_ID,
            'is_admin' => false,
        ]);

        // ══════════════════════════════════════════
        // PASSO 1: Upload do CSV
        // ══════════════════════════════════════════
        $csvContent = "codigo;bem;complemento;dependencia;localidade\n"
            . "09-0565 / 0001;CADEIRA;METALICA;SALA 01;09-0565\n"
            . "09-0565 / 0002;MESA;ESCRITORIO;SALA 01;09-0565\n";

        $response = $this->post(route('migration.spreadsheets.store'), [
            'administracao_id' => self::ADMIN_ID,
            'arquivo_csv' => UploadedFile::fake()->createWithContent('bens.csv', $csvContent),
        ]);

        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('spreadsheets/preview/', $redirectUrl);

        // Extrai o ID da importação da URL
        preg_match('/preview\/(\d+)/', $redirectUrl, $matches);
        $this->assertNotEmpty($matches, 'Deveria ter ID da importação na URL de redirect');
        $importacaoId = (int) $matches[1];

        // Asserts no banco: importação foi criada
        $importacao = DB::table('importacoes')->find($importacaoId);
        $this->assertNotNull($importacao, 'Importação deve existir no banco');
        $this->assertSame((string) self::USER_ID, (string) $importacao->usuario_id);
        $this->assertSame('bens.csv', $importacao->arquivo_nome);
        $this->assertSame('aguardando', $importacao->status);

        // Análise foi salva em disco
        $analysisService = new AnalysisPersistenceService();
        $this->assertTrue($analysisService->existeAnalise($importacaoId), 'Análise deve existir em disco');

        // ══════════════════════════════════════════
        // PASSO 2: Preview da análise
        // ══════════════════════════════════════════
        $response = $this->get(route('migration.spreadsheets.preview', ['importacao' => $importacaoId]));

        $response->assertOk();
        $response->assertSee('Escolha as igrejas que devem entrar na importação.');
        $response->assertSee(self::CHURCH_DESC);
        $response->assertSee('Administração Central');

        // ══════════════════════════════════════════
        // PASSO 3: Salvar ações do preview
        // ══════════════════════════════════════════
        $response = $this->postJson(route('migration.spreadsheets.preview.actions', ['importacao' => $importacaoId]), [
            'acoes' => ['30' => 'importar', '31' => 'importar'],
            'igrejas' => [self::CHURCH_CODE => 'importar'],
            'dependencias' => [self::CHURCH_CODE . ':SALA 01' => 'importar'],
        ]);

        $response->assertOk();
        $response->assertJson([
            'sucesso' => true,
            'total_salvas' => 4,
            'igrejas_salvas' => 1,
        ]);

        // ══════════════════════════════════════════
        // PASSO 4: Confirmar (redirect para processing)
        // ══════════════════════════════════════════
        $response = $this->post(route('migration.spreadsheets.confirm', ['importacao' => $importacaoId]), [
            'importar_tudo' => true,
        ]);

        $response->assertRedirect(route('migration.spreadsheets.processing', ['importacao' => $importacaoId]));

        // ══════════════════════════════════════════
        // PASSO 5: Iniciar processamento
        // ══════════════════════════════════════════
        $response = $this->withSession([
            'importacao_confirm_options_' . $importacaoId => [
                'importar_tudo' => true,
                'acoes' => ['30' => 'importar', '31' => 'importar'],
                'igrejas' => [self::CHURCH_CODE => 'importar'],
            ],
        ])->post(route('migration.spreadsheets.start', ['importacao' => $importacaoId]));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // ══════════════════════════════════════════
        // VERIFICAÇÕES FINAIS: Dados persistidos
        // ══════════════════════════════════════════
        // 1. Importação concluída no banco
        $importacaoAtualizada = DB::table('importacoes')->find($importacaoId);
        $this->assertSame('concluida', $importacaoAtualizada->status);
        $this->assertSame(2, (int) $importacaoAtualizada->linhas_sucesso);
        $this->assertSame(2, (int) $importacaoAtualizada->linhas_processadas);
        $this->assertSame(0, (int) $importacaoAtualizada->linhas_erro);

        // 2. Produtos foram criados
        $produtos = DB::table('produtos')
            ->where('comum_id', self::CHURCH_ID)
            ->orderBy('codigo')
            ->get();

        $this->assertCount(2, $produtos, 'Devem existir 2 produtos criados');

        $this->assertSame('09-0565 / 0001', $produtos[0]->codigo);
        $this->assertSame('CADEIRA', $produtos[0]->bem);
        $this->assertSame('METALICA', $produtos[0]->complemento);
        $this->assertSame(1, (int) $produtos[0]->importado);
        $this->assertSame(1, (int) $produtos[0]->ativo);

        $this->assertSame('09-0565 / 0002', $produtos[1]->codigo);
        $this->assertSame('MESA', $produtos[1]->bem);
        $this->assertSame('ESCRITORIO', $produtos[1]->complemento);
        $this->assertSame(1, (int) $produtos[1]->importado);
        $this->assertSame(1, (int) $produtos[1]->ativo);

        // 3. Dependência foi criada
        $dependencia = DB::table('dependencias')
            ->where('comum_id', self::CHURCH_ID)
            ->where('descricao', 'SALA 01')
            ->first();
        $this->assertNotNull($dependencia, 'Dependência SALA 01 deve existir');

        // 4. Análise em disco foi limpa após processamento
        $this->assertFalse($analysisService->existeAnalise($importacaoId), 'Análise deve ser removida após conclusão');

        // 5. Sessão de confirmação foi removida
        $this->assertFalse(Session::has('importacao_confirm_options_' . $importacaoId));
    }
}

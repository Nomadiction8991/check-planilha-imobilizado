<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacySpreadsheetImportServiceInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regressão do vazamento cross-tenant na listagem de importações recentes.
 *
 * Camada: feature, ponta a ponta — middleware `legacy.permission:spreadsheets.import`
 * → controller `SpreadsheetImportController::create()` → serviço
 * `LegacySpreadsheetImportService::recentImports()` → view `spreadsheets/import`.
 *
 * Cenário reportado: uma conta legada ATIVA, sem igreja e sem qualquer vínculo
 * administrativo, acessa GET /spreadsheets/import e recebe as importações de
 * outras administrações — nome do arquivo, administração/igreja, progresso,
 * contagem de linhas, nome e e-mail do responsável.
 *
 * Antes da correção, `currentAdministrationScopeIds()` devolvia `null` para esse
 * perfil, e `recentImports(null, 5)` interpretava `null` como acesso global: a
 * query não aplicava nenhum filtro.
 */
final class SpreadsheetImportCrossTenantRegressionTest extends TestCase
{
    private const LEGACY_USER_ID = 71;
    private const ADMIN_A = 10;
    private const ADMIN_B = 20;
    private const CHURCH_A = 30;
    private const CHURCH_B = 40;
    private const IMPORT_A = 100;
    private const IMPORT_B = 200;

    /** @var string Arquivo de outro tenant que não pode aparecer na resposta. */
    private const PRIVATE_FILE_A = 'A-central.csv';

    /** @var string Arquivo do tenant proprietário do cenário. */
    private const PRIVATE_FILE_B = 'B-private.csv';

    protected function setUp(): void
    {
        parent::setUp();

        $this->criarTabelas();
        $this->semearDados();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('importacoes');
        Schema::dropIfExists('comums');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('administracoes');
        Schema::dropIfExists('configuracoes');

        parent::tearDown();
    }

    private function criarTabelas(): void
    {
        Schema::create('administracoes', function (Blueprint $table): void {
            $table->id();
            $table->string('descricao');
            $table->string('cnpj')->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('cidade')->nullable();
        });

        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('senha')->nullable();
            $table->tinyInteger('ativo')->default(1);
            $table->string('tipo', 50)->nullable();
            $table->unsignedBigInteger('administracao_id')->nullable();
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->text('administracoes_permitidas')->nullable();
            $table->text('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('comums', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 50);
            $table->string('cnpj')->nullable();
            $table->string('descricao')->nullable();
            $table->unsignedBigInteger('administracao_id')->nullable();
            $table->string('administracao')->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('cidade')->nullable();
            $table->string('setor')->nullable();
            $table->string('estado_administracao', 2)->nullable();
            $table->string('cidade_administracao')->nullable();
        });

        Schema::create('importacoes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('comum_id')->nullable();
            $table->unsignedBigInteger('administracao_id')->nullable();
            $table->string('arquivo_nome');
            $table->string('arquivo_caminho', 500)->nullable();
            $table->integer('total_linhas')->default(0);
            $table->integer('linhas_processadas')->default(0);
            $table->integer('linhas_sucesso')->default(0);
            $table->integer('linhas_erro')->default(0);
            $table->decimal('porcentagem', 5, 2)->default(0);
            $table->string('status', 50)->default('aguardando');
            $table->text('mensagem_erro')->nullable();
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();
        });

        // Tabela lida pelo composer do layout (ordem do menu).
        Schema::create('configuracoes', function (Blueprint $table): void {
            $table->id();
            $table->text('menu_order')->nullable();
        });
    }

    private function semearDados(): void
    {
        DB::table('administracoes')->insert([
            ['id' => self::ADMIN_A, 'descricao' => 'Administração Centro'],
            ['id' => self::ADMIN_B, 'descricao' => 'Administração Zona Leste'],
        ]);

        DB::table('comums')->insert([
            [
                'id' => self::CHURCH_A,
                'codigo' => '09-0001',
                'descricao' => 'Igreja Central',
                'administracao_id' => self::ADMIN_A,
            ],
            [
                'id' => self::CHURCH_B,
                'codigo' => '09-0002',
                'descricao' => 'Igreja Zona Leste',
                'administracao_id' => self::ADMIN_B,
            ],
        ]);

        // Usuário legado ativo, sem igreja e sem vínculo administrativo.
        // `permissions` com spreadsheets.import concedido explicitamente, para
        // que o passe não dependa do default de configuração.
        DB::table('usuarios')->insert([
            'id' => self::LEGACY_USER_ID,
            'nome' => 'Conta Legada Sem Escopo',
            'email' => 'sem.escopo@exemplo.com',
            'senha' => bcrypt('senha123'),
            'ativo' => 1,
            'tipo' => null,
            'administracao_id' => null,
            'comum_id' => null,
            'administracoes_permitidas' => null,
            'permissions' => json_encode(['spreadsheets.import' => true]),
        ]);

        // Donos das importações, um por administração.
        DB::table('usuarios')->insert([
            [
                'id' => 81,
                'nome' => 'Dono Centro',
                'email' => 'dono.centro@exemplo.com',
                'senha' => bcrypt('senha123'),
                'ativo' => 1,
                'administracao_id' => self::ADMIN_A,
                'comum_id' => self::CHURCH_A,
            ],
            [
                'id' => 82,
                'nome' => 'Dono Zona Leste',
                'email' => 'dono.zona@exemplo.com',
                'senha' => bcrypt('senha123'),
                'ativo' => 1,
                'administracao_id' => self::ADMIN_B,
                'comum_id' => self::CHURCH_B,
            ],
        ]);

        // Duas importações concluídas, de administrações distintas.
        DB::table('importacoes')->insert([
            [
                'id' => self::IMPORT_A,
                'usuario_id' => 81,
                'comum_id' => self::CHURCH_A,
                'administracao_id' => self::ADMIN_A,
                'arquivo_nome' => self::PRIVATE_FILE_A,
                'total_linhas' => 12,
                'linhas_processadas' => 12,
                'linhas_sucesso' => 12,
                'linhas_erro' => 0,
                'porcentagem' => 100,
                'status' => 'concluida',
                'iniciada_em' => '2026-09-30 08:00:00',
                'concluida_em' => '2026-09-30 08:10:00',
            ],
            [
                'id' => self::IMPORT_B,
                'usuario_id' => 82,
                'comum_id' => self::CHURCH_B,
                'administracao_id' => self::ADMIN_B,
                'arquivo_nome' => self::PRIVATE_FILE_B,
                'total_linhas' => 7,
                'linhas_processadas' => 7,
                'linhas_sucesso' => 7,
                'linhas_erro' => 0,
                'porcentagem' => 100,
                'status' => 'concluida',
                'iniciada_em' => '2026-10-01 08:00:00',
                'concluida_em' => '2026-10-01 08:10:00',
            ],
        ]);
    }

    /**
     * Sessão de um usuário legado autenticado, sem igreja, sem administração e
     * sem qualquer vínculo administrativo.
     *
     * @return array<string, mixed>
     */
    private function sessaoUsuarioSemEscopo(): array
    {
        return [
            '_enforce_legacy_auth' => true,
            'usuario_id' => self::LEGACY_USER_ID,
            'usuario_nome' => 'Conta Legada Sem Escopo',
            'usuario_email' => 'sem.escopo@exemplo.com',
            'is_admin' => false,
            'administracao_id' => null,
            'administracoes_permitidas' => [],
            'comum_id' => null,
            'legacy_permissions' => ['spreadsheets.import' => true],
        ];
    }

    public function test_pagina_de_importacao_nao_expoe_importacoes_de_outro_tenant(): void
    {
        $response = $this->withSession($this->sessaoUsuarioSemEscopo())
            ->get(route('migration.spreadsheets.create'));

        $response->assertOk();

        $imports = $response->viewData('recentImports');

        // A coleção de importações precisa estar vazia — nenhuma importação de
        // outra administração pode ser listada para quem não tem escopo.
        self::assertSame([], $imports);

        // Nenhum metadado de outro tenant pode chegar ao HTML da resposta.
        $response->assertDontSee(self::PRIVATE_FILE_B);
        $response->assertDontSee(self::PRIVATE_FILE_A);
        $response->assertDontSee('Administração Zona Leste');
        $response->assertDontSee('Administração Centro');
        $response->assertDontSee('Igreja Zona Leste');
        $response->assertDontSee('Igreja Central');
        $response->assertDontSee('Dono Centro');
        $response->assertDontSee('Dono Zona Leste');
        $response->assertDontSee('dono.centro@exemplo.com');
        $response->assertDontSee('dono.zona@exemplo.com');
    }

    public function test_escopo_vazio_ignora_igreja_selecionada_na_sessao(): void
    {
        // Mesmo com uma igreja de outro tenant na sessão, o escopo administrativo
        // vazio continua significando "nenhuma importação": o filtro de igreja não
        // pode ser usado para atravessar a fronteira entre tenants.
        $response = $this->withSession(array_merge($this->sessaoUsuarioSemEscopo(), [
            'comum_id' => self::CHURCH_B,
        ]))->get(route('migration.spreadsheets.create'));

        $response->assertOk();
        $response->assertDontSee(self::PRIVATE_FILE_B);

        self::assertSame([], $response->viewData('recentImports'));
    }

    public function test_controller_resolve_escopo_antes_de_chamar_o_servico(): void
    {
        // O controller não pode negociar escopo nulo com o serviço: `null`
        // significa acesso irrestrito. Se nem o controller nem o serviço
        // filtrarem, a lista vem preenchida e o teste acusa o vazamento.
        $this->mock(LegacySpreadsheetImportServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('administrationOptions')
                ->andReturn(new Collection());

            $mock->shouldReceive('recentImports')
                ->andReturnUsing(fn (?int $churchId, int $limit = 5): array => [
                    [
                        'id' => self::IMPORT_A,
                        'comum_id' => self::CHURCH_A,
                        'administracao_id' => self::ADMIN_A,
                        'arquivo_nome' => self::PRIVATE_FILE_A,
                        'total_linhas' => 12,
                        'linhas_processadas' => 12,
                        'linhas_sucesso' => 12,
                        'linhas_erro' => 0,
                        'porcentagem' => 100.0,
                        'status' => 'concluida',
                        'iniciada_em' => '2026-09-30 08:00:00',
                        'concluida_em' => '2026-09-30 08:10:00',
                        'created_at' => null,
                        'usuario_responsavel_nome' => 'Dono Centro',
                        'usuario_responsavel_email' => 'dono.centro@exemplo.com',
                        'comum_codigo' => '09-0001',
                        'comum_descricao' => 'Igreja Central',
                        'administracao_descricao' => 'Administração Centro',
                        'administracao_label' => 'Administração Centro',
                        'comum_label' => '09-0001 - Igreja Central',
                        'data_referencia' => '2026-09-30 08:10:00',
                    ],
                ]);
        });

        $response = $this->withSession($this->sessaoUsuarioSemEscopo())
            ->get(route('migration.spreadsheets.create'));

        $response->assertOk();

        // O serviço devolveu dados, mas o usuário sem escopo não pode recebê-los.
        self::assertSame([], $response->viewData('recentImports'));
        $response->assertDontSee(self::PRIVATE_FILE_A);
    }

    public function test_usuario_com_vinculo_administrativo_continua_vendo_importacoes(): void
    {
        // Controle: quem tem escopo administrativo continua vendo suas
        // importações. A correção não pode transformar tudo em lista vazia.
        DB::table('usuarios')->insert([
            'id' => 72,
            'nome' => 'Conta Com Escopo',
            'email' => 'com.escopo@exemplo.com',
            'senha' => bcrypt('senha123'),
            'ativo' => 1,
            'administracao_id' => self::ADMIN_B,
            'comum_id' => self::CHURCH_B,
        ]);

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 72,
            'is_admin' => false,
            'administracao_id' => self::ADMIN_B,
            'administracoes_permitidas' => [],
            'comum_id' => null,
            'legacy_permissions' => ['spreadsheets.import' => true],
        ])->get(route('migration.spreadsheets.create'));

        $response->assertOk();
        $response->assertSee(self::PRIVATE_FILE_B);
        $response->assertDontSee(self::PRIVATE_FILE_A);

        $imports = $response->viewData('recentImports');
        self::assertCount(1, $imports);
        self::assertSame(self::PRIVATE_FILE_B, $imports[0]['arquivo_nome']);
        self::assertSame(self::ADMIN_B, $imports[0]['administracao_id']);
    }
}

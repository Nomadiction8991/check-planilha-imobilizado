<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Regressão: o controller de importação precisa passar um escopo administrativo
 * explícito para o serviço. Um usuário legado autenticado, sem igreja e sem
 * vínculo administrativo, deve receber uma lista vazia — nunca importações de
 * outras administrações.
 */
final class SpreadsheetImportScopeControllerTest extends TestCase
{
    private const USER_ID = 7;
    private const ADMIN_A_ID = 10;
    private const ADMIN_B_ID = 20;
    private const IMPORT_A_ID = 100;
    private const IMPORT_B_ID = 200;

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

        // Tabela usada pelo composer do layout (ordem do menu).
        Schema::create('configuracoes', function (Blueprint $table): void {
            $table->id();
            $table->text('menu_order')->nullable();
        });
    }

    private function semearDados(): void
    {
        DB::table('administracoes')->insert([
            ['id' => self::ADMIN_A_ID, 'descricao' => 'Administração Alfa'],
            ['id' => self::ADMIN_B_ID, 'descricao' => 'Administração Beta'],
        ]);

        DB::table('comums')->insert([
            ['id' => 30, 'codigo' => '09-0001', 'descricao' => 'Igreja Alfa', 'administracao_id' => self::ADMIN_A_ID],
            ['id' => 40, 'codigo' => '09-0002', 'descricao' => 'Igreja Beta', 'administracao_id' => self::ADMIN_B_ID],
        ]);

        // Usuário legado comum: sem igreja, sem administração, sem permissões extras.
        DB::table('usuarios')->insert([
            'id' => self::USER_ID,
            'nome' => 'Usuário Sem Escopo',
            'email' => 'sem.escopo@exemplo.com',
            'senha' => bcrypt('senha123'),
            'ativo' => 1,
            'tipo' => null,
            'administracao_id' => null,
            'comum_id' => null,
            'administracoes_permitidas' => null,
            'permissions' => null,
        ]);

        // Donos das importações (outros tenants).
        DB::table('usuarios')->insert([
            'id' => 51,
            'nome' => 'Dono Alfa',
            'email' => 'dono.alfa@exemplo.com',
            'ativo' => 1,
        ]);

        DB::table('usuarios')->insert([
            'id' => 52,
            'nome' => 'Dono Beta',
            'email' => 'dono.beta@exemplo.com',
            'ativo' => 1,
        ]);

        // Duas importações de administrações distintas.
        DB::table('importacoes')->insert([
            [
                'id' => self::IMPORT_A_ID,
                'usuario_id' => 51,
                'comum_id' => 30,
                'administracao_id' => self::ADMIN_A_ID,
                'arquivo_nome' => 'A-publico.csv',
                'total_linhas' => 12,
                'linhas_processadas' => 12,
                'linhas_sucesso' => 12,
                'porcentagem' => 100,
                'status' => 'concluida',
            ],
            [
                'id' => self::IMPORT_B_ID,
                'usuario_id' => 52,
                'comum_id' => 40,
                'administracao_id' => self::ADMIN_B_ID,
                'arquivo_nome' => 'B-private.csv',
                'total_linhas' => 7,
                'linhas_processadas' => 7,
                'linhas_sucesso' => 7,
                'porcentagem' => 100,
                'status' => 'concluida',
            ],
        ]);
    }

    public function test_usuario_sem_escopo_recebe_lista_vazia_de_importacoes(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => self::USER_ID,
            'is_admin' => false,
            'administracao_id' => null,
            'administracoes_permitidas' => [],
            'comum_id' => null,
        ])->get(route('migration.spreadsheets.create'));

        $response->assertOk();

        // Nenhum dado de outro tenant pode aparecer na resposta.
        $response->assertDontSee('B-private.csv');
        $response->assertDontSee('A-publico.csv');
        $response->assertDontSee('Administração Beta');
        $response->assertDontSee('Administração Alfa');
        $response->assertDontSee('Dono Alfa');
        $response->assertDontSee('Dono Beta');
        $response->assertDontSee('dono.alfa@exemplo.com');
        $response->assertDontSee('dono.beta@exemplo.com');
        $response->assertDontSee('Igreja Beta');
        $response->assertDontSee('Igreja Alfa');

        $response->assertViewHas('recentImports', []);
    }

    public function test_escopo_explicito_vazio_nao_aplica_filtro_de_igreja(): void
    {
        // Mesmo que exista igreja selecionada na sessão, um escopo administrativo
        // vazio continua significando "nenhuma importação".
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => self::USER_ID,
            'is_admin' => false,
            'administracao_id' => null,
            'administracoes_permitidas' => [],
            'comum_id' => 40,
        ])->get(route('migration.spreadsheets.create'));

        $response->assertOk();
        $response->assertDontSee('B-private.csv');

        $imports = $response->viewData('recentImports');
        $this->assertSame([], $imports);
    }

    public function test_usuario_com_administracao_continua_vendo_suas_importacoes(): void
    {
        // Controle: quem tem escopo administrativo continua vendo suas importações.
        DB::table('usuarios')->insert([
            'id' => 8,
            'nome' => 'Usuário Alfa',
            'email' => 'alfa@exemplo.com',
            'ativo' => 1,
        ]);

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 8,
            'is_admin' => false,
            'administracao_id' => self::ADMIN_A_ID,
            'administracoes_permitidas' => [],
            'comum_id' => null,
        ])->get(route('migration.spreadsheets.create'));

        $response->assertOk();
        $response->assertSee('A-publico.csv');
        $response->assertDontSee('B-private.csv');
    }

    public function test_controller_nunca_envia_escopo_null_ao_servico(): void
    {
        // O controller resolve o escopo antes de chamar o serviço. Um usuário
        // não-admin sem escopo não pode chegar ao serviço com `null`, que
        // significaria acesso irrestrito.
        $registro = [];

        $this->mock(\App\Contracts\LegacySpreadsheetImportServiceInterface::class, function ($mock) use (&$registro): void {
            $mock->shouldReceive('administrationOptions')->andReturn(collect());
            $mock->shouldReceive('recentImports')->andReturnUsing(
                function (?int $churchId, int $limit = 5) use (&$registro): array {
                    $registro[] = true;

                    return [];
                }
            );
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => self::USER_ID,
            'is_admin' => false,
            'administracao_id' => null,
            'administracoes_permitidas' => [],
            'comum_id' => null,
        ])->get(route('migration.spreadsheets.create'));

        $response->assertOk();

        // Sem escopo nenhum o serviço nem deve ser chamado: o controller já
        // devolve a lista vazia, sem negociar um escopo nulo.
        $this->assertSame([], $registro);
        $response->assertViewHas('recentImports', []);
    }
}

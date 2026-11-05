<?php

declare(strict_types=1);

namespace Tests\Feature\Spreadsheets;

use App\Core\ConnectionManager;
use App\Contracts\LegacySpreadsheetImportServiceInterface;
use App\Services\AnalysisPersistenceService;
use App\Services\LegacySpreadsheetImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SpreadsheetWorkflowCoverageTest extends TestCase
{
    public function test_fluxo_preserva_acoes_selecionadas_ate_a_confirmacao(): void
    {
        Schema::create('administracoes', function (Blueprint $t): void {
            $t->id();
            $t->string('descricao');
        });
        Schema::create('usuarios', function (Blueprint $t): void {
            $t->id();
            $t->string('nome');
            $t->string('email');
            $t->unsignedBigInteger('administracao_id');
            $t->string('tipo');
            $t->boolean('ativo');
        });
        Schema::create('comums', function (Blueprint $t): void {
            $t->id();
            $t->string('codigo');
            $t->unsignedBigInteger('administracao_id')->nullable();
        });
        Schema::create('tipos_bens', function (Blueprint $t): void {
            $t->id();
            $t->string('codigo');
            $t->string('descricao');
        });
        Schema::create('dependencias', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('comum_id');
            $t->string('descricao');
        });
        Schema::create('produtos', function (Blueprint $t): void {
            $t->id('id_produto');
            $t->unsignedBigInteger('comum_id');
            $t->string('codigo');
            $t->unsignedBigInteger('tipo_bem_id');
            $t->string('bem');
            $t->string('complemento')->default('');
            $t->unsignedBigInteger('dependencia_id');
            foreach (['novo', 'importado', 'checado', 'editado', 'imprimir_etiqueta', 'imprimir_14_1', 'ativo'] as $coluna) {
                $t->integer($coluna)->default(0);
            }
            $t->string('observacao')->default('');
        });
        Schema::create('import_erros', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('importacao_id');
            foreach (['linha_csv', 'codigo', 'localidade', 'codigo_comum', 'descricao_csv', 'bem', 'complemento', 'dependencia', 'mensagem_erro'] as $coluna) {
                $t->text($coluna)->nullable();
            }
        });
        Schema::create('configuracoes', function (Blueprint $t): void {
            $t->integer('pulo_linhas')->nullable();
            $t->string('mapeamento_colunas')->nullable();
        });
        Schema::create('importacoes', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('usuario_id');
            $t->unsignedBigInteger('comum_id')->nullable();
            $t->unsignedBigInteger('administracao_id')->nullable();
            $t->string('arquivo_nome');
            $t->string('arquivo_caminho');
            $t->integer('total_linhas');
            $t->string('status');
            $t->timestamp('iniciada_em')->nullable();
            $t->timestamp('concluida_em')->nullable();
            $t->integer('linhas_processadas')->default(0);
            $t->integer('linhas_sucesso')->default(0);
            $t->integer('linhas_erro')->default(0);
            $t->decimal('porcentagem', 5, 2)->default(0);
            $t->text('mensagem_erro')->nullable();
        });
        (require database_path('migrations/20261104180000_create_importacao_acoes_table.php'))->up();
        DB::table('administracoes')->insert(['id' => 1, 'descricao' => 'Administração de teste']);
        DB::table('usuarios')->insert(['id' => 1, 'nome' => 'Usuário de teste', 'email' => 'teste@example.com', 'administracao_id' => 1, 'tipo' => 'usuario', 'ativo' => 1]);
        DB::table('comums')->insert(['id' => 1, 'codigo' => '99-9999', 'administracao_id' => 1]);
        DB::table('tipos_bens')->insert([
            ['codigo' => '4', 'descricao' => 'CADEIRA'],
            ['codigo' => '7', 'descricao' => 'MESA'],
        ]);
        $idInicial = random_int(1000000000, 2000000000);
        self::assertFileDoesNotExist(storage_path('tmp/analise_' . ($idInicial + 1) . '.json'));
        DB::table('importacoes')->insert([
            'id' => $idInicial,
            'usuario_id' => 1,
            'administracao_id' => 1,
            'arquivo_nome' => 'teste.csv',
            'arquivo_caminho' => 'teste.csv',
            'total_linhas' => 0,
            'status' => 'aguardando',
        ]);
        DB::table('importacoes')->where('id', $idInicial)->delete();

        $servico = new LegacySpreadsheetImportService();
        $this->app->instance(LegacySpreadsheetImportServiceInterface::class, $servico);
        if (DB::getDriverName() === 'sqlite') {
            $pdo = DB::connection()->getPdo();
            $pdo->sqliteCreateFunction('NOW', static fn (): string => date('Y-m-d H:i:s'));
        }

        $origem = tempnam(sys_get_temp_dir(), 'planilha_http_');
        self::assertNotFalse($origem);
        self::assertTrue(copy(base_path('tests/Fixtures/importacao/relatorio-ficticio.xls'), $origem));
        $caminho = null;
        $id = null;
        try {
            $resposta = $this->withSession(['usuario_id' => 1, 'administracao_id' => 1])
                ->post(route('migration.spreadsheets.store'), [
                    'administracao_id' => 1,
                    'arquivo_csv' => new UploadedFile($origem, 'planilha.xls', 'application/vnd.ms-excel', null, true),
                ]);
            $importacao = DB::table('importacoes')->first();
            self::assertNotNull($importacao, (string) session('status'));
            $id = (int) $importacao->id;
            $caminho = $importacao->arquivo_caminho;
            $resposta->assertRedirect(route('migration.spreadsheets.preview', ['importacao' => $id]));
            self::assertSame(1, (int) $importacao->usuario_id);
            self::assertSame(1, (int) $importacao->administracao_id);
            self::assertSame('aguardando', $importacao->status);
            self::assertSame('planilha.csv', $importacao->arquivo_nome);
            self::assertFileExists($caminho);
            $analise = (new AnalysisPersistenceService())->carregarAnalise($id);
            self::assertIsArray($analise);
            self::assertSame(2, $analise['resumo']['total']);
            self::assertCount(2, $analise['registros']);
            $this->get(route('migration.spreadsheets.preview', ['importacao' => $id]))->assertOk();
            $this->postJson(route('migration.spreadsheets.preview.actions', ['importacao' => $id]), [
                'igrejas' => ['99-9999' => 'importar'],
            ])->assertOk()->assertJsonPath('sucesso', true)->assertJsonPath('igrejas_salvas', 1);
            $this->assertDatabaseHas('importacao_acoes', ['importacao_id' => $id, 'chave' => '99-9999', 'tipo' => 'igreja', 'acao' => 'importar']);
            $this->post(route('migration.spreadsheets.confirm', ['importacao' => $id]), ['importar_tudo' => 1])
                ->assertRedirect(route('migration.spreadsheets.processing', ['importacao' => $id]));
            $this->get(route('migration.spreadsheets.processing', ['importacao' => $id]))->assertOk();
            $processamento = $this->postJson(route('migration.spreadsheets.start', ['importacao' => $id]));
            $processamento->assertOk()->assertJsonPath('success', true);
            self::assertSame(2, $processamento->json('resultado.sucesso'), json_encode(DB::table('import_erros')->pluck('mensagem_erro')->all()));
            self::assertSame(2, DB::table('produtos')->count());
            self::assertSame(['99-9999 / 0001', '99-9999 / 0002'], DB::table('produtos')->orderBy('codigo')->pluck('codigo')->all());
            self::assertSame('concluida', DB::table('importacoes')->where('id', $id)->value('status'));
        } finally {
            if ($id !== null) {
                (new AnalysisPersistenceService())->limparAnalise($id);
            }
            if (is_string($caminho) && is_file($caminho)) {
                unlink($caminho);
            }
            if (is_file($origem)) {
                unlink($origem);
            }
            $convertido = $origem . '_converted.csv';
            if (is_file($convertido)) {
                unlink($convertido);
            }
            ConnectionManager::reset();
        }
    }

    public function test_arquivo_ausente_ou_extensao_invalida_sao_rejeitados(): void
    {
        $this->withSession(['usuario_id' => 1, 'administracao_id' => 1]);

        $this->from(route('migration.spreadsheets.create'))
            ->post(route('migration.spreadsheets.store'), ['administracao_id' => 1])
            ->assertRedirect(route('migration.spreadsheets.create'))
            ->assertSessionHasErrors(['arquivo_csv']);

        $this->from(route('migration.spreadsheets.create'))
            ->post(route('migration.spreadsheets.store'), [
                'administracao_id' => 1,
                'arquivo_csv' => UploadedFile::fake()->createWithContent('arquivo.pdf', 'conteúdo fictício'),
            ])->assertRedirect(route('migration.spreadsheets.create'))
            ->assertSessionHasErrors(['arquivo_csv']);
    }

    public function test_fluxo_exige_autenticacao(): void
    {
        $this->withSession(['_enforce_legacy_auth' => true])
            ->post(route('migration.spreadsheets.store'), ['administracao_id' => 1])
            ->assertRedirect(route('migration.login'));
        $this->get(route('migration.spreadsheets.preview', ['importacao' => 1]))
            ->assertRedirect(route('migration.login'));
    }

    public function test_planilha_vazia_nao_cria_importacao(): void
    {
        $this->withSession(['usuario_id' => 1, 'administracao_id' => 1])
            ->post(route('migration.spreadsheets.store'), [
                'administracao_id' => 1,
                'arquivo_csv' => UploadedFile::fake()->createWithContent('vazio.csv', ''),
            ])->assertRedirect(route('migration.spreadsheets.create'))
            ->assertSessionHas('status_type', 'error');
    }
}

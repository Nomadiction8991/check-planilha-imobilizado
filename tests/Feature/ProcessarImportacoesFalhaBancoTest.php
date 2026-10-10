<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CsvParserService;
use App\Services\ImportacaoService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Contrato de exit code do comando checkplanilha:processar-importacoes com
 * FALHA REAL de banco/escrita — nenhum mock lanca excecao aqui.
 *
 * Diferente de ProcessarImportacoesExitCodeTest — que fixa o mesmo contrato
 * com dubles de servico — este teste exercita o caminho de ponta a ponta:
 * ImportacaoService real, ligado ao banco SQLite do proprio ambiente de
 * teste, com a analise no formato que o CsvParserService le, e a falha
 * provocada por um TRIGGER que aborta o UPDATE de status. Quem estoura e o
 * motor do banco, nao um duble.
 *
 * Bug (P2) sendo fixado: o comando imprimia "❌ <mensagem>" quando o
 * processamento falhava, mas devolvia 0 ao processo. Um runner (cron, CI,
 * script) que confia no exit code marcava a execucao como sucesso e nao
 * alertava nem retentava, embora a importacao nao tivesse sido processada.
 */
final class ProcessarImportacoesFalhaBancoTest extends TestCase
{
    private const IMPORTACAO_ID = 889001;

    private const CODIGO_COMUM = '09-0565';

    private const CODIGO_PRODUTO = '09-0565 / 001495';

    private const MENSAGEM_FALHA = 'falha de banco simulada: disco cheio';

    protected function tearDown(): void
    {
        // Limpa a analise em disco para o proximo teste nao a reaproveite.
        $this->removerAnalise();

        parent::tearDown();
    }

    /**
     * Le a saida do comando uma unica vez. Artisan::output() drena o buffer
     * do Symfony a cada chamada: a segunda leitura devolve string vazia, o
     * que faria um assertivo comportamento "sumir" entre dois asserts.
     */
    private function capturarSaida(): string
    {
        try {
            return Artisan::output();
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function removerAnalise(): void
    {
        $caminho = $this->caminhoAnalise();
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }

    /**
     * Caminho principal: falha real de escrita no banco durante o
     * processamento precisa gerar exit code diferente de zero.
     */
    public function test_falha_de_escrita_no_banco_retorna_codigo_de_erro(): void
    {
        $this->montarCenario();
        $this->forcarFalhaDeEscritaNoBanco();

        $exitCode = $this->processar();

        self::assertNotSame(
            0,
            $exitCode,
            'Falha de banco/escrita precisa gerar exit code diferente de zero, senao o runner marca a execucao como sucesso.',
        );

        $this->assertStringContainsString(self::MENSAGEM_FALHA, $this->capturarSaida());
    }

    /**
     * O importador precisa de uma saida que ele consiga ler: a mensagem de
     * erro do banco tem de aparecer na saida do comando, nao so o codigo.
     *
     * A saida e lida uma unica vez e guardada: Artisan::output() drena o
     * buffer do Symfony, e a segunda chamada devolve string vazia.
     */
    public function test_saida_do_comando_traz_o_indicador_de_erro(): void
    {
        $this->montarCenario();
        $this->forcarFalhaDeEscritaNoBanco();

        $this->processar();

        $output = $this->capturarSaida();
        $this->assertStringContainsString('❌', $output);
        $this->assertStringContainsString('Processando importação #'.self::IMPORTACAO_ID, $output);
    }

    /**
     * Teste de controle: sem falha injetada, o MESMO caminho processa a
     * importacao de verdade e devolve 0. Sem ele o teste acima poderia passar
     * porque o comando nunca chegou a tocar no processamento.
     */
    public function test_processamento_bem_sucedido_sem_falha_injetada_mantem_exit_code_de_sucesso(): void
    {
        $this->montarCenario();

        $exitCode = $this->processar();

        self::assertSame(0, $exitCode, 'O caminho feliz nao pode passar a falhar.');

        $output = $this->capturarSaida();
        $this->assertStringContainsString('1 sucesso, 0 erros', $output);

        // Prova de que o processamento realmente rodou: o produto existe no banco.
        $produtos = DB::table('produtos')->where('codigo', self::CODIGO_PRODUTO)->get();
        self::assertCount(1, $produtos, 'O produto do CSV precisa ter sido importado.');

        // E a importacao ficou concluida, nao em erro.
        $status = DB::table('importacoes')->where('id', self::IMPORTACAO_ID)->value('status');
        self::assertSame('concluida', $status);
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    /**
     * Injeta os servicos reais ligados a MESMA conexao do ambiente de teste
     * usada pelo schema, e dispara o comando pelo Artisan — exatamente como
     * um runner (cron, CI, script) faria.
     *
     * O CsvParserService tambem recebe o PDO explicitamente: sem isso o
     * construtor criaria uma conexao propria via ConnectionManager (que so
     * sabe falar MySQL) e o comando morreria antes de processar.
     */
    private function processar(): int
    {
        $this->garantirAnalise();

        $pdo = DB::connection()->getPdo();

        $this->app->instance(ImportacaoService::class, new ImportacaoService($pdo));
        $this->app->instance(CsvParserService::class, new CsvParserService($pdo));

        return Artisan::call('checkplanilha:processar-importacoes', ['importacao' => self::IMPORTACAO_ID]);
    }

    /**
     * Provoca a falha no banco de verdade: um TRIGGER aborta o UPDATE que
     * marca a importacao como 'processando' — a primeira escrita do
     * processamento. E falha de escrita no banco, nao excecao de mock.
     */
    private function forcarFalhaDeEscritaNoBanco(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS falha_escrita_importacoes');

        DB::statement(
            "CREATE TRIGGER falha_escrita_importacoes
             BEFORE UPDATE OF status ON importacoes
             WHEN NEW.status = 'processando'
             BEGIN
                 SELECT RAISE(ABORT, '".self::MENSAGEM_FALHA."');
             END",
        );
    }

    private function montarCenario(): void
    {
        $this->criarSchema();
        $this->semearDados();
    }

    private function criarSchema(): void
    {
        // Schema minimo para o processamento, e NAO o schema completo de
        // producao. Isso e deliberado: o pdo_sqlite deste ambiente nao cria a
        // tabela comuns quando o DDL traz as colunas cnpj e administracao
        // (VARCHAR nullable) — o CREATE passa, mas a tabela nao aparece em
        // sqlite_master e o seed seguinte morre com "no such table". Nenhum
        // desses campos e tocado pelo caminho testado (processSingle ->
        // processarComAcoes), entao omiti-los nao enfraquece o cenario.
        DB::statement('CREATE TABLE IF NOT EXISTS administracoes (id INTEGER PRIMARY KEY AUTOINCREMENT, descricao VARCHAR(255) NOT NULL)');

        DB::statement('CREATE TABLE IF NOT EXISTS comums (id INTEGER PRIMARY KEY AUTOINCREMENT, codigo VARCHAR(50) NOT NULL UNIQUE, descricao VARCHAR(255) DEFAULT NULL, administracao_id INTEGER DEFAULT NULL)');

        DB::statement('CREATE TABLE IF NOT EXISTS usuarios (id INTEGER PRIMARY KEY AUTOINCREMENT, nome VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, senha VARCHAR(255) NOT NULL, ativo TINYINT DEFAULT 1, administracao_id INTEGER DEFAULT NULL, comum_id INTEGER DEFAULT NULL)');

        DB::statement('CREATE TABLE IF NOT EXISTS tipos_bens (id INTEGER PRIMARY KEY AUTOINCREMENT, codigo INTEGER NOT NULL, descricao VARCHAR(255) NOT NULL, administracao_id INTEGER DEFAULT NULL)');

        DB::statement('CREATE TABLE IF NOT EXISTS dependencias (id INTEGER PRIMARY KEY AUTOINCREMENT, comum_id INTEGER DEFAULT NULL, descricao VARCHAR(255) NOT NULL, UNIQUE (comum_id, descricao))');

        DB::statement('CREATE TABLE IF NOT EXISTS produtos (id_produto INTEGER PRIMARY KEY AUTOINCREMENT, comum_id INTEGER NOT NULL, codigo VARCHAR(50) DEFAULT NULL, tipo_bem_id INTEGER DEFAULT 99, bem TEXT NOT NULL, complemento TEXT DEFAULT NULL, dependencia_id INTEGER DEFAULT 0, editado_tipo_bem_id INTEGER DEFAULT NULL, editado_bem VARCHAR(255) DEFAULT NULL, editado_complemento VARCHAR(255) DEFAULT NULL, editado_dependencia_id INTEGER DEFAULT NULL, novo INTEGER DEFAULT 0, importado TINYINT DEFAULT 0, checado INTEGER DEFAULT 0, editado INTEGER DEFAULT 0, imprimir_etiqueta INTEGER DEFAULT 0, imprimir_14_1 INTEGER DEFAULT 0, observacao VARCHAR(255) DEFAULT \'\', ativo INTEGER DEFAULT 1)');

        DB::statement('CREATE TABLE IF NOT EXISTS importacoes (id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, comum_id INTEGER DEFAULT NULL, administracao_id INTEGER DEFAULT NULL, arquivo_nome VARCHAR(255) NOT NULL, arquivo_caminho VARCHAR(500) NOT NULL, total_linhas INTEGER DEFAULT 0, linhas_processadas INTEGER DEFAULT 0, linhas_sucesso INTEGER DEFAULT 0, linhas_erro INTEGER DEFAULT 0, porcentagem DECIMAL(5,2) DEFAULT 0, status VARCHAR(50) DEFAULT \'aguardando\', mensagem_erro TEXT DEFAULT NULL, iniciada_em TIMESTAMP DEFAULT NULL, concluida_em TIMESTAMP DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');

        DB::statement('CREATE TABLE IF NOT EXISTS import_erros (id INTEGER PRIMARY KEY AUTOINCREMENT, importacao_id INTEGER NOT NULL, linha_csv VARCHAR(50) DEFAULT NULL, codigo VARCHAR(50) DEFAULT NULL, localidade VARCHAR(255) DEFAULT NULL, codigo_comum VARCHAR(50) DEFAULT NULL, descricao_csv TEXT DEFAULT NULL, bem VARCHAR(255) DEFAULT NULL, complemento VARCHAR(255) DEFAULT NULL, dependencia VARCHAR(255) DEFAULT NULL, mensagem_erro TEXT NOT NULL, resolvido TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
    }

    private function semearDados(): void
    {
        // INSERT puro, sem DELETE previo, e sempre apos o schema. A ordem
        // administracoes -> comuns -> usuarios nao e preferencia: inserir em
        // usuarios antes de comuns quebra o pdo_sqlite deste ambiente, que
        // passa a nao enxergar a tabela comuns ("no such table"). O banco
        // :memory: ja comeca vazio em cada teste, entao nao ha o que limpar.
        DB::table('administracoes')->insert(['id' => 1, 'descricao' => 'Administração Central']);

        DB::table('comums')->insert(['id' => 100, 'codigo' => self::CODIGO_COMUM, 'descricao' => 'Igreja Matriz Central', 'administracao_id' => 1]);

        DB::table('usuarios')->insert(['id' => 7, 'nome' => 'Maria Silva', 'email' => 'maria@exemplo.com', 'senha' => 'hash', 'ativo' => 1, 'administracao_id' => 1]);

        DB::table('tipos_bens')->insert(['codigo' => 4, 'descricao' => 'CADEIRA', 'administracao_id' => 1]);

        // A importacao que o comando vai processar: aguardando.
        DB::table('importacoes')->insert(['id' => self::IMPORTACAO_ID, 'usuario_id' => 7, 'comum_id' => 100, 'administracao_id' => 1, 'arquivo_nome' => 'planilha_teste.csv', 'arquivo_caminho' => '/tmp/planilha_teste.csv', 'total_linhas' => 1, 'status' => 'aguardando']);
    }

    /**
     * Escreve a analise em disco no mesmo formato que o
     * AnalysisPersistenceService produz — o CsvParserService real a le.
     */
    private function garantirAnalise(): void
    {
        $caminho = $this->caminhoAnalise();
        $dir = dirname($caminho);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($caminho, json_encode($this->analiseEmDisco(), JSON_UNESCAPED_UNICODE));
    }

    private function caminhoAnalise(): string
    {
        return base_path('storage/tmp/analise_'.self::IMPORTACAO_ID.'.json');
    }

    /**
     * @return array<string, mixed>
     */
    private function analiseEmDisco(): array
    {
        return [
            'importacao_id' => self::IMPORTACAO_ID,
            'resumo' => ['novos' => 1, 'atualizar' => 0, 'exclusoes' => 0],
            'registros' => [
                [
                    'linha_csv' => 30,
                    'status' => 'novo',
                    'acao_sugerida' => 'importar',
                    'dados_csv' => [
                        'codigo' => self::CODIGO_PRODUTO,
                        'tipo_bem_codigo' => '4',
                        'tipo_bem_descricao' => 'CADEIRA',
                        'bem' => 'CADEIRA',
                        'complemento' => 'ESTOFADA',
                        'dependencia_descricao' => 'SALÃO',
                        'codigo_comum' => self::CODIGO_COMUM,
                        'localidade' => 'BR '.self::CODIGO_COMUM,
                        'nome_original' => 'CADEIRA ESTOFADA',
                    ],
                    'dados_db' => null,
                    'diferencas' => [],
                    'id_produto' => null,
                ],
            ],
        ];
    }
}

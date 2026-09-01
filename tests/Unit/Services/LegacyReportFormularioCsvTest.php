<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Services\LegacyReportService;
use App\Services\LegacyReportTemplateService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyReportFormularioCsvTest extends TestCase
{
    private LegacyReportTemplateService $templates;

    private LegacyAuthSessionServiceInterface&MockInterface $auth;

    private LegacyReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templates = new LegacyReportTemplateService();
        $this->auth = $this->mock(LegacyAuthSessionServiceInterface::class);
        $this->auth->shouldReceive('currentUser')->zeroOrMoreTimes()->andReturn([
            'id' => 9,
            'nome' => 'Maria Silva',
        ]);

        $this->service = new LegacyReportService($this->templates, $this->auth);
    }

    public function testExportaCsvDoFormulario141ComItens(): void
    {
        $this->seedChurch();
        $this->seedTipoBem(4, '4', 'CADEIRA/CADEIRA GIRATORIA');
        $this->seedDependencia(2, 'SALAO', 7);
        $this->seedProduto141([
            'codigo' => '12-3456 / 0001',
            'bem' => 'CADEIRA',
            'complemento' => 'METALICA',
            'condicao_14_1' => '1',
            'dependencia_id' => 2,
            'tipo_bem_id' => 4,
            'nota_numero' => 'NF-100',
            'nota_data' => '2026-08-01',
            'nota_valor' => '150.90',
            'nota_fornecedor' => 'Movelaria LTDA',
        ]);
        $this->seedProduto141([
            'codigo' => '12-3456 / 0002',
            'bem' => 'MESA',
            'complemento' => '',
            'condicao_14_1' => '2',
            'dependencia_id' => 2,
            'tipo_bem_id' => 4,
        ]);

        $file = $this->service->downloadFormularioCsv(7, '14.1');

        $this->assertStringStartsWith('relatorio_14.1_12-3456_', $file['filename']);
        $this->assertMatchesRegularExpression('/_\d{8}_\d{6}\.csv$/', $file['filename']);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $file['content']);

        $linhas = $this->parseCsv($file['content']);

        $this->assertSame([
            'Código', 'Condição', 'Descrição original', 'Descrição atual', 'Dependência',
            'Número nota', 'Data nota', 'Valor nota', 'Fornecedor',
        ], $linhas[0]);

        $this->assertSame(
            ['12-3456 / 0001', 'Mais de cinco anos com documento', 'CADEIRA METALICA', '{4 - CADEIRA/CADEIRA GIRATORIA} CADEIRA METALICA {SALAO}', 'SALAO', 'NF-100', '01/08/2026', '150.90', 'Movelaria LTDA'],
            $linhas[1],
        );

        $this->assertSame(
            ['12-3456 / 0002', 'Mais de cinco anos sem documento', 'MESA', '{4 - CADEIRA/CADEIRA GIRATORIA} MESA {SALAO}', 'SALAO', '', '', '', ''],
            $linhas[2],
        );
    }

    public function testExportaCsvDoFormulario141ComVirgulaNaNota(): void
    {
        $this->seedChurch();
        $this->seedProduto141([
            'codigo' => '12-3456 / 0003',
            'bem' => 'TALITA',
            'condicao_14_1' => '3',
            'nota_fornecedor' => 'Comercio, Importadora e Cia',
        ]);

        $file = $this->service->downloadFormularioCsv(7, '14.1');
        $linhas = $this->parseCsv($file['content']);

        $this->assertSame('Comercio, Importadora e Cia', $linhas[1][8]);
    }

    public function testExportaCsvDoFormulario146SomenteEdicoesRelevantes(): void
    {
        $this->seedChurch();
        $this->seedTipoBem(4, '4', 'CADEIRA/CADEIRA GIRATORIA');
        $this->seedTipoBem(5, '5', 'MESA');
        $this->seedDependencia(2, 'SALAO', 7);
        $this->seedDependencia(3, 'COZINHA', 7);

        // Edição relevante: mudou a dependência.
        $this->seedProdutoEditado([
            'codigo' => '12-3456 / 0010',
            'bem' => 'BANCO',
            'tipo_bem_id' => 5,
            'dependencia_id' => 2,
            'editado_bem' => 'BANCO',
            'editado_dependencia_id' => 3,
        ]);
        // Edição irrelevante: nada mudou de fato (mesma descrição/tipo/local).
        $this->seedProdutoEditado([
            'codigo' => '12-3456 / 0011',
            'bem' => 'CADEIRA',
            'tipo_bem_id' => 4,
            'dependencia_id' => 2,
            'editado_bem' => 'CADEIRA',
            'editado_tipo_bem_id' => 4,
            'editado_dependencia_id' => 2,
        ]);

        $file = $this->service->downloadFormularioCsv(7, '14.6');

        $this->assertStringStartsWith('relatorio_14.6_12-3456_', $file['filename']);

        $linhas = $this->parseCsv($file['content']);

        $this->assertSame([
            'Código', 'Descrição original', 'Descrição atual', 'Tipo de bem original', 'Tipo de bem editado',
            'Dependência original', 'Dependência editada',
        ], $linhas[0]);
        $this->assertCount(2, $linhas);

        $linha = $linhas[1];
        $this->assertSame('12-3456 / 0010', $linha[0]);
        $this->assertSame('BANCO', $linha[1]);
        $this->assertSame('BANCO', $linha[2]);
        $this->assertSame('5', $linha[3]);
        $this->assertSame('5', $linha[4]);
        $this->assertSame('SALAO', $linha[5]);
        $this->assertSame('COZINHA', $linha[6]);
    }

    public function testRejeitaFormularioInvalido(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Formulário inválido.');

        $this->service->downloadFormularioCsv(7, '99.9');
    }

    public function testRejeitaIgrejaInexistente(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->downloadFormularioCsv(999, '14.1');
    }

    public function testRejeitaFormularioSemItens(): void
    {
        $this->criarTabelas();
        $this->seedChurch();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não está disponível');

        $this->service->downloadFormularioCsv(7, '14.1');
    }

    public function testRejeitaFolhaEmBranco(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('folha em branco');

        $this->service->downloadFormularioCsv(7, '14.2');
    }

    public function testSanitizaDescricaoComFormulaNoFormulario141(): void
    {
        $this->seedChurch();
        $this->seedProduto141([
            'codigo' => '12-3456 / 0099',
            'bem' => '=2+2',
            'complemento' => '',
            'condicao_14_1' => '1',
            'nota_fornecedor' => '+cmd|/C calc',
        ]);

        $file = $this->service->downloadFormularioCsv(7, '14.1');
        $linhas = $this->parseCsv($file['content']);

        // Descrições e fornecedor com prefixo perigoso devem vir com apóstrofo.
        $this->assertStringStartsWith("'=2+2", $linhas[1][2]);
        $this->assertStringStartsWith("'+cmd|/C calc", $linhas[1][8]);
        // Código e data são valores sistêmicos e não devem ser prefixados.
        $this->assertSame('12-3456 / 0099', $linhas[1][0]);
    }

    public function testSanitizaDependenciaNoFormulario146(): void
    {
        $this->seedChurch();
        $this->seedTipoBem(4, '4', 'CADEIRA');
        $this->seedDependencia(2, '@evil', 7);
        $this->seedDependencia(3, '-injected', 7);
        $this->seedProdutoEditado([
            'codigo' => '12-3456 / 0020',
            'bem' => 'BANCO',
            'tipo_bem_id' => 4,
            'dependencia_id' => 2,
            'editado_bem' => 'BANCO',
            'editado_dependencia_id' => 3,
        ]);

        $file = $this->service->downloadFormularioCsv(7, '14.6');
        $linhas = $this->parseCsv($file['content']);

        $this->assertSame("'@evil", $linhas[1][5]);
        $this->assertSame("'-injected", $linhas[1][6]);
    }

    /**
     * Interpreta o conteúdo CSV respeitando aspas e separador ';'.
     *
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $content): array
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            self::fail('Não foi possível interpretar o CSV.');
        }

        fwrite($stream, $content);
        rewind($stream);

        $bom = "\xEF\xBB\xBF";
        $rows = [];
        $primeira = true;
        while (($row = fgetcsv($stream, 0, ';')) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            if ($primeira && isset($row[0])) {
                $row[0] = preg_replace('/^' . preg_quote($bom, '/') . '/', '', (string) $row[0]) ?? $row[0];
                $primeira = false;
            }

            /** @var array<int, string> $row */
            $rows[] = array_map(static fn ($value): string => (string) $value, $row);
        }
        fclose($stream);

        return $rows;
    }

    /**
     * Cria as tabelas mínimas em SQLite :memory: espelhando o schema legado.
     */
    private function criarTabelas(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS comums (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo VARCHAR(50) NOT NULL,
            cnpj VARCHAR(255) DEFAULT NULL,
            descricao VARCHAR(255) DEFAULT NULL,
            administracao VARCHAR(255) DEFAULT NULL,
            cidade VARCHAR(255) DEFAULT NULL,
            setor VARCHAR(255) DEFAULT NULL,
            estado VARCHAR(255) DEFAULT NULL,
            estado_administracao VARCHAR(255) DEFAULT NULL,
            cidade_administracao VARCHAR(255) DEFAULT NULL,
            administracao_id INTEGER DEFAULT NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS administracoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao VARCHAR(255) NOT NULL,
            cnpj VARCHAR(255) DEFAULT NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS tipos_bens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo INTEGER NOT NULL,
            descricao VARCHAR(255) NOT NULL,
            administracao_id INTEGER DEFAULT NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS dependencias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao VARCHAR(255) NOT NULL,
            comum_id INTEGER NOT NULL
        )');

        DB::statement("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(255) NOT NULL
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS produtos (
            id_produto INTEGER PRIMARY KEY AUTOINCREMENT,
            comum_id INTEGER NOT NULL,
            codigo VARCHAR(255) DEFAULT '',
            tipo_bem_id INTEGER DEFAULT NULL,
            dependencia_id INTEGER DEFAULT NULL,
            editado_tipo_bem_id INTEGER DEFAULT NULL,
            editado_dependencia_id INTEGER DEFAULT NULL,
            editado INTEGER DEFAULT 0,
            novo INTEGER DEFAULT 0,
            ativo INTEGER DEFAULT 1,
            checado INTEGER DEFAULT 0,
            imprimir_etiqueta INTEGER DEFAULT 0,
            imprimir_14_1 INTEGER DEFAULT 0,
            condicao_14_1 VARCHAR(10) DEFAULT '',
            observacao TEXT DEFAULT '',
            bem VARCHAR(255) DEFAULT '',
            complemento VARCHAR(255) DEFAULT '',
            marca VARCHAR(255) DEFAULT '',
            altura_m NUMERIC DEFAULT NULL,
            largura_m NUMERIC DEFAULT NULL,
            comprimento_m NUMERIC DEFAULT NULL,
            editado_marca VARCHAR(255) DEFAULT '',
            administrador_acessor_id INTEGER DEFAULT NULL,
            editado_bem VARCHAR(255) DEFAULT '',
            editado_complemento VARCHAR(255) DEFAULT '',
            editado_altura_m NUMERIC DEFAULT NULL,
            editado_largura_m NUMERIC DEFAULT NULL,
            editado_comprimento_m NUMERIC DEFAULT NULL,
            nota_numero VARCHAR(255) DEFAULT '',
            nota_data VARCHAR(255) DEFAULT '',
            nota_valor VARCHAR(255) DEFAULT '',
            nota_fornecedor VARCHAR(255) DEFAULT ''
        )");
    }

    private function seedChurch(): void
    {
        $this->criarTabelas();

        DB::table('administracoes')->insert([
            'id' => 1,
            'descricao' => 'Cuiabá',
            'cnpj' => '12.345.678/0001-90',
        ]);
        DB::table('comums')->insert([
            'id' => 7,
            'codigo' => '12-3456',
            'descricao' => 'Central Cuiabá',
            'cidade' => 'Cuiabá',
            'estado' => 'MT',
            'setor' => 'Norte',
            'administracao_id' => 1,
        ]);
    }

    private function seedTipoBem(int $id, string $codigo, string $descricao): void
    {
        DB::table('tipos_bens')->insert([
            'id' => $id,
            'codigo' => $codigo,
            'descricao' => $descricao,
        ]);
    }

    private function seedDependencia(int $id, string $descricao, int $comumId): void
    {
        DB::table('dependencias')->insert([
            'id' => $id,
            'descricao' => $descricao,
            'comum_id' => $comumId,
        ]);
    }

    private function seedProduto141(array $overrides = []): void
    {
        $this->insertProduto(array_merge([
            'imprimir_14_1' => 1,
            'ativo' => 1,
        ], $overrides));
    }

    private function seedProdutoEditado(array $overrides = []): void
    {
        $this->insertProduto(array_merge([
            'editado' => 1,
            'novo' => 0,
            'ativo' => 1,
            'editado_bem' => null,
            'editado_complemento' => null,
            'editado_marca' => '',
        ], $overrides));
    }

    private function insertProduto(array $attributes): void
    {
        $this->criarTabelas();

        DB::table('produtos')->insert(array_merge([
            'comum_id' => 7,
        ], $attributes));
    }
}

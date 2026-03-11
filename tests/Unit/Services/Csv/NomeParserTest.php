<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Csv;

use App\Services\Csv\NomeParser;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * Testes de caracterização para NomeParser::parsearNome().
 *
 * Estes testes documentam o comportamento existente do parser e garantem
 * que refatorações futuras não quebrem a separação de tipo_bem, bem e complemento.
 *
 * Domínio: CSV do sistema de imobilizado CCB com formato
 *   "CÓDIGO_TIPO - DESCRICAO_TIPO BEM COMPLEMENTO"
 */
class NomeParserTest extends TestCase
{
    private NomeParser $parser;

    protected function setUp(): void
    {
        // Cria PDO mock que retorna null para qualquer consulta a tipos_bens.
        // Os testes que precisam de dados do banco configuram o mock individualmente.
        $pdo = $this->criarPdoMockSemTipos();
        $this->parser = new NomeParser($pdo);
    }

    // ─── Formato personalizado (NxB) ───

    public function testFormatoPersonalizadoQuantidadeEBem(): void
    {
        $resultado = $this->parser->parsearNome('2x - CADEIRA');

        $this->assertSame(2, $resultado['quantidade']);
        $this->assertSame('CADEIRA', $resultado['bem']);
        $this->assertSame('', $resultado['complemento']);
    }

    public function testFormatoPersonalizadoComComplementoNumerico(): void
    {
        $resultado = $this->parser->parsearNome('1x - BANCO 2,50m');

        $this->assertSame(1, $resultado['quantidade']);
        // O bem é a parte antes do número
        $this->assertStringContainsString('BANCO', $resultado['bem']);
    }

    public function testFormatoPersonalizadoComDependenciaEntreColchetes(): void
    {
        $resultado = $this->parser->parsearNome('3x - MESA [Banheiro]');

        $this->assertSame(3, $resultado['quantidade']);
        $this->assertSame('BANHEIRO', $resultado['dependencia_inline']);
    }

    public function testFormatoPersonalizadoComDependenciaNaoAlteraBem(): void
    {
        $resultado = $this->parser->parsearNome('1x - ARMARIO [Secretaria]');

        $this->assertSame('ARMARIO', $resultado['bem']);
        $this->assertSame('SECRETARIA', $resultado['dependencia_inline']);
    }

    // ─── Formato CSV (N - TEXTO) ───

    public function testFormatoCsvExtratipoBemCodigo(): void
    {
        $resultado = $this->parser->parsearNome('4 - CADEIRA CADEIRA TRIBUNA');

        $this->assertSame('4', $resultado['tipo_bem_codigo']);
    }

    public function testFormatoCsvNomeCompletoFallbackSemTipoBem(): void
    {
        // Sem mock de banco → obterBensDoTipo retorna []
        // Fallback: tenta separar por " - ", senão usa texto completo como bem
        $resultado = $this->parser->parsearNome('7 - ARMARIO');

        $this->assertSame('7', $resultado['tipo_bem_codigo']);
        // Texto após o código vai para bem quando não há dados do tipo no banco
        $this->assertNotEmpty($resultado['bem']);
    }

    public function testNomeVazioRetornaResultadoPadrao(): void
    {
        $resultado = $this->parser->parsearNome('');

        $this->assertSame('', $resultado['tipo_bem_codigo']);
        $this->assertSame('', $resultado['bem']);
        $this->assertSame('', $resultado['complemento']);
        $this->assertSame(1, $resultado['quantidade']);
    }

    public function testNomeSemPrefixoNumericoRetornaNomeInteiroCombem(): void
    {
        $resultado = $this->parser->parsearNome('CADEIRA SEM CODIGO');

        // Não casa com nenhum formato → bem = nome original
        $this->assertSame('CADEIRA SEM CODIGO', $resultado['bem']);
        $this->assertSame('', $resultado['tipo_bem_codigo']);
    }

    // ─── Com tipo_bem no banco (mock completo) ───

    public function testFormatoCsvComTipoBemNoBancoSeparaBemEComplemento(): void
    {
        $pdo    = $this->criarPdoMockComTipo('4', 'CADEIRA');
        $parser = new NomeParser($pdo);

        // CSV ecoa a descricao do tipo antes do bem selecionado:
        // "CADEIRA" (eco) + "CADEIRA" (bem escolhido) + "ALMOFADADA" (complemento)
        $resultado = $parser->parsearNome('4 - CADEIRA CADEIRA ALMOFADADA');

        $this->assertSame('4', $resultado['tipo_bem_codigo']);
        $this->assertSame('CADEIRA', $resultado['bem']);
        $this->assertSame('ALMOFADADA', $resultado['complemento']);
    }

    public function testFormatoCsvComTipoBemMultiplasOpcoes(): void
    {
        // Tipo com descricao "BANCO DE MADEIRA/GENUFLEXORIO"
        $pdo    = $this->criarPdoMockComTipo('1', 'BANCO DE MADEIRA/GENUFLEXORIO');
        $parser = new NomeParser($pdo);

        $resultado = $parser->parsearNome('1 - BANCO DE MADEIRA GENUFLEXORIO BANCO DE MADEIRA 2,50 M');

        $this->assertSame('1', $resultado['tipo_bem_codigo']);
        // O bem deve ser uma das opções do tipo
        $this->assertContains($resultado['bem'], ['BANCO DE MADEIRA', 'GENUFLEXORIO']);
    }

    // ─── Helpers ───

    /** Cria PDO mock cujos statements retornam false (sem dados de tipo_bem). */
    private function criarPdoMockSemTipos(): PDO
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        return $pdo;
    }

    /**
     * Cria PDO mock que retorna a descricao fornecida para qualquer consulta a tipos_bens.
     */
    private function criarPdoMockComTipo(string $codigo, string $descricao): PDO
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(['descricao' => $descricao]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        return $pdo;
    }
}

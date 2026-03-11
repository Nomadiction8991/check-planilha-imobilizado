<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\ProdutoRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Testes para ProdutoRepository usando SQLite em memória.
 *
 * SQLite em memória é preferível a mocks de PDO para repositórios porque
 * exercita o SQL real, detectando erros de sintaxe e comportamento de borda.
 */
class ProdutoRepositoryTest extends TestCase
{
    private PDO $pdo;
    private ProdutoRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->criarSchema();
        $this->repository = new ProdutoRepository($this->pdo);
    }

    // ─── limparEdicoes ───

    public function testLimparEdicoesRetornaTrueQuandoProdutoExiste(): void
    {
        $idProduto = $this->inserirProdutoComEdicoes();

        $resultado = $this->repository->limparEdicoes($idProduto, 1);

        $this->assertTrue($resultado);
    }

    public function testLimparEdicoesZeraOsCamposDeEdicao(): void
    {
        $idProduto = $this->inserirProdutoComEdicoes();

        $this->repository->limparEdicoes($idProduto, 1);

        $stmt = $this->pdo->prepare("SELECT * FROM produtos WHERE id_produto = :id");
        $stmt->execute([':id' => $idProduto]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('0', (string) $produto['editado_tipo_bem_id']);
        $this->assertSame('', $produto['editado_bem']);
        $this->assertSame('', $produto['editado_complemento']);
        $this->assertSame('0', (string) $produto['editado_dependencia_id']);
        $this->assertSame('0', (string) $produto['imprimir_etiqueta']);
        $this->assertSame('0', (string) $produto['checado']);
        $this->assertSame('0', (string) $produto['editado']);
    }

    public function testLimparEdicoesNaoAfetaProdutoDeOutraComum(): void
    {
        // Produto na comum 1
        $idProduto = $this->inserirProdutoComEdicoes(comumId: 1);

        // Tenta limpar passando comum 2 — não deve afetar
        $this->repository->limparEdicoes($idProduto, 2);

        $stmt = $this->pdo->prepare("SELECT editado FROM produtos WHERE id_produto = :id");
        $stmt->execute([':id' => $idProduto]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        // Campo 'editado' deve continuar 1 (não foi afetado)
        $this->assertSame('1', (string) $produto['editado']);
    }

    public function testLimparEdicoesRetornaTrueParaProdutoInexistente(): void
    {
        // UPDATE em ID inexistente afeta 0 linhas mas execute() retorna true no SQLite
        $resultado = $this->repository->limparEdicoes(99999, 1);

        $this->assertTrue($resultado);
    }

    // ─── Schema e fixtures ───

    private function criarSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS produtos (
                id_produto          INTEGER PRIMARY KEY AUTOINCREMENT,
                comum_id            INTEGER NOT NULL DEFAULT 1,
                codigo              TEXT    NOT NULL DEFAULT '',
                tipo_bem_id         INTEGER NOT NULL DEFAULT 0,
                bem                 TEXT    NOT NULL DEFAULT '',
                complemento         TEXT    NOT NULL DEFAULT '',
                dependencia_id      INTEGER NOT NULL DEFAULT 0,
                editado_tipo_bem_id INTEGER NOT NULL DEFAULT 0,
                editado_bem         TEXT    NOT NULL DEFAULT '',
                editado_complemento TEXT    NOT NULL DEFAULT '',
                editado_dependencia_id INTEGER NOT NULL DEFAULT 0,
                novo                INTEGER NOT NULL DEFAULT 0,
                importado           INTEGER NOT NULL DEFAULT 0,
                checado             INTEGER NOT NULL DEFAULT 0,
                editado             INTEGER NOT NULL DEFAULT 0,
                imprimir_etiqueta   INTEGER NOT NULL DEFAULT 0,
                imprimir_14_1       INTEGER NOT NULL DEFAULT 0,
                condicao_14_1       TEXT    NOT NULL DEFAULT '',
                observacao          TEXT    NOT NULL DEFAULT '',
                nota_numero         TEXT,
                nota_data           TEXT,
                nota_valor          TEXT,
                nota_fornecedor     TEXT    NOT NULL DEFAULT '',
                administrador_acessor_id INTEGER,
                ativo               INTEGER NOT NULL DEFAULT 1
            )
        ");
    }

    private function inserirProdutoComEdicoes(int $comumId = 1): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO produtos
                (comum_id, codigo, tipo_bem_id, bem, complemento, dependencia_id,
                 editado_tipo_bem_id, editado_bem, editado_complemento, editado_dependencia_id,
                 imprimir_etiqueta, checado, imprimir_14_1, condicao_14_1,
                 nota_numero, nota_data, nota_valor, nota_fornecedor, editado)
            VALUES
                (:comum_id, 'TST-001', 1, 'CADEIRA', 'ALMOFADADA', 1,
                 5, 'BANCO', 'EDITADO', 3,
                 1, 1, 1, 'BOM',
                 '123', '2026-01-01', '150.00', 'FORNECEDOR X', 1)
        ");
        $stmt->execute([':comum_id' => $comumId]);
        return (int) $this->pdo->lastInsertId();
    }
}

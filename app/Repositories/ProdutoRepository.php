<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class ProdutoRepository extends BaseRepository
{
    protected string $tabela = 'produtos';
    protected string $chavePrimaria = 'id_produto';

    /** @var string[] Whitelist de colunas permitidas para INSERT/UPDATE */
    protected array $colunas = [
        'comum_id',
        'codigo',
        'tipo_bem_id',
        'bem',
        'complemento',
        'dependencia_id',
        'editado_tipo_bem_id',
        'editado_bem',
        'editado_complemento',
        'editado_dependencia_id',
        'novo',
        'importado',
        'checado',
        'editado',
        'imprimir_etiqueta',
        'imprimir_14_1',
        'condicao_14_1',
        'observacao',
        'nota_numero',
        'nota_data',
        'nota_valor',
        'nota_fornecedor',
        'administrador_acessor_id',
        'ativo',
    ];

    public function buscarPorComumPaginado(
        int $comumId,
        int $pagina = 1,
        int $limite = 10,
        array $filtros = []
    ): array {
        $offset = ($pagina - 1) * $limite;
        $where = ['p.comum_id = :comum_id'];
        $params = [':comum_id' => $comumId];

        if (!empty($filtros['filtro_complemento'])) {
            $where[] = '(p.bem LIKE :complemento OR p.complemento LIKE :complemento)';
            $params[':complemento'] = '%' . $filtros['filtro_complemento'] . '%';
        }

        if (!empty($filtros['pesquisa_id'])) {
            $where[] = 'p.id_produto = :pesquisa_id';
            $params[':pesquisa_id'] = (int) $filtros['pesquisa_id'];
        }

        if (!empty($filtros['filtro_tipo_ben'])) {
            $where[] = 'tb.codigo = :tipo_ben';
            $params[':tipo_ben'] = $filtros['filtro_tipo_ben'];
        }

        if (!empty($filtros['filtro_bem'])) {
            $where[] = 'p.codigo = :bem';
            $params[':bem'] = $filtros['filtro_bem'];
        }

        if (!empty($filtros['filtro_dependencia'])) {
            $where[] = 'p.dependencia_id = :dependencia';
            $params[':dependencia'] = (int) $filtros['filtro_dependencia'];
        }

        $filtroStatus = $filtros['filtro_STATUS'] ?? $filtros['filtro_status'] ?? '';
        if ($filtroStatus !== '') {
            switch ((string) $filtroStatus) {
                case 'com_nota':
                    $where[] = 'p.nota_numero IS NOT NULL AND p.nota_numero != ""';
                    break;
                case 'com_14_1':
                    $where[] = 'p.imprimir_14_1 = 1';
                    break;
                case 'sem_status':
                    $where[] = '(p.nota_numero IS NULL OR p.nota_numero = "") AND COALESCE(p.imprimir_14_1, 0) = 0';
                    break;
            }
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM produtos p 
                     LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id 
                     WHERE {$whereSql}";
        $stmtCount = $this->conexao->prepare($countSql);
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v);
        }
        $stmtCount->execute();
        $total = (int) $stmtCount->fetchColumn();

        $sql = "SELECT p.*, tb.codigo AS tipo_bem_codigo, tb.descricao AS tipo_bem_descricao,
                       d.descricao AS dependencia_descricao
                FROM produtos p
                LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                LEFT JOIN dependencias d ON p.dependencia_id = d.id
                WHERE {$whereSql}
                ORDER BY p.id_produto ASC
                LIMIT " . (int) $limite . " OFFSET " . (int) $offset;

        $stmt = $this->conexao->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;

        return [
            'dados' => $dados,
            'total' => $total,
            'pagina' => $pagina,
            'limite' => $limite,
            'totalPaginas' => $totalPaginas,
        ];
    }

    public function atualizarObservacao(int $produtoId, int $comumId, string $observacao): bool
    {
        $sql = "UPDATE {$this->tabela} SET observacao = :obs WHERE id_produto = :id AND comum_id = :comum_id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':obs', $observacao, PDO::PARAM_STR);
        $stmt->bindValue(':id', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function atualizarPorIdEComum(int $produtoId, int $comumId, array $dados): bool
    {
        $dados = array_intersect_key($dados, array_flip($this->colunas));
        if ($dados === []) {
            return false;
        }

        $sets = [];
        foreach (array_keys($dados) as $coluna) {
            $sets[] = "{$coluna} = :{$coluna}";
        }

        $sql = "UPDATE {$this->tabela}
                SET " . implode(', ', $sets) . "
                WHERE id_produto = :id_produto
                  AND comum_id = :comum_id";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id_produto', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);

        foreach ($dados as $chave => $valor) {
            $stmt->bindValue(':' . $chave, $valor);
        }

        return $stmt->execute();
    }

    public function atualizarChecado(int $produtoId, int $comumId, int $checado): bool
    {
        $sql = "UPDATE {$this->tabela} SET checado = :checado WHERE id_produto = :id AND comum_id = :comum_id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':checado', $checado, PDO::PARAM_INT);
        $stmt->bindValue(':id', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function atualizarEtiqueta(int $produtoId, int $comumId, int $imprimir): bool
    {
        $sql = "UPDATE {$this->tabela} SET imprimir_etiqueta = :imprimir WHERE id_produto = :id AND comum_id = :comum_id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':imprimir', $imprimir, PDO::PARAM_INT);
        $stmt->bindValue(':id', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function atualizarAdministradorAcessorEmLote(array $idsProdutos, int $comumId, ?int $usuarioId): int
    {
        $idsProdutos = array_values(array_filter(array_map('intval', $idsProdutos), static fn(int $id): bool => $id > 0));
        if (empty($idsProdutos)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($idsProdutos), '?'));
        $sql = "UPDATE {$this->tabela}
                SET administrador_acessor_id = ?
                WHERE comum_id = ?
                  AND id_produto IN ({$placeholders})";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(1, $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(2, $comumId, PDO::PARAM_INT);

        foreach ($idsProdutos as $index => $idProduto) {
            $stmt->bindValue($index + 3, $idProduto, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->rowCount();
    }

    public function buscarPorComumId(int $comumId): array
    {
        $sql = "SELECT p.*, tb.codigo AS tipo_bem_codigo, tb.descricao AS tipo_bem_descricao,
                       d.descricao AS dependencia_descricao
                FROM {$this->tabela} p
                LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                LEFT JOIN dependencias d ON p.dependencia_id = d.id
                WHERE p.comum_id = :comum_id
                ORDER BY p.id_produto ASC";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorIdEComum(int $idProduto, int $comumId): ?array
    {
        $sql = "SELECT p.*, tb.codigo AS tipo_bem_codigo, tb.descricao AS tipo_bem_descricao,
                       d.descricao AS dependencia_descricao
                FROM {$this->tabela} p
                LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                LEFT JOIN dependencias d ON p.dependencia_id = d.id
                WHERE p.id_produto = :id_produto
                  AND p.comum_id = :comum_id
                LIMIT 1";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    public function desativarPorIdEComum(int $idProduto, int $comumId): bool
    {
        $sql = "UPDATE {$this->tabela}
                SET ativo = 0
                WHERE id_produto = :id_produto
                  AND comum_id = :comum_id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function desativarEmLotePorComum(array $idsProdutos, int $comumId): int
    {
        $idsProdutos = array_values(array_filter(array_map('intval', $idsProdutos), static fn(int $id): bool => $id > 0));
        if (empty($idsProdutos)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($idsProdutos), '?'));
        $sql = "UPDATE {$this->tabela}
                SET ativo = 0
                WHERE comum_id = ?
                  AND id_produto IN ({$placeholders})";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(1, $comumId, PDO::PARAM_INT);

        foreach ($idsProdutos as $index => $idProduto) {
            $stmt->bindValue($index + 2, $idProduto, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->rowCount();
    }

    public function contarPorComum(int $comumId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->tabela} WHERE comum_id = :comum_id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function buscarDistintosCodigos(int $comumId): array
    {
        $sql = "SELECT DISTINCT codigo FROM {$this->tabela} WHERE comum_id = :comum_id AND codigo IS NOT NULL ORDER BY codigo";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Buscar produtos para a view de planilha com JOINs completos e filtros.
     */
    public function buscarParaPlanilha(int $comumId, int $pagina, int $limite, array $filtros = []): array
    {
        $offset = ($pagina - 1) * $limite;
        $where = ['p.comum_id = :comum_id'];
        $params = [':comum_id' => $comumId];

        if (!empty($filtros['nome'])) {
            $where[] = '(p.bem LIKE :nome_bem OR p.complemento LIKE :nome_comp)';
            $params[':nome_bem'] = '%' . $filtros['nome'] . '%';
            $params[':nome_comp'] = '%' . $filtros['nome'] . '%';
        }

        if (!empty($filtros['dependencia'])) {
            $where[] = 'p.dependencia_id = :dependencia';
            $params[':dependencia'] = (int) $filtros['dependencia'];
        }

        if (!empty($filtros['codigo'])) {
            $where[] = 'p.codigo LIKE :codigo';
            $params[':codigo'] = '%' . $filtros['codigo'] . '%';
        }

        $status = $filtros['status'] ?? '';
        if ($status === 'checado') {
            $where[] = 'p.checado = 1';
        } elseif ($status === 'observacao') {
            $where[] = 'p.observacao != ""';
        } elseif ($status === 'etiqueta') {
            $where[] = 'p.imprimir_etiqueta = 1';
        } elseif ($status === 'pendente') {
            $where[] = 'p.checado = 0';
        } elseif ($status === 'editado') {
            $where[] = 'p.editado = 1';
        }

        $whereSql = implode(' AND ', $where);

        // Count
        $stmtCount = $this->conexao->prepare("SELECT COUNT(*) FROM produtos p WHERE {$whereSql}");
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v);
        }
        $stmtCount->execute();
        $total = (int) $stmtCount->fetchColumn();
        $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;

        // Data with JOINs
        $sql = "SELECT p.*, 
                       tb.codigo AS tipo_codigo, 
                       tb.descricao AS tipo_desc,
                       etb.codigo AS editado_tipo_codigo,
                       etb.descricao AS editado_tipo_desc,
                       d.descricao AS dependencia_desc,
                       COALESCE(ed.descricao, '') AS editado_dependencia_desc
                FROM produtos p
                LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                LEFT JOIN tipos_bens etb ON p.editado_tipo_bem_id = etb.id
                LEFT JOIN dependencias d ON p.dependencia_id = d.id
                LEFT JOIN dependencias ed ON p.editado_dependencia_id = ed.id
                WHERE {$whereSql} 
                ORDER BY p.codigo ASC 
                LIMIT " . (int) $limite . " OFFSET " . (int) $offset;

        $stmt = $this->conexao->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'dados'        => $dados,
            'total'        => $total,
            'pagina'       => $pagina,
            'totalPaginas' => $totalPaginas,
        ];
    }

    /**
     * Busca produtos novos (novo = 1), paginado
     */
    public function buscarProdutosNovosPaginado(
        int $comumId,
        string $busca = '',
        int $pagina = 1,
        int $limite = 10
    ): array {
        $offset = ($pagina - 1) * $limite;
        $where = ['p.comum_id = :comum_id', 'p.novo = 1'];
        $params = [':comum_id' => $comumId];

        if (!empty($busca)) {
            $where[] = '(p.bem LIKE :busca OR p.complemento LIKE :busca)';
            $params[':busca'] = '%' . $busca . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM produtos p WHERE {$whereSql}";
        $stmtCount = $this->conexao->prepare($countSql);
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v);
        }
        $stmtCount->execute();
        $total = (int) $stmtCount->fetchColumn();

        $sql = "SELECT p.*,
                       tb.descricao AS tipo_bem_descricao,
                       d.descricao AS dependencia_descricao,
                       d.descricao AS dependencia_desc
                FROM produtos p
                LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                LEFT JOIN dependencias d ON p.dependencia_id = d.id
                WHERE {$whereSql}
                ORDER BY p.id_produto DESC
                LIMIT " . (int) $limite . " OFFSET " . (int) $offset;

        $stmt = $this->conexao->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;

        return [
            'dados' => $dados,
            'total' => $total,
            'pagina' => $pagina,
            'limite' => $limite,
            'totalPaginas' => $totalPaginas,
        ];
    }

    /**
     * Limpa as edições de um produto
     */
    public function limparEdicoes(int $idProduto, int $comumId): void
    {
        $sql = "UPDATE produtos
                SET editado_tipo_bem_id = 0,
                    editado_bem = '',
                    editado_complemento = '',
                    editado_dependencia_id = 0,
                    imprimir_etiqueta = 0,
                    checado = 0,
                    imprimir_14_1 = 0,
                    condicao_14_1 = '',
                    nota_numero = NULL,
                    nota_data = NULL,
                    nota_valor = NULL,
                    nota_fornecedor = '',
                    editado = 0
                WHERE id_produto = :id_produto
                  AND comum_id = :comum_id";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
        $stmt->execute();
    }
}

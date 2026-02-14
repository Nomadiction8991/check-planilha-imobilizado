<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class ProdutoRepository extends BaseRepository
{
    protected string $tabela = 'produtos';
    protected string $chavePrimaria = 'id_produto';

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
            $where[] = 'p.descricao LIKE :complemento';
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
            $where[] = 'p.codigo_bem = :bem';
            $params[':bem'] = $filtros['filtro_bem'];
        }

        if (!empty($filtros['filtro_dependencia'])) {
            $where[] = 'p.dependencia_id = :dependencia';
            $params[':dependencia'] = (int) $filtros['filtro_dependencia'];
        }

        if (isset($filtros['filtro_STATUS']) && $filtros['filtro_STATUS'] !== '') {
            $where[] = 'p.checado = :status';
            $params[':status'] = (int) $filtros['filtro_STATUS'];
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
        $sql = "SELECT DISTINCT codigo_bem FROM {$this->tabela} WHERE comum_id = :comum_id AND codigo_bem IS NOT NULL ORDER BY codigo_bem";
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
            $where[] = '(p.descricao_completa LIKE :nome OR p.bem LIKE :nome)';
            $params[':nome'] = '%' . $filtros['nome'] . '%';
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
                       d.descricao AS dependencia_desc,
                       COALESCE(ed.descricao, '') AS editado_dependencia_desc
                FROM produtos p
                LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
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
}

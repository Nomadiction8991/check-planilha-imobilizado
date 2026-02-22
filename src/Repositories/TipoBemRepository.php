<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class TipoBemRepository extends BaseRepository
{
    protected string $tabela = 'tipos_bens';

    public function criar(array $dados): int
    {
        // Auto-gera codigo como próximo valor sequencial
        $maxStmt = $this->conexao->query("SELECT COALESCE(MAX(codigo), 0) FROM {$this->tabela}");
        $proximoCodigo = (int) $maxStmt->fetchColumn() + 1;

        $sql = "INSERT INTO {$this->tabela} (codigo, descricao) 
                VALUES (:codigo, :descricao)";

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([
            'codigo'   => $proximoCodigo,
            'descricao' => $dados['descricao']
        ]);

        return (int) $this->conexao->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = "UPDATE {$this->tabela} 
                SET descricao = :descricao 
                WHERE id = :id";

        $stmt = $this->conexao->prepare($sql);
        return $stmt->execute([
            'id'       => $id,
            'descricao' => $dados['descricao']
        ]);
    }

    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM {$this->tabela} WHERE id = :id";
        $stmt = $this->conexao->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE id = :id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function buscarPorCodigo(int $codigo): ?array
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE codigo = :codigo";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute(['codigo' => $codigo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function buscarPaginado(string $busca = '', int $limite = 20, int $offset = 0): array
    {
        $where = '';
        $params = [];

        if (!empty($busca)) {
            $where = "WHERE descricao LIKE :busca_desc OR codigo LIKE :busca_cod";
            $params[':busca_desc'] = "%{$busca}%";
            $params[':busca_cod'] = "%{$busca}%";
        }

        $sql = "SELECT * FROM {$this->tabela} 
                {$where} 
                ORDER BY codigo ASC 
                LIMIT " . (int)$limite . " OFFSET " . (int)$offset;

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarComFiltro(string $busca = ''): int
    {
        $where = '';
        $params = [];

        if (!empty($busca)) {
            $where = "WHERE descricao LIKE :busca_desc OR codigo LIKE :busca_cod";
            $params[':busca_desc'] = "%{$busca}%";
            $params[':busca_cod'] = "%{$busca}%";
        }

        $sql = "SELECT COUNT(*) FROM {$this->tabela} {$where}";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function buscarTodos(): array
    {
        $sql = "SELECT * FROM {$this->tabela} ORDER BY codigo ASC";
        $stmt = $this->conexao->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

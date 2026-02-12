<?php

namespace App\Repositories;

use PDO;
use Exception;

class DependenciaRepository extends BaseRepository
{
    protected string $tabela = 'dependencias';

    public function buscarPaginado(string $busca = '', int $limite = 10, int $offset = 0): array
    {
        $params = [];
        $where = '';

        if ($busca !== '') {
            $where = "descricao LIKE :busca";
            $params[':busca'] = '%' . $busca . '%';
        }

        $sql = "SELECT * FROM {$this->tabela}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $sql .= " ORDER BY descricao ASC LIMIT :limite OFFSET :offset";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabela}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }

        $stmt = $this->conexao->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    public function buscarPorDescricao(string $descricao): ?array
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE descricao = :descricao";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':descricao', $descricao);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function criar(array $dados): int
    {
        $sql = "INSERT INTO {$this->tabela} (descricao) VALUES (:descricao)";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':descricao', $dados['descricao']);
        $stmt->execute();
        return (int) $this->conexao->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = "UPDATE {$this->tabela} SET descricao = :descricao WHERE id = :id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':descricao', $dados['descricao']);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM {$this->tabela} WHERE id = :id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE id = :id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}

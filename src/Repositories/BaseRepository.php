<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use PDO;
use PDOException;


abstract class BaseRepository implements RepositoryInterface
{
    protected PDO $conexao;
    protected string $tabela;
    protected string $chavePrimaria = 'id';

    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    
    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE {$this->chavePrimaria} = :id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    
    public function buscarTodos(): array
    {
        $sql = "SELECT * FROM {$this->tabela}";
        return $this->conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function criar(array $dados): int
    {
        $colunas = implode(', ', array_keys($dados));
        $placeholders = ':' . implode(', :', array_keys($dados));

        $sql = "INSERT INTO {$this->tabela} ({$colunas}) VALUES ({$placeholders})";
        $stmt = $this->conexao->prepare($sql);

        foreach ($dados as $chave => $valor) {
            $stmt->bindValue(":{$chave}", $valor);
        }

        $stmt->execute();
        return (int) $this->conexao->lastInsertId();
    }

    
    public function atualizar(int $id, array $dados): bool
    {
        $sets = [];
        foreach (array_keys($dados) as $coluna) {
            $sets[] = "{$coluna} = :{$coluna}";
        }
        $setSql = implode(', ', $sets);

        $sql = "UPDATE {$this->tabela} SET {$setSql} WHERE {$this->chavePrimaria} = :id";
        $stmt = $this->conexao->prepare($sql);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        foreach ($dados as $chave => $valor) {
            $stmt->bindValue(":{$chave}", $valor);
        }

        return $stmt->execute();
    }

    
    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM {$this->tabela} WHERE {$this->chavePrimaria} = :id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    
    public function contar(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->tabela}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }

        $stmt = $this->conexao->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    
    protected function paginar(int $pagina, int $limite, string $where = '', array $params = [], string $orderBy = ''): array
    {
        $offset = ($pagina - 1) * $limite;

        $sql = "SELECT * FROM {$this->tabela}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $sql .= " LIMIT :limite OFFSET :offset";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = $this->contar($where, $params);
        $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;

        return [
            'dados' => $dados,
            'total' => $total,
            'pagina' => $pagina,
            'limite' => $limite,
            'totalPaginas' => $totalPaginas
        ];
    }
}

<?php

namespace App\Repositories;

use PDO;

class TipoBemRepository extends BaseRepository
{
    protected string $table = 'tipos_bens';

    public function __construct(PDO $conexao)
    {
        parent::__construct($conexao);
    }

    public function criar(array $dados): int
    {
        $sql = "INSERT INTO {$this->table} (codigo, descricao) 
                VALUES (:codigo, :descricao)";

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([
            'codigo' => $dados['codigo'],
            'descricao' => $dados['descricao']
        ]);

        return (int) $this->conexao->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = "UPDATE {$this->table} 
                SET codigo = :codigo, descricao = :descricao 
                WHERE id = :id";

        $stmt = $this->conexao->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'codigo' => $dados['codigo'],
            'descricao' => $dados['descricao']
        ]);
    }

    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conexao->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function buscarPorCodigo(int $codigo): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE codigo = :codigo";
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
            $where = "WHERE descricao LIKE :busca OR codigo LIKE :busca";
            $params[':busca'] = "%{$busca}%";
        }

        $sql = "SELECT * FROM {$this->table} 
                {$where} 
                ORDER BY codigo ASC 
                LIMIT :limite OFFSET :offset";

        $stmt = $this->conexao->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarComFiltro(string $busca = ''): int
    {
        $where = '';
        $params = [];

        if (!empty($busca)) {
            $where = "WHERE descricao LIKE :busca OR codigo LIKE :busca";
            $params[':busca'] = "%{$busca}%";
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} {$where}";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function buscarTodos(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY codigo ASC";
        $stmt = $this->conexao->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

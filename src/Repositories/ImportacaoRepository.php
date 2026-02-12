<?php

namespace App\Repositories;

use PDO;

class ImportacaoRepository extends BaseRepository
{
    protected string $table = 'importacoes';

    public function criar(array $dados): int
    {
        $sql = "INSERT INTO {$this->table} 
                (usuario_id, comum_id, arquivo_nome, arquivo_caminho, total_linhas, status, iniciada_em) 
                VALUES (:usuario_id, :comum_id, :arquivo_nome, :arquivo_caminho, :total_linhas, :status, NOW())";

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $dados['usuario_id'],
            ':comum_id' => $dados['comum_id'],
            ':arquivo_nome' => $dados['arquivo_nome'],
            ':arquivo_caminho' => $dados['arquivo_caminho'],
            ':total_linhas' => $dados['total_linhas'],
            ':status' => $dados['status'] ?? 'aguardando'
        ]);

        return (int) $this->conexao->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sets = [];
        $params = [':id' => $id];

        if (isset($dados['linhas_processadas'])) {
            $sets[] = 'linhas_processadas = :linhas_processadas';
            $params[':linhas_processadas'] = $dados['linhas_processadas'];
        }

        if (isset($dados['linhas_sucesso'])) {
            $sets[] = 'linhas_sucesso = :linhas_sucesso';
            $params[':linhas_sucesso'] = $dados['linhas_sucesso'];
        }

        if (isset($dados['linhas_erro'])) {
            $sets[] = 'linhas_erro = :linhas_erro';
            $params[':linhas_erro'] = $dados['linhas_erro'];
        }

        if (isset($dados['porcentagem'])) {
            $sets[] = 'porcentagem = :porcentagem';
            $params[':porcentagem'] = $dados['porcentagem'];
        }

        if (isset($dados['status'])) {
            $sets[] = 'status = :status';
            $params[':status'] = $dados['status'];
        }

        if (isset($dados['mensagem_erro'])) {
            $sets[] = 'mensagem_erro = :mensagem_erro';
            $params[':mensagem_erro'] = $dados['mensagem_erro'];
        }

        if (isset($dados['concluida_em'])) {
            $sets[] = 'concluida_em = :concluida_em';
            $params[':concluida_em'] = $dados['concluida_em'];
        }

        if (empty($sets)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->conexao->prepare($sql);

        return $stmt->execute($params);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function buscarUltimaPorUsuario(int $usuarioId): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE usuario_id = :usuario_id 
                ORDER BY created_at DESC 
                LIMIT 1";

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function buscarEmAndamento(): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status IN ('aguardando', 'processando') 
                ORDER BY created_at ASC";

        $stmt = $this->conexao->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

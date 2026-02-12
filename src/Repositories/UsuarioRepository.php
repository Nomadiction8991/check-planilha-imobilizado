<?php

namespace App\Repositories;

use PDO;
use Exception;

/**
 * Repositório de Usuários
 * Gerencia acesso a dados da tabela 'usuarios'
 */
class UsuarioRepository extends BaseRepository
{
    protected string $tabela = 'usuarios';

    /**
     * Busca usuário por email (normalizado em uppercase)
     */
    public function buscarPorEmail(string $email): ?array
    {
        $emailUpper = mb_strtoupper($email, 'UTF-8');

        $sql = "SELECT * FROM {$this->tabela} WHERE UPPER(email) = :email";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':email', $emailUpper);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Busca usuário por CPF
     */
    public function buscarPorCpf(string $cpf): ?array
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE cpf = :cpf";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':cpf', $cpf);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Busca usuários com paginação e filtros
     */
    public function buscarPaginadoComFiltros(int $pagina, int $limite, array $filtros = []): array
    {
        $offset = ($pagina - 1) * $limite;
        $where = [];
        $params = [];

        // Filtro de busca (nome ou email)
        if (!empty($filtros['busca'])) {
            $where[] = '(LOWER(nome) LIKE :busca_nome OR LOWER(email) LIKE :busca_email)';
            $buscaLower = '%' . mb_strtolower($filtros['busca'], 'UTF-8') . '%';
            $params[':busca_nome'] = $buscaLower;
            $params[':busca_email'] = $buscaLower;
        }

        // Filtro de status (ativo/inativo)
        if (isset($filtros['status']) && $filtros['status'] !== '' && in_array($filtros['status'], ['0', '1'], true)) {
            $where[] = 'ativo = :status';
            $params[':status'] = $filtros['status'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // Contagem total (sem filtros)
        $totalGeral = (int) $this->conexao->query("SELECT COUNT(*) FROM {$this->tabela}")->fetchColumn();

        // Contagem com filtros
        $sqlCount = "SELECT COUNT(*) FROM {$this->tabela}" . $whereSql;
        $stmtCount = $this->conexao->prepare($sqlCount);
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $total = (int) $stmtCount->fetchColumn();

        // Busca paginada
        $sql = "SELECT * FROM {$this->tabela}" . $whereSql . " ORDER BY nome ASC LIMIT :limite OFFSET :offset";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;

        return [
            'dados' => $usuarios,
            'total' => $total,
            'totalGeral' => $totalGeral,
            'pagina' => $pagina,
            'limite' => $limite,
            'totalPaginas' => $totalPaginas
        ];
    }

    /**
     * Valida se email já existe (exceto para o próprio usuário em updates)
     */
    public function emailExiste(string $email, ?int $ignorarId = null): bool
    {
        $emailUpper = mb_strtoupper($email, 'UTF-8');

        $sql = "SELECT id FROM {$this->tabela} WHERE UPPER(email) = :email";
        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
        }

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':email', $emailUpper);
        if ($ignorarId !== null) {
            $stmt->bindValue(':ignorar_id', $ignorarId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Valida se CPF já existe (exceto para o próprio usuário em updates)
     */
    public function cpfExiste(string $cpf, ?int $ignorarId = null): bool
    {
        $sql = "SELECT id FROM {$this->tabela} WHERE cpf = :cpf";
        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
        }

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':cpf', $cpf);
        if ($ignorarId !== null) {
            $stmt->bindValue(':ignorar_id', $ignorarId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria usuário com validações
     */
    public function criarUsuario(array $dados): int
    {
        // Normalizar email para uppercase
        if (isset($dados['email'])) {
            $dados['email'] = mb_strtoupper($dados['email'], 'UTF-8');
        }

        // Hash da senha se fornecida
        if (isset($dados['senha'])) {
            $dados['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        }

        return $this->criar($dados);
    }

    /**
     * Atualiza usuário (com tratamento especial para senha)
     */
    public function atualizarUsuario(int $id, array $dados): bool
    {
        // Normalizar email para uppercase
        if (isset($dados['email'])) {
            $dados['email'] = mb_strtoupper($dados['email'], 'UTF-8');
        }

        // Hash da senha se fornecida (se vazia, não atualiza)
        if (isset($dados['senha'])) {
            if (trim($dados['senha']) === '') {
                unset($dados['senha']); // Não atualizar se vazia
            } else {
                $dados['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
            }
        }

        return $this->atualizar($id, $dados);
    }

    /**
     * Verifica autenticação do usuário
     */
    public function autenticar(string $email, string $senha): ?array
    {
        $usuario = $this->buscarPorEmail($email);

        if (!$usuario) {
            return null;
        }

        // Verifica senha
        if (!password_verify($senha, $usuario['senha'])) {
            return null;
        }

        // Verifica se está ativo
        if ($usuario['ativo'] != 1) {
            throw new Exception('Usuário inativo.');
        }

        return $usuario;
    }
}

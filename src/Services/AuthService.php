<?php

namespace App\Services;

use PDO;

class AuthService
{
    private PDO $conexao;

    public function __construct()
    {
        // Assumir que a conexão global está disponível, ou injetar
        global $conexao;
        $this->conexao = $conexao;
    }

    public function authenticate(string $email, string $senha): array
    {
        // Buscar usuário por email (comparacao em UPPER para ser robusto a case)
        // Não filtramos por ativo aqui para permitir mostrar mensagem específica quando inativo
        $stmt = $this->conexao->prepare('SELECT * FROM usuarios WHERE UPPER(email) = :email LIMIT 1');
        $stmt->bindValue(':email', to_uppercase($email));
        $stmt->execute();
        $usuario = $stmt->fetch();

        if (!$usuario) {
            throw new \Exception('E-mail ou senha inválidos.');
        }

        // Se usuário existe mas está inativo, informar especificamente
        if ((int)($usuario['ativo'] ?? 0) !== 1) {
            throw new \Exception('Usuário inativo. Entre em contato com o administrador.');
        }

        // Verificar senha
        if (!password_verify($senha, $usuario['senha'])) {
            throw new \Exception('E-mail ou senha inválidos.');
        }

        // Login bem-sucedido - definir sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];

        return $usuario;
    }
}

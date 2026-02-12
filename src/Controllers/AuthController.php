<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Repositories\UsuarioRepository;
use App\Core\ConnectionManager;

class AuthController
{
    private AuthService $authService;

    public function __construct(?AuthService $authService = null)
    {
        if ($authService === null) {
            $conexao = ConnectionManager::getConnection();
            $usuarioRepo = new UsuarioRepository($conexao);
            $authService = new AuthService($usuarioRepo);
        }
        $this->authService = $authService;
    }

    public function login()
    {
        if ($this->authService->isAuthenticated()) {
            header('Location: ../index.php');
            exit;
        }

        $erro = '';
        $sucesso = '';

        if (isset($_GET['registered'])) {
            $sucesso = 'Cadastro realizado com sucesso! Faça login para continuar.';
        }

        require __DIR__ . '/../Views/auth/login.php';
    }

    public function authenticate()
    {
        $email = to_uppercase(trim($_POST['email'] ?? ''));
        $senha = trim($_POST['senha'] ?? '');

        try {
            if (empty($email) || empty($senha)) {
                throw new \Exception('E-mail e senha são obrigatórios.');
            }

            $this->authService->authenticate($email, $senha);

            header('Location: ../index.php');
            exit;
        } catch (\Exception $e) {
            $erro = $e->getMessage();
            require __DIR__ . '/../Views/auth/login.php';
        }
    }
}

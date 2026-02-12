<?php

namespace App\Controllers;

use App\Services\AuthService;

class AuthController
{
    public function login()
    {
        // Se já está logado, redireciona para o index antigo
        if (isset($_SESSION['usuario_id'])) {
            header('Location: ../index.php');
            exit;
        }

        $erro = '';
        $sucesso = '';

        // Mensagem de sucesso ao registrar
        if (isset($_GET['registered'])) {
            $sucesso = 'Cadastro realizado com sucesso! Faça login para continuar.';
        }

        // Incluir a view
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

            $authService = new AuthService();
            $usuario = $authService->authenticate($email, $senha);

            // Login bem-sucedido - redirecionar para o código antigo
            header('Location: ../index.php');
            exit;
        } catch (\Exception $e) {
            $erro = $e->getMessage();
            // Incluir a view com erro
            require __DIR__ . '/../Views/auth/login.php';
        }
    }
}

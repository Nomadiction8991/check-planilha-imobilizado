<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Repositories\UsuarioRepository;
use App\Core\ConnectionManager;

/**
 * AuthController - Controlador de Autenticação
 * 
 * SOLID Principles:
 * - Single Responsibility: Gerencia APENAS fluxo HTTP de autenticação
 * - Dependency Inversion: Depende de AuthService (abstração)
 * 
 * Responsabilidades:
 * - Processar requisições HTTP (GET/POST)
 * - Validar entrada do usuário
 * - Delegar autenticação para AuthService
 * - Renderizar views ou redirecionar
 */
class AuthController
{
    private AuthService $authService;

    public function __construct(?AuthService $authService = null)
    {
        // Permite DI mas mantém backward compatibility
        if ($authService === null) {
            $conexao = ConnectionManager::getConnection();
            $usuarioRepo = new UsuarioRepository($conexao);
            $authService = new AuthService($usuarioRepo);
        }
        $this->authService = $authService;
    }

    public function login()
    {
        // Se já está logado, redireciona para o index
        if ($this->authService->isAuthenticated()) {
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

            $this->authService->authenticate($email, $senha);

            // Login bem-sucedido - redirecionar
            header('Location: ../index.php');
            exit;
        } catch (\Exception $e) {
            $erro = $e->getMessage();
            // Incluir a view com erro
            require __DIR__ . '/../Views/auth/login.php';
        }
    }
}

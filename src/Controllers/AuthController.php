<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\AuthenticationException;
use App\Services\AuthService;
use App\Repositories\UsuarioRepository;
use App\Core\ConnectionManager;
use App\Core\ViewRenderer;

class AuthController extends BaseController
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

    public function login(): void
    {
        if ($this->authService->isAuthenticated()) {
            $this->redirecionar('/products/view');
            return;
        }

        $erro = '';
        $sucesso = '';

        if (isset($_GET['registered'])) {
            $sucesso = 'Cadastro realizado com sucesso! Faça login para continuar.';
        }

        $this->renderizar('auth/login', [
            'erro' => $erro,
            'sucesso' => $sucesso,
        ]);
    }

    public function authenticate(): void
    {
        $email = mb_strtoupper(trim($this->post('email', '')), 'UTF-8');
        $senha = trim($this->post('senha', ''));

        try {
            if (empty($email) || empty($senha)) {
                throw new AuthenticationException('E-mail e senha são obrigatórios.');
            }

            $this->authService->authenticate($email, $senha);

            $this->redirecionar('/products/view');
        } catch (\Exception $e) {
            $this->renderizar('auth/login', [
                'erro' => $e->getMessage(),
                'sucesso' => '',
            ]);
        }
    }

    public function logout(): void
    {
        $this->authService->logout();
        $this->redirecionar('/login');
    }
}

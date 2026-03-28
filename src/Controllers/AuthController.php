<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\AuthenticationException;
use App\Services\AuthService;
use App\Services\RateLimiterService;
use App\Repositories\UsuarioRepository;
use App\Core\ConnectionManager;
use App\Core\ViewRenderer;

class AuthController extends BaseController
{
    private AuthService $authService;
    private RateLimiterService $rateLimiter;

    public function __construct(?AuthService $authService = null, ?RateLimiterService $rateLimiter = null)
    {
        if ($authService === null) {
            $conexao = ConnectionManager::getConnection();
            $usuarioRepo = new UsuarioRepository($conexao);
            $authService = new AuthService($usuarioRepo);
        }
        $this->authService = $authService;
        $this->rateLimiter = $rateLimiter ?? new RateLimiterService(5, 900); // 5 tentativas em 15 minutos
    }

    public function login(): void
    {
        if ($this->authService->isAuthenticated()) {
            $this->redirecionar('/products/view');
            return;
        }

        $erro = '';
        $sucesso = '';

        if ($this->query('registered') !== null) {
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

            // Verifica rate limiting por email
            if ($this->rateLimiter->isRateLimited($email)) {
                $remaining = $this->rateLimiter->getRemainingAttempts($email);
                throw new AuthenticationException('Muitas tentativas de login. Tente novamente em ' . $this->rateLimiter->getResetTime($email) . ' segundos.');
            }

            try {
                $this->authService->authenticate($email, $senha);
                // Login bem-sucedido, reseta tentativas
                $this->rateLimiter->reset($email);
                $redirectTarget = (string) \App\Core\SessionManager::get('redirect_after_login', '');
                \App\Core\SessionManager::remove('redirect_after_login');

                if ($redirectTarget !== '' && str_starts_with($redirectTarget, '/')) {
                    $this->redirecionar($redirectTarget);
                }

                $this->redirecionar('/products/view');
            } catch (AuthenticationException $e) {
                // Falha na autenticação, registra tentativa
                $this->rateLimiter->recordFailedAttempt($email);
                throw $e;
            }
        } catch (\Exception $e) {
            $this->renderizar('auth/login', [
                'erro' => $e->getMessage(),
                'sucesso' => '',
            ]);
        }
    }

    public function logout(): void
    {
        if (!$this->isPost()) {
            $this->redirecionar('/login');
            return;
        }

        $this->authService->logout();
        $this->redirecionar('/login');
    }
}

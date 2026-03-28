<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\SessionManager;

class AuthMiddleware
{
    private const SESSION_TIMEOUT = 1800;

    public function handle(): void
    {
        if ($this->hasSessionTimedOut()) {
            $this->handleTimeout();
            return;
        }

        $this->updateLastActivity();

        if ($this->isAuthenticated()) {
            return;
        }

        if ($this->isPublicAccess()) {
            return;
        }

        $this->saveRedirectTarget();
        $this->logRedirect('not logged in');
        $this->redirectToLogin();
    }

    public function isAuthenticated(): bool
    {
        return SessionManager::isAuthenticated();
    }

    private function isPublicAccess(): bool
    {
        return !empty($_SESSION['public_acesso'])
            && (!empty($_SESSION['public_comum_id']) || !empty($_SESSION['public_planilha_id']));
    }

    public function hasSessionTimedOut(): bool
    {
        if (!SessionManager::has('last_activity')) {
            return false;
        }

        $inactiveTime = time() - (int) SessionManager::get('last_activity', 0);
        return $inactiveTime > self::SESSION_TIMEOUT;
    }

    private function updateLastActivity(): void
    {
        SessionManager::set('last_activity', time());
    }

    private function handleTimeout(): void
    {
        SessionManager::destroy();
        $this->logRedirect("session timeout");
        $this->redirectToLogin(['timeout' => '1']);
    }

    private function saveRedirectTarget(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        // SEGURANÇA: Sanitizar URI para evitar exposição de dados sensíveis em logs/cache
        $uri = preg_replace('/[?&](password|token|api_key|secret)=[^&]*/i', '', $uri);
        SessionManager::set('redirect_after_login', $uri);
    }

    private function redirectToLogin(array $queryParams = []): void
    {
        $loginUrl = '/login';

        if (!empty($queryParams)) {
            $loginUrl .= '?' . http_build_query($queryParams);
        }

        header("Location: {$loginUrl}");
        exit;
    }

    /**
     * Registra redirecionamentos sem expor dados sensíveis (session IDs, tokens, etc)
     * SEGURANÇA: Logs sanitizados para evitar exposição de informações confidenciais
     */
    private function logRedirect(string $reason): void
    {
        // Não registra script_name completo para evitar exposição de caminho completo
        error_log("AUTH_REDIRECT: {$reason}");
    }

    public function getUserId(): ?int
    {
        return SessionManager::getUserId();
    }
}

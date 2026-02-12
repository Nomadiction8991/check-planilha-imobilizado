<?php

namespace App\Middleware;

use App\Core\SessionManager;

require_once __DIR__ . '/../Core/SessionManager.php';
require_once __DIR__ . '/../Services/AuthService.php';


class AuthMiddleware
{

    private const SESSION_TIMEOUT = 1800;


    private const PUBLIC_ROUTES = [
        '/src/Views/planilhas/relatorio141_view.php',
        '/src/Views/planilhas/relatorio_imprimir_alteracao.php',
    ];

    public function __construct()
    {
        // SessionManager é usado estaticamente
    }


    public function handle(): void
    {

        if (defined('SKIP_AUTH') && SKIP_AUTH === true) {
            return;
        }


        if ($this->hasSessionTimedOut()) {
            $this->handleTimeout();
            return;
        }


        $this->updateLastActivity();


        if ($this->isAuthenticated()) {
            return;
        }


        if ($this->isPublicAccess()) {
            if ($this->isPublicRoute()) {
                return;
            }


            $this->logRedirect('missing session usuario_id; is_public=1');
            $this->redirectToLogin();
            return;
        }


        $this->saveRedirectTarget();
        $this->logRedirect('not logged in');
        $this->redirectToLogin();
    }


    public function isAuthenticated(): bool
    {
        return isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] > 0;
    }


    private function isPublicAccess(): bool
    {
        return !empty($_SESSION['public_acesso'])
            && (!empty($_SESSION['public_comum_id']) || !empty($_SESSION['public_planilha_id']));
    }


    private function isPublicRoute(): bool
    {
        $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $root = realpath(__DIR__ . '/../..');

        foreach (self::PUBLIC_ROUTES as $route) {
            $fullPath = realpath($root . $route);
            if ($fullPath && $scriptFile === $fullPath) {
                return true;
            }
        }

        return false;
    }


    public function hasSessionTimedOut(): bool
    {
        if (!isset($_SESSION['last_activity'])) {
            return false;
        }

        $inactiveTime = time() - (int) $_SESSION['last_activity'];
        return $inactiveTime > self::SESSION_TIMEOUT;
    }


    private function updateLastActivity(): void
    {
        $_SESSION['last_activity'] = time();
    }


    private function handleTimeout(): void
    {
        $sessionId = session_id();

        session_unset();
        session_destroy();

        $this->logRedirect("session timeout for session_id={$sessionId}");
        $this->redirectToLogin(['timeout' => '1']);
    }


    private function saveRedirectTarget(): void
    {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
    }


    private function redirectToLogin(array $queryParams = []): void
    {
        $loginUrl = $this->getLoginUrl();

        if (!empty($queryParams)) {
            $loginUrl .= '?' . http_build_query($queryParams);
        }

        header("Location: {$loginUrl}");
        exit;
    }


    private function getLoginUrl(): string
    {
        $prefix = '';

        if (defined('BASE_PATH')) {
            $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
            $basePath = realpath(BASE_PATH);

            if ($docRoot && $basePath && strpos($basePath, $docRoot) === 0) {
                $prefix = trim(str_replace($docRoot, '', $basePath), '/');
            }
        }

        $segments = array_filter([$prefix, 'login.php'], 'strlen');
        $path = '/' . implode('/', $segments);

        return preg_replace('#/+#', '/', $path);
    }


    private function logRedirect(string $reason): void
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? 'unknown';
        $sessionId = session_id();
        $redirectTarget = $_SESSION['redirect_after_login'] ?? 'none';

        error_log("AUTH_REDIRECT: {$reason}; script={$scriptName}; session_id={$sessionId}; redirect_after_login={$redirectTarget}");
    }


    public function isAdmin(): bool
    {
        return $this->isAuthenticated();
    }


    public function isDoador(): bool
    {
        return $this->isAuthenticated();
    }


    public function getUserId(): ?int
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return (int) $_SESSION['usuario_id'];
    }


    public static function getInstance(): self
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }
}

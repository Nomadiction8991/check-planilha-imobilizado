<?php

require_once __DIR__ . '/../Core/SessionManager.php';
require_once __DIR__ . '/../Services/AuthService.php';

/**
 * Auth Middleware - Middleware de autenticação e autorização
 * 
 * Gerencia controle de acesso, timeout de sessão, rotas públicas
 * e redirecionamentos de autenticação.
 * 
 * Este middleware deve ser incluído no início de páginas protegidas.
 * 
 * @package App\Middleware
 * @version 1.0.0
 */
class AuthMiddleware
{
    private SessionManager $sessionManager;

    /**
     * Timeout de sessão em segundos (30 minutos)
     */
    private const SESSION_TIMEOUT = 1800;

    /**
     * Rotas públicas permitidas (acesso sem autenticação padrão)
     * 
     * @var array
     */
    private const PUBLIC_ROUTES = [
        '/app/views/shared/menu_unificado.php',
        '/app/views/planilhas/relatorio141_view.php',
        '/app/views/planilhas/relatorio_imprimir_alteracao.php',
        '/src/Views/shared/menu_unificado.php',
        '/src/Views/planilhas/relatorio141_view.php',
        '/src/Views/planilhas/relatorio_imprimir_alteracao.php',
    ];

    public function __construct(?SessionManager $sessionManager = null)
    {
        if ($sessionManager === null) {
            $sessionManager = SessionManager::getInstance();
        }
        $this->sessionManager = $sessionManager;
    }

    /**
     * Executa verificação de autenticação
     * 
     * Verifica:
     * 1. Se SKIP_AUTH está definido (bypass para páginas públicas)
     * 2. Se usuário está autenticado
     * 3. Se é acesso público válido
     * 4. Se sessão não expirou por timeout
     * 
     * Redireciona para login se necessário.
     * 
     * @return void
     */
    public function handle(): void
    {
        // Permite bypass de autenticação para páginas públicas específicas
        if (defined('SKIP_AUTH') && SKIP_AUTH === true) {
            return;
        }

        // Verifica timeout de sessão
        if ($this->hasSessionTimedOut()) {
            $this->handleTimeout();
            return;
        }

        // Atualiza última atividade
        $this->updateLastActivity();

        // Verifica se usuário está autenticado
        if ($this->isAuthenticated()) {
            return;
        }

        // Modo público: permite acesso restrito a algumas páginas
        if ($this->isPublicAccess()) {
            if ($this->isPublicRoute()) {
                return;
            }

            // Rota pública não permitida - redireciona
            $this->logRedirect('missing session usuario_id; is_public=1');
            $this->redirectToLogin();
            return;
        }

        // Não autenticado e não é acesso público - redireciona
        $this->saveRedirectTarget();
        $this->logRedirect('not logged in');
        $this->redirectToLogin();
    }

    /**
     * Verifica se usuário está autenticado
     * 
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] > 0;
    }

    /**
     * Verifica se é acesso público (sem autenticação de usuário)
     * 
     * @return bool
     */
    private function isPublicAccess(): bool
    {
        return !empty($_SESSION['public_acesso'])
            && (!empty($_SESSION['public_comum_id']) || !empty($_SESSION['public_planilha_id']));
    }

    /**
     * Verifica se rota atual é permitida para acesso público
     * 
     * @return bool
     */
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

    /**
     * Verifica se sessão expirou por timeout (30 minutos de inatividade)
     * 
     * @return bool
     */
    public function hasSessionTimedOut(): bool
    {
        if (!isset($_SESSION['last_activity'])) {
            return false;
        }

        $inactiveTime = time() - (int) $_SESSION['last_activity'];
        return $inactiveTime > self::SESSION_TIMEOUT;
    }

    /**
     * Atualiza timestamp de última atividade
     * 
     * @return void
     */
    private function updateLastActivity(): void
    {
        $_SESSION['last_activity'] = time();
    }

    /**
     * Trata timeout de sessão
     * 
     * @return void
     */
    private function handleTimeout(): void
    {
        $sessionId = session_id();

        session_unset();
        session_destroy();

        $this->logRedirect("session timeout for session_id={$sessionId}");
        $this->redirectToLogin(['timeout' => '1']);
    }

    /**
     * Salva URL de destino para redirecionamento após login
     * 
     * @return void
     */
    private function saveRedirectTarget(): void
    {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Redireciona para página de login
     * 
     * @param array $queryParams Parâmetros adicionais para URL
     * @return void (never returns - executa exit)
     */
    private function redirectToLogin(array $queryParams = []): void
    {
        $loginUrl = $this->getLoginUrl();

        if (!empty($queryParams)) {
            $loginUrl .= '?' . http_build_query($queryParams);
        }

        header("Location: {$loginUrl}");
        exit;
    }

    /**
     * Gera URL de login baseada na profundidade do diretório
     * 
     * @return string
     */
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

    /**
     * Loga motivo do redirecionamento (debug)
     * 
     * @param string $reason Motivo do redirecionamento
     * @return void
     */
    private function logRedirect(string $reason): void
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? 'unknown';
        $sessionId = session_id();
        $redirectTarget = $_SESSION['redirect_after_login'] ?? 'none';

        error_log("AUTH_REDIRECT: {$reason}; script={$scriptName}; session_id={$sessionId}; redirect_after_login={$redirectTarget}");
    }

    /**
     * Verifica se usuário é administrador
     * 
     * Por decisão do projeto, todos os usuários autenticados são administradores.
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Verifica se usuário é doador
     * 
     * Por decisão do projeto, todos os usuários autenticados são doadores.
     * 
     * @return bool
     */
    public function isDoador(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Obtem ID do usuário autenticado
     * 
     * @return int|null
     */
    public function getUserId(): ?int
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return (int) $_SESSION['usuario_id'];
    }

    /**
     * Factory method para criar instância singleton
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }
}

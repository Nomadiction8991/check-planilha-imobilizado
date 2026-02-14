<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

use App\Core\ConnectionManager;
use App\Core\CsrfService;
use App\Core\SessionManager;
use App\Middleware\AuthMiddleware;
use App\Repositories\ComumRepository;
use App\Repositories\UsuarioRepository;
use App\Routes\MapaRotas;
use App\Services\UserSessionService;

$rotas = MapaRotas::obter();
$metodo = $_SERVER['REQUEST_METHOD'];
$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$caminho = rtrim($caminho, '/') ?: '/';

// Match route with parameter support (:id → dynamic segments)
$chaveRota = null;
$rotaParams = [];

$chaveExata = $metodo . ' ' . $caminho;
if (isset($rotas[$chaveExata])) {
    $chaveRota = $chaveExata;
} else {
    foreach ($rotas as $rotaKey => $rotaHandler) {
        if (strpos($rotaKey, ':') === false) {
            continue;
        }
        [$rotaMetodo, $rotaPath] = explode(' ', $rotaKey, 2);
        if ($rotaMetodo !== $metodo) {
            continue;
        }
        $pattern = preg_replace('#:([a-zA-Z_]+)#', '([^/]+)', $rotaPath);
        $pattern = '#^' . $pattern . '$#';
        if (preg_match($pattern, $caminho, $matches)) {
            $chaveRota = $rotaKey;
            preg_match_all('#:([a-zA-Z_]+)#', $rotaPath, $paramNames);
            foreach ($paramNames[1] as $i => $nome) {
                $rotaParams[$nome] = $matches[$i + 1];
            }
            break;
        }
    }
}

if ($chaveRota === null) {
    http_response_code(404);
    echo 'Página não encontrada: ' . htmlspecialchars($caminho, ENT_QUOTES, 'UTF-8');
    exit();
}

[$classeControlador, $acao] = $rotas[$chaveRota];

// Route params available via $_GET
foreach ($rotaParams as $nome => $valor) {
    $_GET[$nome] = $valor;
}

// Public routes (skip auth)
$rotasPublicas = [
    'GET /',
    'GET /login',
    'POST /login',
    'GET /logout',
];

// AuthMiddleware for protected routes
if (!in_array($chaveRota, $rotasPublicas, true)) {
    $authMiddleware = new AuthMiddleware();
    $authMiddleware->handle();

    // Ensure comum_id is resolved once per request
    $conexao = ConnectionManager::getConnection();
    $userSessionService = new UserSessionService(
        new UsuarioRepository($conexao),
        new ComumRepository($conexao)
    );
    $userSessionService->ensureComumId();
}

// CSRF validation for POST (except login)
if ($metodo === 'POST' && $chaveRota !== 'POST /login') {
    $csrfToken = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!CsrfService::validate($csrfToken)) {
        if (is_ajax_request()) {
            json_response(['error' => true, 'message' => 'Token CSRF inválido. Recarregue a página.'], 403);
        }
        http_response_code(403);
        echo 'Token de segurança inválido. Recarregue a página e tente novamente.';
        exit;
    }
}

// Dependency injection
$conexao ??= ConnectionManager::getConnection();

if ($classeControlador === 'App\Controllers\AuthController') {
    $controlador = new $classeControlador();
} else {
    $controlador = new $classeControlador($conexao);
}

$resposta = $controlador->$acao();

if (is_string($resposta)) {
    echo $resposta;
}

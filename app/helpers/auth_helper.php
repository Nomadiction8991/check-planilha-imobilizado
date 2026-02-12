<?php

/**
 * @deprecated Use AuthMiddleware em src/Middleware/AuthMiddleware.php
 * @see AuthMiddleware
 */

// Permite pular autenticação em páginas públicas/controladas
if (defined('SKIP_AUTH') && SKIP_AUTH === true) {
    return;
}

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/Middleware/AuthMiddleware.php';

// Logar mas nao exibir erros em producao
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Inicializa middleware e executa verificação
$authMiddleware = AuthMiddleware::getInstance();
$authMiddleware->handle();

// Funções de compatibilidade (delegam para AuthMiddleware)

/**
 * URL de login baseada na profundidade do diretorio
 * @deprecated Use AuthMiddleware internamente
 */
function getLoginUrl(): string
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
 * Verifica se o usuario é Administrador/Acessor
 * @deprecated Use AuthMiddleware::isAdmin()
 */
function isAdmin(): bool
{
    return AuthMiddleware::getInstance()->isAdmin();
}

/**
 * Verifica se o usuario é Doador/Cônjuge
 * @deprecated Use AuthMiddleware::isDoador()
 */
function isDoador(): bool
{
    return AuthMiddleware::getInstance()->isDoador();
}

/**
 * Verifica se o usuario esta autenticado
 * @deprecated Use AuthMiddleware::isAuthenticated()
 */
function isLoggedIn(): bool
{
    return AuthMiddleware::getInstance()->isAuthenticated();
}

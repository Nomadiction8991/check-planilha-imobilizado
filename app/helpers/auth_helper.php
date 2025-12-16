<?php
// Permite pular autenticação em páginas públicas/controladas
if (defined('SKIP_AUTH') && SKIP_AUTH === true) {
    return;
}

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

// Logar mas nao exibir erros em producao
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Middleware de autenticacao
// Incluir este arquivo no inicio de todas as paginas que precisam de autenticacao

// URL de login baseada na profundidade do diretorio
function getLoginUrl(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $depth = substr_count($script, '/') - 2;
    return str_repeat('../', max($depth, 0)) . 'login.php';
}

// Modo publico: permitir acesso restrito a algumas paginas com base em sessao publica
$isPublic = !empty($_SESSION['public_acesso']) && !empty($_SESSION['public_planilha_id']);
if (!isset($_SESSION['usuario_id'])) {
    if ($isPublic) {
        // Lista de paginas publicas permitidas (entrada do script)
        $allowed = [
            '/app/views/shared/menu_unificado.php',
            '/app/views/planilhas/relatorio141_view.php',
            '/app/views/planilhas/relatorio141_assinatura.php',
            '/app/views/planilhas/relatorio141_assinatura_form.php',
            '/app/views/planilhas/relatorio_imprimir_alteracao.php',
        ];

        $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $root = realpath(__DIR__);
        $ok = false;
        foreach ($allowed as $rel) {
            $full = realpath($root . $rel);
            if ($full && $scriptFile === $full) {
                $ok = true;
                break;
            }
        }

        if (!$ok) {
            // Log why redirecting to login (help debug redirect loops)
            error_log('AUTH_REDIRECT: missing session usuario_id; is_public=' . ($isPublic ? '1' : '0') . ' script=' . ($_SERVER['SCRIPT_NAME'] ?? '') . ' session_id=' . session_id());
            header('Location: ' . getLoginUrl());
            exit;
        }
    } else {
        // Save redirect target and log for debug
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        error_log('AUTH_REDIRECT: not logged in; redirect_after_login=' . ($_SESSION['redirect_after_login'] ?? '') . ' session_id=' . session_id());
        header('Location: ' . getLoginUrl());
        exit;
    }
}

// Atualizar ultima atividade
$_SESSION['last_activity'] = time();

// Timeout de sessao (30 minutos de inatividade)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    error_log('AUTH_REDIRECT: session timeout for session_id=' . session_id());
    header('Location: ' . getLoginUrl() . '?timeout=1');
    exit;
}

// NOTE: `usuario_tipo` column may be removed; prefer using `is_admin` / `is_doador` session flags set at login.

// Verifica se o usuario é Administrador/Acessor
function isAdmin(): bool {
    // Prefer explicit session flag set at login
    if (isset($_SESSION['is_admin'])) {
        return (bool) $_SESSION['is_admin'];
    }
    // Fallback to legacy 'usuario_tipo' if present
    if (isset($_SESSION['usuario_tipo'])) {
        $tipo = (string) $_SESSION['usuario_tipo'];
        return $tipo === 'Administrador/Acessor' || stripos($tipo, 'administrador') !== false;
    }
    // Default: not admin
    return false;
}

// Verifica se o usuario é Doador/Cônjuge
function isDoador(): bool {
    if (isset($_SESSION['is_doador'])) {
        return (bool) $_SESSION['is_doador'];
    }
    if (isset($_SESSION['usuario_tipo'])) {
        return $_SESSION['usuario_tipo'] === 'Doador/Conjuge' || stripos($_SESSION['usuario_tipo'], 'doador') !== false;
    }
    return false;
}

// Verifica se o usuario esta autenticado
function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] > 0;
}


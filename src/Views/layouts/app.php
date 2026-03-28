<?php


// Carregar configurações da aplicação
$appConfig = require __DIR__ . '/../../../config/app.php';
$projectRoot = $appConfig['project_root'];
$siteTitle = $appConfig['titulo_site'] ?? 'Check Planilha';

$manifest_path = '/manifest-prod.json';

$headTitle = $siteTitle;
$pageTitle = $pageTitle ?? $siteTitle;
$backUrl = $backUrl ?? null;
$headerActions = $headerActions ?? '';
$customCss = $customCss ?? '';
$customJs = $customJs ?? '';

// Compatibilidade com views legadas
if (!isset($content)) {
    if (isset($conteudo)) {
        $content = $conteudo;
    } elseif (isset($contentHtml)) {
        $content = $contentHtml;
    } elseif (isset($contentFile) && file_exists($contentFile)) {
        ob_start();
        include $contentFile;
        $content = ob_get_clean();
    } else {
        $content = null;
    }
}

// Detectar rota atual para marcar item ativo na sidebar
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
function _navActive(string $prefix, string $current): string
{
    return str_starts_with($current, $prefix) ? ' nav-active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= \App\Core\CsrfService::getToken() ?>">
    <title><?= htmlspecialchars($headTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="icon" type="image/png" href="/assets/images/logo.png">

    <!-- PWA -->
    <link rel="manifest" href="<?= $manifest_path ?>">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" href="/assets/images/logo.png">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        black: '#000000',
                        white: '#ffffff',
                        neutral: {
                            50: '#fafafa',
                            100: '#f5f5f5',
                            200: '#e5e5e5',
                            300: '#d4d4d4',
                            400: '#a3a3a3',
                            500: '#808080',
                            600: '#525252',
                            700: '#383838',
                            800: '#262626',
                            900: '#171717',
                            950: '#0a0a0a'
                        },
                        success: {
                            DEFAULT: '#166534',
                            light: '#f0fdf4',
                            border: '#86efac'
                        },
                        error: {
                            DEFAULT: '#991b1b',
                            light: '#fef2f2',
                            border: '#fecaca'
                        },
                        warning: {
                            DEFAULT: '#b45309',
                            light: '#fefce8',
                            border: '#fde047'
                        },
                        info: {
                            DEFAULT: '#0369a1',
                            light: '#f0f9ff',
                            border: '#06b6d4'
                        }
                    },
                    fontFamily: {
                        sans: ['-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'sans-serif'],
                        mono: ['Monaco', '"Courier New"', 'monospace']
                    }
                }
            }
        }
    </script>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Global theme -->
    <link rel="stylesheet" href="/assets/css/minimalist-theme.css">

    <style>
        /* ── LAYOUT SHELL ─────────────────────────────── */
        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            background: #fafafa;
        }

        /* Desktop layout */
        .app-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ── SIDEBAR (desktop) ────────────────────────── */
        .app-sidebar {
            width: 220px;
            flex-shrink: 0;
            background: #000;
            color: #fff;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            border-right: 1px solid #1a1a1a;
        }

        .sidebar-brand {
            padding: 20px 16px 16px;
            border-bottom: 1px solid #1a1a1a;
        }

        .sidebar-brand h1 {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #fff;
            margin: 0 0 2px;
        }

        .sidebar-brand span {
            font-size: 11px;
            color: #525252;
            letter-spacing: 0.04em;
        }

        /* Nav groups */
        .sidebar-nav {
            flex: 1;
            padding: 8px 0;
        }

        .nav-group-label {
            padding: 10px 16px 4px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #383838;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 16px;
            font-size: 13px;
            color: #a3a3a3;
            text-decoration: none;
            transition: background 120ms, color 120ms;
            border-left: 2px solid transparent;
        }

        .nav-link:hover {
            background: #111;
            color: #fff;
            text-decoration: none;
        }

        .nav-link.nav-active {
            color: #fff;
            border-left-color: #fff;
            background: #111;
        }

        .nav-link i {
            font-size: 13px;
            width: 14px;
            text-align: center;
            flex-shrink: 0;
        }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid #1a1a1a;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 8px;
            font-size: 12px;
            color: #525252;
            text-decoration: none;
            border-radius: 2px;
            transition: background 120ms, color 120ms;
        }

        .sidebar-footer a:hover {
            background: #1a1a1a;
            color: #a3a3a3;
        }

        /* ── MAIN AREA (desktop) ──────────────────────── */
        .app-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0;
        }

        /* Top bar (desktop) */
        .app-topbar {
            background: #fff;
            border-bottom: 1px solid #e5e5e5;
            padding: 12px 24px;
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-shrink: 0;
        }

        .topbar-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }

        .topbar-caption {
            font-size: 10px;
            font-weight: 700;
            color: #808080;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .topbar-title {
            font-size: 13px;
            font-weight: 600;
            color: #000;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .topbar-context {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
            min-width: 0;
        }

        .church-context {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border: 1px solid #d4d4d4;
            background: #fafafa;
            border-radius: 2px;
            min-width: 0;
            max-width: 100%;
        }

        .church-context-label {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .church-context-label span:first-child {
            font-size: 10px;
            font-weight: 700;
            color: #808080;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .church-context-label strong {
            font-size: 12px;
            font-weight: 700;
            color: #000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .church-switcher {
            min-width: 260px;
            max-width: 420px;
            width: 100%;
            background: #fff;
            color: #000;
            border: 1px solid #000;
            border-radius: 2px;
            padding: 9px 34px 9px 10px;
            font-size: 12px;
            font-weight: 600;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23000000' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            cursor: pointer;
        }

        .church-switcher:focus {
            outline: none;
            box-shadow: inset 0 0 0 1px #000;
        }

        .topbar-user {
            font-size: 12px;
            color: #525252;
            padding: 8px 0;
            white-space: nowrap;
        }

        /* Content area */
        .app-content-area {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            background: #fafafa;
        }

        /* ── MOBILE (< 1024px) ────────────────────────── */
        @media (max-width: 1023px) {
            .app-shell {
                display: none;
            }
        }

        @media (max-width: 1320px) {
            .app-topbar {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .topbar-meta,
            .topbar-context {
                width: 100%;
            }

            .topbar-context {
                justify-content: space-between;
            }

            .church-context {
                flex: 1;
            }

            .church-context-label {
                flex: 1;
            }

            .church-switcher {
                min-width: 0;
                flex: 1;
            }
        }

        @media (max-width: 720px) {
            .app-topbar {
                padding: 10px 14px;
                gap: 10px;
            }

            .church-context {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .church-context-label strong {
                white-space: normal;
            }

            .church-switcher {
                width: 100%;
                max-width: none;
            }

            .topbar-user {
                width: 100%;
                padding: 0;
            }
        }

        /* ── MOBILE SHELL (substituí o celular-frame) ─── */
        .mobile-shell {
            display: flex;
            flex-direction: column;
            height: 100dvh;
            overflow: hidden;
        }

        .mobile-header {
            flex-shrink: 0;
        }

        .mobile-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background: #fafafa;
        }

        .mobile-footer {
            flex-shrink: 0;
        }

        /* Só mostra mobile-shell em mobile */
        @media (min-width: 1024px) {
            .mobile-shell {
                display: none;
            }

            .mobile-header,
            .mobile-footer {
                display: none;
            }
        }

        /* ── DRAWER (mobile) ──────────────────────────── */
        #menu-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 40;
        }

        #menu-overlay.open {
            display: block;
        }

        #menu-drawer {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 280px;
            max-width: 85vw;
            background: #000;
            color: #fff;
            z-index: 50;
            display: flex;
            flex-direction: column;
            transform: translateX(-100%);
            transition: transform 280ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        #menu-drawer.open {
            transform: translateX(0);
        }

        .drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border-bottom: 1px solid #1a1a1a;
        }

        .drawer-nav {
            flex: 1;
            overflow-y: auto;
        }

        .drawer-toggle {
            width: 100%;
            padding: 10px 16px;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            background: none;
            border: none;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 120ms;
        }

        .drawer-toggle:hover {
            background: #111;
        }

        .drawer-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 250ms ease-in-out;
            background: #0a0a0a;
            border-left: 2px solid #1a1a1a;
        }

        .drawer-submenu a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px 8px 28px;
            font-size: 13px;
            color: #a3a3a3;
            text-decoration: none;
            transition: background 120ms, color 120ms;
        }

        .drawer-submenu a:hover {
            background: #111;
            color: #fff;
        }

        .drawer-chevron {
            font-size: 11px;
            color: #525252;
            transition: transform 200ms;
        }

        .drawer-toggle[aria-expanded="true"] .drawer-chevron {
            transform: rotate(180deg);
        }

        /* ── FLASH MESSAGES ───────────────────────────── */
        .flash-msg {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 2px;
            border: 1px solid;
            font-size: 14px;
            position: fixed;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            width: min(720px, calc(100vw - 24px));
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            z-index: 2500;
        }

        /* ── PAGINAÇÃO GLOBAL ─────────────────────────── */
        .app-pagination-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .app-pagination {
            list-style: none;
            margin: 0;
            padding: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .app-pagination .page-item {
            display: inline-flex;
        }

        .app-pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border: 1px solid #d4d4d4;
            border-radius: 2px;
            background: #fff;
            color: #262626;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.01em;
            transition: background 120ms ease, border-color 120ms ease, color 120ms ease;
        }

        .app-pagination .page-link:hover {
            background: #f5f5f5;
            border-color: #a3a3a3;
            color: #000;
            text-decoration: none;
        }

        .app-pagination .page-item.active .page-link {
            background: #000;
            border-color: #000;
            color: #fff;
            cursor: default;
        }

        .app-pagination .page-item.disabled .page-link {
            opacity: 0.45;
            pointer-events: none;
            background: #fafafa;
            color: #808080;
            border-color: #e5e5e5;
        }
    </style>

    <?php
    $linkHref = null;
    if (isset($customCssPath)) {
        $fsA = $projectRoot . '/' . ltrim($customCssPath, '/');
        $fsB = $projectRoot . '/public/' . ltrim($customCssPath, '/');
        if (file_exists($fsA)) {
            $linkHref = $customCssPath . '?v=' . filemtime($fsA);
        } elseif (file_exists($fsB)) {
            $linkHref = '/' . ltrim($customCssPath, '/') . '?v=' . filemtime($fsB);
        }
    }
    ?>
    <?php if ($linkHref): ?>
        <link rel="stylesheet" href="<?= $linkHref ?>">
    <?php elseif (!empty($customCss)): ?>
        <style>
            <?= $customCss ?>
        </style>
    <?php endif; ?>
</head>

<body>
    <?php
    $pageTitle  = $pageTitle ?? ($tituloPagina ?? null);
    $userName   = \App\Core\SessionManager::getUserName() ?? '';
    $homePath   = $homePath ?? null;
    $logoutPath = $logoutPath ?? '/logout';

    $comuns = [];
    $comumAtualId = null;
    if (isset($_SESSION['usuario_id'])) {
        try {
            $cacheKey = '_layout_comuns_cache';
            $cacheTtl = 300;
            $cache = $_SESSION[$cacheKey] ?? null;

            if (
                is_array($cache)
                && isset($cache['loaded_at'], $cache['items'])
                && (time() - (int) $cache['loaded_at']) < $cacheTtl
            ) {
                $comuns = is_array($cache['items']) ? $cache['items'] : [];
            } else {
                $conexao = \App\Core\ConnectionManager::getConnection();
                $stmtComuns = $conexao->query('SELECT id, codigo, descricao FROM comums ORDER BY codigo ASC');
                $comuns = $stmtComuns ? $stmtComuns->fetchAll(PDO::FETCH_ASSOC) : [];
                $_SESSION[$cacheKey] = [
                    'loaded_at' => time(),
                    'items' => $comuns,
                ];
            }

            $comumAtualId = \App\Core\SessionManager::getComumId();
        } catch (\Exception $e) {
            error_log('Erro ao carregar comuns: ' . $e->getMessage());
        }
    }

    if ($homePath === null) {
        $homePath = $comumAtualId
            ? '/products/view?comum_id=' . urlencode((string) $comumAtualId)
            : '/products/view';
    }

    function _fmtCodigoComumLayout($codigo): string
    {
        if (!is_numeric($codigo)) return $codigo;
        $s = str_pad((string)$codigo, 5, '0', STR_PAD_LEFT);
        return 'BR ' . substr($s, 0, 2) . '-' . substr($s, 2);
    }

    // Montar opções de igrejas
    ob_start();
    foreach ($comuns as $comum):
        $label = _fmtCodigoComumLayout($comum['codigo']);
        if (!empty($comum['descricao'])) $label .= ' · ' . strtoupper($comum['descricao']);
        $sel = (int)$comum['id'] === (int)$comumAtualId ? ' selected' : '';
        echo '<option value="' . (int)$comum['id'] . '"' . $sel . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    endforeach;
    $comunsOptions = ob_get_clean();
    $comumAtualLabel = 'Nenhuma igreja selecionada';
    foreach ($comuns as $comum) {
        if ((int)$comum['id'] === (int)$comumAtualId) {
            $comumAtualLabel = _fmtCodigoComumLayout($comum['codigo']);
            if (!empty($comum['descricao'])) {
                $comumAtualLabel .= ' · ' . strtoupper($comum['descricao']);
            }
            break;
        }
    }

    // Nav items (compartilhado entre sidebar e drawer)
    $navItems = [
        'Trabalho' => [
            'icon'  => 'bi-list-ul',
            'items' => [
                ['href' => '/products/view',   'icon' => 'bi-eye',      'label' => 'Produtos'],
                ['href' => '/churches',        'icon' => 'bi-building', 'label' => 'Igrejas'],
                ['href' => '/spreadsheets/import-errors', 'icon' => 'bi-exclamation-octagon', 'label' => 'Erros'],
                ['href' => '/products/label',             'icon' => 'bi-tags',               'label' => 'Etiquetas'],
            ],
        ],
        'Cadastro' => [
            'icon'  => 'bi-pencil-square',
            'items' => [
                ['href' => '/asset-types',     'icon' => 'bi-box-seam', 'label' => 'Tipos de Bens'],
                ['href' => '/users',           'icon' => 'bi-people',   'label' => 'Usuários'],
                ['href' => '/products/create',    'icon' => 'bi-plus-circle', 'label' => 'Produto'],
                ['href' => '/asset-types/create', 'icon' => 'bi-box-seam',    'label' => 'Tipo de Bem'],
                ['href' => '/departments/create', 'icon' => 'bi-link-45deg',  'label' => 'Dependências'],
                ['href' => '/users/create',       'icon' => 'bi-person-plus', 'label' => 'Usuário'],
            ],
        ],
        'Relatórios' => [
            'icon'  => 'bi-file-earmark-text',
            'items' => [
                ['href' => '/reports/14-1',       'icon' => 'bi-journal-text', 'label' => '14.1'],
                ['href' => '/reports/alteracoes', 'icon' => 'bi-eye-fill',     'label' => 'Alterações'],
            ],
        ],
        'Importação' => [
            'icon'  => 'bi-three-dots',
            'items' => [
                ['href' => '/spreadsheets/import',        'icon' => 'bi-upload',             'label' => 'Importar Planilha'],
            ],
        ],
    ];

    // Flash message HTML
    $flashHtml = '';
    if (!empty($_SESSION['mensagem'])) {
        $tipoAlerta = $_SESSION['tipo_mensagem'] ?? 'info';
        $msgHtml = htmlspecialchars($_SESSION['mensagem'], ENT_QUOTES, 'UTF-8');
        $iconMap = [
            'success' => 'bi-check-circle',
            'danger'  => 'bi-exclamation-triangle',
            'warning' => 'bi-exclamation-diamond',
            'info'    => 'bi-info-circle',
            'note'    => 'bi-journal-text',
            'observation' => 'bi-chat-square-text',
        ];
        $icon  = $iconMap[$tipoAlerta]  ?? $iconMap['info'];
        $flashTypeClass = match ($tipoAlerta) {
            'success' => 'flash-success',
            'danger', 'error' => 'flash-danger',
            'warning' => 'flash-warning',
            'note' => 'flash-note',
            'observation' => 'flash-observation',
            default => 'flash-info',
        };
        $flashHtml = '<div class="flash-msg ' . $flashTypeClass . '" role="alert">'
            . '<i class="bi ' . $icon . ' flex-shrink-0" style="margin-top:2px"></i>'
            . '<div style="flex:1">' . $msgHtml . '</div>'
            . '<button type="button" style="background:none;border:none;font-size:18px;cursor:pointer;color:inherit;padding:0;line-height:1" onclick="this.parentElement.remove()" aria-label="Fechar">&times;</button>'
            . '</div>';
        unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);
    }
    ?>

    <?= $flashHtml ?>

    <!-- ═══════════ DESKTOP LAYOUT ═══════════ -->
    <div class="app-shell">

        <!-- Sidebar -->
        <aside class="app-sidebar">
            <div class="sidebar-brand">
                <h1><?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                <span>Gestão de Patrimônio</span>
            </div>

            <nav class="sidebar-nav">
                <?php foreach ($navItems as $groupLabel => $group): ?>
                    <div class="nav-group-label"><?= htmlspecialchars($groupLabel) ?></div>
                    <?php foreach ($group['items'] as $item): ?>
                        <a href="<?= htmlspecialchars($item['href']) ?>" class="nav-link<?= _navActive($item['href'], $currentPath) ?>">
                            <i class="bi <?= $item['icon'] ?>"></i><?= htmlspecialchars($item['label']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <?php if ($userName): ?>
                    <div style="padding:4px 8px 8px;font-size:11px;color:#383838;letter-spacing:0.04em"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="POST" action="<?= htmlspecialchars($logoutPath) ?>">
                    <?= \App\Core\CsrfService::hiddenField() ?>
                    <button type="submit" style="display:flex;align-items:center;gap:8px;width:100%;background:none;border:none;padding:10px 12px;color:inherit;text-align:left;cursor:pointer">
                        <i class="bi bi-box-arrow-right"></i>Sair
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main -->
        <div class="app-main">
            <header class="app-topbar">
                <div class="topbar-meta">
                    <span class="topbar-caption">Área de trabalho</span>
                    <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="topbar-context">
                    <?php if (!empty($comuns)): ?>
                        <div class="church-context">
                            <div class="church-context-label">
                                <span>Igreja ativa</span>
                                <strong><?= htmlspecialchars($comumAtualLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <select id="comum-selector-desktop" class="church-switcher" onchange="trocarComumDesktop(this.value)" aria-label="Trocar igreja ativa">
                                <?= $comunsOptions ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <?php if ($userName): ?>
                        <span class="topbar-user"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </header>
            <main class="app-content-area">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <!-- ═══════════ MOBILE LAYOUT ═══════════ -->
    <div class="mobile-shell">

        <!-- Mobile Header -->
        <div class="mobile-header" style="background:#000;color:#fff;border-bottom:1px solid #1a1a1a;padding:0 16px;height:52px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0">
            <div style="display:flex;align-items:center;gap:10px">
                <button onclick="openDrawer()" style="background:none;border:none;color:#fff;cursor:pointer;width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:2px" aria-label="Menu">
                    <i class="bi bi-list" style="font-size:20px"></i>
                </button>
                <div>
                    <div style="font-size:13px;font-weight:600;color:#fff;letter-spacing:0.05em;text-transform:uppercase;line-height:1.2"><?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($userName): ?><div style="font-size:11px;color:#525252"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </div>
            </div>
            <?php if (!empty($comuns)): ?>
                <select id="comum-selector" style="background:#fff;color:#000;border:1px solid #000;border-radius:2px;padding:7px 26px 7px 8px;font-size:11px;font-weight:600;max-width:180px;appearance:none;background-repeat:no-repeat;background-position:right 8px center;">
                    <?= $comunsOptions ?>
                </select>
            <?php endif; ?>
        </div>

        <!-- Overlay + Drawer -->
        <div id="menu-overlay" onclick="closeDrawer()"></div>
        <div id="menu-drawer" role="dialog" aria-label="Menu de navegação">
            <div class="drawer-header">
                <span style="font-size:12px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#a3a3a3">Menu</span>
                <button onclick="closeDrawer()" style="background:none;border:none;color:#a3a3a3;cursor:pointer;width:32px;height:32px;display:flex;align-items:center;justify-content:center" aria-label="Fechar">
                    <i class="bi bi-x" style="font-size:20px"></i>
                </button>
            </div>

            <nav class="drawer-nav">
                <?php foreach ($navItems as $groupLabel => $group): ?>
                    <div>
                        <button class="drawer-toggle" type="button" aria-expanded="false" data-group="<?= htmlspecialchars($groupLabel) ?>">
                            <span style="display:flex;align-items:center;gap:8px">
                                <i class="bi <?= $group['icon'] ?>" style="color:#525252;font-size:14px"></i>
                                <?= htmlspecialchars($groupLabel) ?>
                            </span>
                            <i class="bi bi-chevron-down drawer-chevron"></i>
                        </button>
                        <div class="drawer-submenu">
                            <?php foreach ($group['items'] as $item): ?>
                                <a href="<?= htmlspecialchars($item['href']) ?>">
                                    <i class="bi <?= $item['icon'] ?>" style="color:#383838;font-size:12px"></i>
                                    <?= htmlspecialchars($item['label']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Mobile Content -->
        <main class="mobile-content">
            <?= $content ?? '' ?>
        </main>

        <!-- Mobile Footer -->
        <footer class="mobile-footer" style="background:#000;color:#fff;border-top:1px solid #1a1a1a;padding:0 8px;height:52px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
            <button onclick="goBack()" style="flex:1;background:none;border:none;color:#a3a3a3;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:2px;padding:8px 4px;font-size:10px;letter-spacing:0.04em;transition:color 120ms" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#a3a3a3'">
                <i class="bi bi-arrow-left" style="font-size:16px"></i>Voltar
            </button>
            <a href="<?= htmlspecialchars($homePath) ?>" style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:8px 4px;font-size:10px;letter-spacing:0.04em;color:#a3a3a3;text-decoration:none;transition:color 120ms" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#a3a3a3'">
                <i class="bi bi-house-door" style="font-size:16px"></i>Início
            </a>
            <form method="POST" action="<?= htmlspecialchars($logoutPath) ?>" style="flex:1;display:flex">
                <?= \App\Core\CsrfService::hiddenField() ?>
                <button type="submit" style="flex:1;background:none;border:none;display:flex;flex-direction:column;align-items:center;gap:2px;padding:8px 4px;font-size:10px;letter-spacing:0.04em;color:#525252;cursor:pointer;transition:color 120ms" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#525252'">
                    <i class="bi bi-box-arrow-right" style="font-size:16px"></i>Sair
                </button>
            </form>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="/assets/js/csrf-global.js"></script>
    <script src="/assets/js/ui-components.js"></script>
    <script src="/assets/js/pwa-install.js"></script>
    <script>
        window._appHomePath = <?= json_encode($homePath, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window._appCurrentComumId = <?= json_encode($comumAtualId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="/assets/js/layouts/app.js"></script>

    <script>
        // ── Drawer mobile ──────────────────────────────────────
        function openDrawer() {
            document.getElementById('menu-drawer').classList.add('open');
            document.getElementById('menu-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            document.getElementById('menu-drawer').classList.remove('open');
            document.getElementById('menu-overlay').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDrawer();
        });

        // ── Accordion do drawer ────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.drawer-toggle').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var isOpen = this.getAttribute('aria-expanded') === 'true';
                    var sub = this.nextElementSibling;
                    if (!sub) return;
                    if (isOpen) {
                        sub.style.maxHeight = '0';
                        this.setAttribute('aria-expanded', 'false');
                    } else {
                        sub.style.maxHeight = sub.scrollHeight + 'px';
                        this.setAttribute('aria-expanded', 'true');
                    }
                });
            });

            // Fechar drawer ao clicar em link
            document.querySelectorAll('.drawer-submenu a').forEach(function(a) {
                a.addEventListener('click', closeDrawer);
            });
        });

        // ── Seletor de igreja (mobile) ─────────────────────────
        var comumSelector = document.getElementById('comum-selector');
        if (comumSelector) {
            comumSelector.addEventListener('change', function() {
                trocarComum(this.value);
            });
        }

        // ── Seletor de igreja (desktop) ───────────────────────
        function trocarComumDesktop(val) {
            trocarComum(val);
        }

        function trocarComum(comumId) {
            if (!comumId) return;
            var selectorDesktop = document.getElementById('comum-selector-desktop');
            var selectorMobile = document.getElementById('comum-selector');
            if (selectorDesktop) selectorDesktop.disabled = true;
            if (selectorMobile) selectorMobile.disabled = true;

            fetch('/users/select-church', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        comum_id: parseInt(comumId, 10)
                    })
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (!data.success) {
                        throw new Error(data.message || 'Erro ao trocar igreja');
                    }
                    var url = new URL(window.location.href);
                    url.searchParams.set('comum_id', comumId);
                    window.location.href = url.toString();
                })
                .catch(function(error) {
                    if (selectorDesktop) selectorDesktop.disabled = false;
                    if (selectorMobile) selectorMobile.disabled = false;
                    alert(error.message || 'Erro ao trocar igreja.');
                });
        }
    </script>

    <?php if ($customJs): ?>
        <script>
            <?= $customJs ?>
        </script>
    <?php endif; ?>
</body>

</html>

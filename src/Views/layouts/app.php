<?php


// Carregar configurações da aplicação
$appConfig = require __DIR__ . '/../../../config/app.php';
$projectRoot = $appConfig['project_root'];
$siteTitle = $appConfig['titulo_site'] ?? 'Check Planilha';

$manifest_path = '/manifest-prod.json';

// Título do <head> sempre fixo, título visual da página pode variar
$headTitle = $siteTitle;
$pageTitle = $pageTitle ?? $siteTitle;
$backUrl = $backUrl ?? null;
$headerActions = $headerActions ?? '';
$customCss = $customCss ?? '';
$customJs = $customJs ?? '';
$showHeader = $showHeader ?? true;
$showFooter = $showFooter ?? true;

// Compatibilidade com views legadas que definiam $conteudo / $contentHtml / $contentFile
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
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= \App\Core\CsrfService::getToken() ?>">
    <title><?= htmlspecialchars($headTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/logo.png">

    <!-- PWA - Progressive Web App -->
    <link rel="manifest" href="<?= $manifest_path ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" href="/assets/images/logo.png">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM"
        crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
        integrity="sha384-QuGBSgV5Im3DzL2z+8Ko9/hqNy/N0O7zwvXAtfd1MvPKWa/UbeLV65cfm4BV5Wgq"
        crossorigin="anonymous">

    <!-- Estilos do Layout App (globals) -->
    <link rel="stylesheet" href="/assets/css/app-layout.css?v=<?= filemtime($projectRoot . '/public/assets/css/app-layout.css') ?>">

    <!-- Estilos por partial -->
    <link rel="stylesheet" href="/assets/css/celular-container.css?v=<?= filemtime($projectRoot . '/public/assets/css/celular-container.css') ?>">
    <link rel="stylesheet" href="/assets/css/header-mobile.css?v=<?= filemtime($projectRoot . '/public/assets/css/header-mobile.css') ?>">
    <link rel="stylesheet" href="/assets/css/footer-mobile.css?v=<?= filemtime($projectRoot . '/public/assets/css/footer-mobile.css') ?>">

    <!-- Custom CSS Adicional -->
    <?php
    // Suporte para caminhos de CSS por view que podem apontar para '/assets/...' ou '/public/assets/...'
    $linkHref = null;
    if (isset($customCssPath)) {
        $fsA = $projectRoot . '/' . ltrim($customCssPath, '/');
        $fsB = $projectRoot . '/public/' . ltrim($customCssPath, '/');
        if (file_exists($fsA)) {
            $linkHref = $customCssPath;
            $linkHref .= '?v=' . filemtime($fsA);
        } elseif (file_exists($fsB)) {
            // serve pelo webroot sem o /public prefix
            $linkHref = '/' . ltrim($customCssPath, '/');
            $linkHref .= '?v=' . filemtime($fsB);
        }
    }
    ?>
    <?php if ($linkHref): ?>
        <link rel="stylesheet" href="<?= $linkHref ?>">
    <?php elseif (isset($customCss) && $customCss): ?>
        <style>
            <?= $customCss ?>
        </style>
    <?php endif; ?>

    <!-- Scripts externos solicitados pela view (ex: Inputmask CDN) -->
    <?php if (!empty($headScripts) && is_array($headScripts)): ?>
        <?php foreach ($headScripts as $hs): ?>
            <script src="<?= htmlspecialchars($hs['src'], ENT_QUOTES, 'UTF-8') ?>"
                <?= !empty($hs['integrity']) ? 'integrity="' . htmlspecialchars($hs['integrity'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                <?= !empty($hs['crossorigin']) ? 'crossorigin="' . htmlspecialchars($hs['crossorigin'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>></script>
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body>
    <div class="app-container celular-frame">
        <div class="mobile-wrapper celular-shell">
            <?php
            // Variáveis para header, footer e conteúdo
            $pageTitle = $pageTitle ?? ($tituloPagina ?? null);
            $userName  = \App\Core\SessionManager::getUserName() ?? '';
            $menuPath  = $menuPath ?? '/menu';
            $homePath  = $homePath ?? '/products/view';
            $logoutPath = $logoutPath ?? '/logout';

            // Carregar comuns para o seletor (se usuário logado)
            $comuns = [];
            $comumAtualId = null;
            if (isset($_SESSION['usuario_id'])) {
                try {
                    $conexao = \App\Core\ConnectionManager::getConnection();
                    $comumRepo = new \App\Repositories\ComumRepository($conexao);
                    $comuns = $comumRepo->buscarTodos();

                    // comum_id já é garantida pelo index.php via UserSessionService
                    $comumAtualId = \App\Core\SessionManager::getComumId();
                } catch (\Exception $e) {
                    error_log('Erro ao carregar comuns: ' . $e->getMessage());
                }
            }
            ?>

            <?php if ($showHeader): ?>
                <?php include __DIR__ . '/partials/header_mobile.php'; ?>
            <?php endif; ?>

            <main class="app-content celular-screen">
                <?php
                // Exibir mensagens flash da sessão (setMensagem do BaseController)
                if (!empty($_SESSION['mensagem'])) {
                    $tipoAlerta = $_SESSION['tipo_mensagem'] ?? 'info';
                    $msgHtml = htmlspecialchars($_SESSION['mensagem'], ENT_QUOTES, 'UTF-8');
                    echo "<div class=\"alert alert-{$tipoAlerta} alert-dismissible fade show\" role=\"alert\">{$msgHtml}"
                        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>';
                    unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);
                }
                ?>
                <?= $content ?? '' ?>
            </main>

            <?php if ($showFooter): ?>
                <?php include __DIR__ . '/partials/footer_mobile.php'; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz"
        crossorigin="anonymous"></script>

    <!-- CSRF Global — DEVE ser o primeiro JS após Bootstrap -->
    <script src="/assets/js/csrf-global.js"></script>

    <!-- PWA Install Manager -->
    <script src="/assets/js/pwa-install.js"></script>

    <!-- Scripts do Layout App -->
    <script src="/assets/js/layouts/app.js"></script>

    <!-- Submit lock: feedback visual em formulários lentos -->
    <script src="/assets/js/utils/form-submit-lock.js" defer></script>

    <!-- JavaScript Customizado -->
    <?php if ($customJs): ?>
        <script>
            <?= $customJs ?>
        </script>
    <?php endif; ?>
</body>

</html>
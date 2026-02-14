<?php


require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';

$ambiente_manifest = 'prod';
if (strpos($_SERVER['REQUEST_URI'], '/dev/') !== false) {
    $ambiente_manifest = 'dev';
} elseif (strpos($_SERVER['HTTP_HOST'], 'dev.') !== false || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    $ambiente_manifest = 'dev';
}
$manifest_path = ($ambiente_manifest === 'dev') ? '/dev/manifest-dev.json' : '/manifest-prod.json';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $pageTitle ?? 'Anvy - Gestão de Planilhas'; ?></title>

    <!-- PWA - Progressive Web App -->
    <link rel="manifest" href="<?php echo $manifest_path; ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CheckPlanilha">
    <link rel="apple-touch-icon" href="/assets/images/logo.png">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Estilos do Layout App Wrapper -->
    <link rel="stylesheet" href="/assets/css/app-wrapper-layout.css">

    <?php
    $appConfig = $appConfig ?? require __DIR__ . '/../../../config/app.php';
    $projectRoot = $appConfig['project_root'];
    ?>
    <?php if (isset($customCssPath) && file_exists($projectRoot . '/' . ltrim($customCssPath, '/'))): ?>
        <link rel="stylesheet" href="<?php echo $customCssPath; ?>">
    <?php elseif (isset($customCss)): ?>
        <style>
            <?php echo $customCss; ?>
        </style>
    <?php endif; ?>
</head>

<body>
    <div class="app-container">
        <div class="mobile-wrapper">
            <!-- Content -->
            <main class="app-content fade-in">
                <?php if (isset($contentHtml) && $contentHtml !== ''): ?>
                    <?php echo $contentHtml; ?>
                <?php elseif (isset($conteudo) && $conteudo !== ''): ?>
                    <?php echo $conteudo; ?>
                <?php elseif (isset($content) && $content !== ''): ?>
                    <?php echo $content; ?>
                <?php elseif (isset($contentFile)): ?>
                    <?php include $contentFile; ?>
                <?php else: ?>
                    <!-- Conteúdo padrão aqui -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Conteúdo não definido
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Variáveis PHP para o Service Worker -->
    <script>
        window._swConfig = {
            swPath: <?php echo json_encode(($ambiente_manifest === "dev") ? "/dev/sw.js" : "/sw.js"); ?>,
            ambiente: <?php echo json_encode($ambiente_manifest); ?>
        };
    </script>

    <!-- Scripts do Layout App Wrapper -->
    <script src="/assets/js/layouts/app-wrapper.js"></script>

    <?php if (isset($customJs)): ?>
        <script>
            <?php echo $customJs; ?>
        </script>
    <?php endif; ?>
</body>

</html>
<?php




$ambiente_manifest = 'prod';
if (
    strpos($_SERVER['REQUEST_URI'], '/dev/') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'dev.') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false
) {
    $ambiente_manifest = 'dev';
}
$manifest_path = ($ambiente_manifest === 'dev') ? '/dev/manifest-dev.json' : '/manifest-prod.json';

$pageTitle = $pageTitle ?? 'ANVY - GESTÃO DE PLANILHAS';
$backUrl = $backUrl ?? null;
$headerActions = $headerActions ?? '';
$customCss = $customCss ?? '';
$customJs = $customJs ?? '';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- PWA - Progressive Web App -->
    <link rel="manifest" href="<?= $manifest_path ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CheckPlanilha">
    <link rel="apple-touch-icon" href="/assets/images/logo.png">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Estilos do Layout App -->
    <link rel="stylesheet" href="/assets/css/app-layout.css">

    <!-- Custom CSS Adicional -->
    <?php if ($customCss): ?>
        <style><?= $customCss ?></style>
    <?php endif; ?>
</head>

<body>
    <div class="app-container celular-frame">
        <div class="mobile-wrapper celular-shell">
            <?php
            // Variáveis para header, footer e conteúdo
            $pageTitle = $pageTitle ?? ($tituloPagina ?? null);
            $userName  = $userName ?? ($usuario['nome'] ?? '');
            $menuPath  = $menuPath ?? '/menu';
            $homePath  = $homePath ?? '/comuns';
            $logoutPath = $logoutPath ?? '/logout';
            ?>

            <?php include __DIR__ . '/partials/header_mobile.php'; ?>

            <main class="app-content celular-screen">
                <?= $content ?? '' ?>
            </main>

            <?php include __DIR__ . '/partials/footer_mobile.php'; ?>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                const swPath = '<?= ($ambiente_manifest === "dev") ? "/dev/sw.js" : "/sw.js" ?>';
                navigator.serviceWorker.register(swPath)
                    .then(reg => console.log('Service Worker registrado:', reg.scope))
                    .catch(err => console.error('Erro ao registrar Service Worker:', err));
            });
        }
    </script>

    <!-- Função para voltar (igual ao botão do navegador) -->
    <script>
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/comuns';
            }
        }
    </script>

    <!-- Auto-dismiss alerts -->
    <script>
        (function() {
            const AUTO_MS = 3000;
            const FADE_MS = 1000;

            function processAlert(el) {
                if (!el || el.dataset._autoDismissProcessed) return;
                el.dataset._autoDismissProcessed = '1';

                // Remove botão fechar
                const closeBtn = el.querySelector('.btn-close');
                if (closeBtn) closeBtn.remove();

                el.classList.add('fade');
                el.style.transition = `opacity ${FADE_MS}ms ease`;

                if (!el.classList.contains('show')) el.classList.add('show');

                setTimeout(() => {
                    el.classList.remove('show');
                    setTimeout(() => el.remove(), FADE_MS + 20);
                }, AUTO_MS);
            }

            document.querySelectorAll('.alert').forEach(processAlert);

            const mo = new MutationObserver(muts => {
                for (const m of muts) {
                    for (const node of m.addedNodes) {
                        if (!(node instanceof HTMLElement)) continue;
                        if (node.classList && node.classList.contains('alert')) processAlert(node);
                        node.querySelectorAll && node.querySelectorAll('.alert').forEach(processAlert);
                    }
                }
            });
            mo.observe(document.body, {
                childList: true,
                subtree: true
            });
        })();
    </script>

    <!-- Modais dentro do wrapper -->
    <script>
        document.addEventListener('show.bs.modal', function(event) {
            var appWrapper = document.querySelector('.mobile-wrapper');
            if (!appWrapper) return;
            var modal = event.target;
            if (modal && modal.parentElement !== appWrapper) {
                appWrapper.appendChild(modal);
            }
        });
    </script>

    <!-- JavaScript Customizado -->
    <?php if ($customJs): ?>
        <script>
            <?= $customJs ?>
        </script>
    <?php endif; ?>
</body>

</html>
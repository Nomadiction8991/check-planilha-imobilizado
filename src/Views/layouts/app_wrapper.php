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

    <?php if (isset($customCssPath) && file_exists(__DIR__ . '/../../../' . ltrim($customCssPath, '/'))): ?>
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

    <!-- Função para voltar (igual ao botão do navegador) -->
    <script>
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Se não há histórico, vai para a página inicial
                window.location.href = '/comuns';
            }
        }
    </script>

    <!-- Bloqueio de zoom global (pinch/double-tap) fora do viewer do relatório -->
    <script>
        (function() {
            const isViewerOpen = () => {
                const ov = document.getElementById('viewerOverlay');
                return !!(ov && !ov.hasAttribute('hidden'));
            };

            // Evita pinch-zoom (2+ dedos) fora do viewer
            document.addEventListener('touchstart', function(e) {
                if (isViewerOpen()) return; // permitir no viewer (zoom customizado)
                if (e.touches && e.touches.length > 1) {
                    e.preventDefault();
                }
            }, {
                passive: false
            });

            // Evita double-tap zoom fora do viewer
            let lastTouchEnd = 0;
            document.addEventListener('touchend', function(e) {
                if (isViewerOpen()) return;
                const now = Date.now();
                if (now - lastTouchEnd <= 300) {
                    e.preventDefault();
                }
                lastTouchEnd = now;
            }, {
                passive: false
            });

            // Alguns navegadores disparam gesturestart (iOS antigos)
            document.addEventListener('gesturestart', function(e) {
                if (isViewerOpen()) return;
                e.preventDefault();
            });

            // Melhora em navegadores que suportam touch-action
            document.body.style.touchAction = 'manipulation';
        })();
    </script>

    <!-- Garantir que modais fiquem dentro do wrapper mobile -->
    <script>
        document.addEventListener('show.bs.modal', function(event) {
            var appWrapper = document.querySelector('.mobile-wrapper');
            if (!appWrapper) return;
            var modal = event.target;
            if (modal && modal.parentElement !== appWrapper) {
                appWrapper.appendChild(modal);
            }

            // Mover backdrop para dentro do wrapper
            setTimeout(function() {
                var backdrop = document.querySelector('.modal-backdrop');
                if (backdrop && backdrop.parentElement !== appWrapper) {
                    appWrapper.appendChild(backdrop);
                }
            }, 10);
        });
    </script>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                const swPath = '<?php echo ($ambiente_manifest === "dev") ? "/dev/sw.js" : "/sw.js"; ?>';
                navigator.serviceWorker.register(swPath)
                    .then(registration => {
                        console.log('Service Worker registrado com sucesso:', registration.scope);
                        console.log('Ambiente:', '<?php echo $ambiente_manifest; ?>');
                    })
                    .catch(err => console.error('Falha ao registrar Service Worker:', err));
            });
        }
    </script>

    <?php if (isset($customJs)): ?>
        <script>
            <?php echo $customJs; ?>
        </script>
    <?php endif; ?>
</body>

</html>

<!-- Global alert behavior: remove close button, auto-dismiss after 3s with 1s fade -->
<script>
    (function() {
        const AUTO_MS = 3000; // show time
        const FADE_MS = 1000; // fade duration

        function processAlert(el) {
            if (!el || el.dataset._autoDismissProcessed) return;
            el.dataset._autoDismissProcessed = '1';

            // Remove any close button (X)
            const closeBtn = el.querySelector('.btn-close');
            if (closeBtn) closeBtn.remove();

            // Ensure fade class and desired transition duration
            el.classList.add('fade');
            // force transition duration to 1s for opacity
            el.style.transition = `opacity ${FADE_MS}ms ease`;

            // Ensure it is shown (some alerts may be created without show)
            if (!el.classList.contains('show')) el.classList.add('show');

            // Schedule auto-hide
            setTimeout(() => {
                // remove 'show' to start fade
                el.classList.remove('show');
                // remove from DOM after fade
                setTimeout(() => {
                    try {
                        el.remove();
                    } catch (e) {}
                }, FADE_MS + 20);
            }, AUTO_MS);
        }

        // process existing alerts
        document.querySelectorAll('.alert').forEach(processAlert);

        // observe for dynamically added alerts
        const mo = new MutationObserver(muts => {
            for (const m of muts) {
                for (const node of m.addedNodes) {
                    if (!(node instanceof HTMLElement)) continue;
                    if (node.classList && node.classList.contains('alert')) processAlert(node);
                    // also check nested
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
</body>

</html>
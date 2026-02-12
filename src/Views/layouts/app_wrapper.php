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

    <!-- Mobile-First Global CSS - REMOVIDO: contradiz objetivo de CSS único para todos dispositivos -->
    <!-- <link rel="stylesheet" href="<?php echo ($ambiente_manifest === 'dev') ? '/dev/public/assets/css/mobile-first.css' : '/public/assets/css/mobile-first.css'; ?>"> -->

    <!-- Custom CSS -->
    <style>
        /* Ensure header titles display uppercase visually too */
        .app-title {
            text-transform: uppercase;
        }

        /* ===== LAYOUT MOBILE 400px CENTRALIZADO ===== */
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        /* Campos em maiúsculas por padrão */
        input.form-control:not([type="password"]),
        textarea.form-control,
        select.form-select,
        .text-uppercase {
            text-transform: uppercase;
        }

        /* Container principal centralizado */
        .app-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 20px 10px;
        }

        /* Wrapper mobile de 400px */
        .mobile-wrapper {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            min-height: calc(100vh - 40px);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Conteúdo principal (header e footer removidos) */
        /* Cont eúdo principal */
        .app-content {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        /* Cards Bootstrap personalizados */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 16px;
            transition: all 0.3s;
        }

        .card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        /* Modais dentro do wrapper mobile */
        .mobile-wrapper .modal,
        .mobile-wrapper .modal-backdrop {
            position: fixed !important;
            z-index: 1055;
        }

        .mobile-wrapper .modal {
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 400px;
            height: 100vh;
            display: none !important;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.45);
            padding: 12px 16px;
        }

        .mobile-wrapper .modal.show {
            display: flex !important;
            opacity: 1;
        }

        .mobile-wrapper .modal-dialog {
            margin: 1rem;
            width: auto;
            max-width: 360px;
            transform: none !important;
        }

        .mobile-wrapper .modal-backdrop {
            display: none !important;
        }

        /* Evitar salto do layout por padding quando modal abre */
        body.modal-open {
            padding-right: 0 !important;
            overflow: hidden;
        }

        /* Largura dos botões do modal */
        .w-47 {
            width: 47%;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            padding: 12px 16px;
        }

        /* Botões personalizados */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* Exceção: botões dentro de input-group não devem se mover */
        .input-group .btn:hover,
        .input-group .btn:focus,
        .input-group .btn:active {
            transform: none !important;
        }

        /* Tabelas responsivas */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            margin-bottom: 0;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        thead th {
            border: none;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 8px;
        }

        tbody tr {
            transition: all 0.2s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        tbody td {
            padding: 12px 8px;
            vertical-align: middle;
            font-size: 14px;
        }

        /* Badges personalizados */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 11px;
        }

        /* Forms */
        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 10px 12px;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #495057;
            margin-bottom: 6px;
        }

        /* Loading */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        /* Paginação */
        .pagination {
            gap: 4px;
        }

        .page-link {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            color: #667eea;
            transition: all 0.3s;
        }

        .page-link:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        /* Responsividade extra para < 400px */
        @media (max-width: 420px) {
            .app-container {
                padding: 0;
            }

            .mobile-wrapper {
                border-radius: 0;
                min-height: 100vh;
            }
        }

        /* Utilitários */
        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .shadow-sm-custom {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        }

        /* Animações */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>

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
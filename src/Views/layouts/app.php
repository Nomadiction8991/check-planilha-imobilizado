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

    <!-- Estilos Globais -->
    <style>
        /* Layout Mobile-First 400px Centralizado */
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

        /* Header fixo */
        .app-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 400px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .app-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
        }

        .header-actions {
            display: flex;
            gap: 8px;
        }

        .btn-header-action {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-header-action:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* Conteúdo principal */
        .app-content {
            flex: 1;
            padding: 80px 16px 12px;
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

        .table-hover tbody tr {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        /* Modais */
        .mobile-wrapper .modal {
            position: fixed !important;
            z-index: 1055;
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
        }

        body.modal-open {
            padding-right: 0 !important;
            overflow: hidden;
        }

        /* Custom CSS Adicional */
        <?php if ($customCss): ?><?= $customCss ?><?php endif; ?>
    </style>
</head>

<body>
    <div class="app-container">
        <div class="mobile-wrapper">
            <!-- Header -->
            <header class="app-header">
                <div class="header-left">
                    <?php if ($backUrl): ?>
                        <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-back">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                    <?php endif; ?>
                    <h1 class="app-title"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
                <div class="header-actions">
                    <?= $headerActions ?>
                </div>
            </header>

            <!-- Conteúdo Principal -->
            <main class="app-content">
                <?= $content ?? '' ?>
            </main>
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
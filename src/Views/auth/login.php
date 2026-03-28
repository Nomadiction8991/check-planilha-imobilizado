<?php
$appConfig ??= require dirname(__DIR__, 3) . '/config/app.php';
$siteTitle = $appConfig['titulo_site'] ?? 'Check Planilha';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= \App\Core\CsrfService::getToken() ?>">
    <title><?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/logo.png">

    <!-- PWA - Progressive Web App -->
    <link rel="manifest" href="/manifest-prod.json">
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
                        dark: '#000000',
                        light: '#ffffff',
                        neutral: {
                            100: '#f5f5f5',
                            200: '#e5e5e5',
                            300: '#d4d4d4',
                            600: '#525252',
                            900: '#171717'
                        }
                    },
                    fontFamily: {
                        sans: ['-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .login-container {
            background-color: #ffffff;
            border: 1px solid #e5e5e5;
        }
        
        .login-header {
            border-bottom: 2px solid #000000;
        }
        
        .input-field {
            border: 1px solid #d4d4d4;
            transition: all 150ms ease-out;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #000000;
            box-shadow: inset 0 0 0 3px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary {
            background-color: #000000;
            border: 1px solid #000000;
            transition: all 150ms ease-out;
        }
        
        .btn-primary:hover:not(:disabled) {
            background-color: #171717;
            border-color: #171717;
        }
        
        .btn-primary:active:not(:disabled) {
            background-color: #000000;
        }
        
        .alert-error {
            background-color: #fafafa;
            border: 1px solid #000000;
            color: #171717;
        }
        
        .alert-success {
            background-color: #fafafa;
            border: 1px solid #d4d4d4;
            color: #171717;
        }
    </style>
</head>

<body class="bg-neutral-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <!-- Login Card -->
        <div class="login-container">
            <!-- Header -->
            <div class="login-header px-8 py-6">
                <h1 class="text-2xl font-bold text-black">Check Planilha</h1>
                <p class="text-neutral-600 text-sm mt-1">Acesso ao sistema</p>
            </div>

            <!-- Form Area -->
            <div class="p-8">
                <!-- Error Message -->
                <?php if (!empty($erro)): ?>
                    <div class="alert-error mb-6 flex items-start gap-3 px-4 py-3 border rounded-sm">
                        <i class="bi bi-exclamation-circle flex-shrink-0 mt-0.5"></i>
                        <span class="text-sm"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <!-- Success Message -->
                <?php if (!empty($sucesso)): ?>
                    <div class="alert-success mb-6 flex items-start gap-3 px-4 py-3 border rounded-sm">
                        <i class="bi bi-check-circle flex-shrink-0 mt-0.5"></i>
                        <span class="text-sm"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="/login" class="space-y-5">
                    <?= \App\Core\CsrfService::hiddenField() ?>
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-black mb-2">
                            E-mail
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="seu@email.com"
                            class="input-field w-full px-3 py-2.5 text-sm text-black placeholder-neutral-400 rounded-sm"
                            autocomplete="email"
                        >
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="senha" class="block text-sm font-medium text-black mb-2">
                            Senha
                        </label>
                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            required
                            placeholder="••••••••"
                            class="input-field w-full px-3 py-2.5 text-sm text-black placeholder-neutral-400 rounded-sm"
                            autocomplete="current-password"
                        >
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="btn-primary w-full px-4 py-2.5 bg-black text-white text-sm font-medium rounded-sm flex items-center justify-center gap-2 mt-6"
                    >
                        <i class="bi bi-box-arrow-in-right"></i>
                        Entrar
                    </button>
                </form>

                <!-- Footer Text -->
                <p class="text-xs text-neutral-500 text-center mt-6">
                    Acesso restrito. Sistema de gestão de patrimônio eclesiástico.
                </p>
            </div>
        </div>
    </div>

    <!-- UI Components -->
    <script src="/assets/js/ui-components.js"></script>
    <!-- PWA Install Manager -->
    <script src="/assets/js/pwa-install.js"></script>
</body>

</html>

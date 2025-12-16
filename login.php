<?php
define('SKIP_AUTH', true);
require_once __DIR__ . '/app/bootstrap.php';

// Se jÃ¡ estÃ¡ logado, redireciona para o index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$erro = '';
$sucesso = '';

// Mensagem de sucesso ao registrar
if (isset($_GET['registered'])) {
    $sucesso = 'Cadastro realizado com sucesso! FaÃ§a login para continuar.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Email pode ser buscado em CAIXA ALTA; senha nunca deve ser alterada
    $email = to_uppercase(trim($_POST['email'] ?? ''));
    $senha = trim($_POST['senha'] ?? '');

    try {
        if (empty($email) || empty($senha)) {
            throw new Exception('E-mail e senha são obrigatórios.');
        }

            // Buscar usuÃ¡rio por email (comparacao em UPPER para ser robusto a case)
            // Não filtramos por ativo aqui para permitir mostrar mensagem específica quando inativo
            $stmt = $conexao->prepare('SELECT * FROM usuarios WHERE UPPER(email) = :email LIMIT 1');
            $stmt->bindValue(':email', to_uppercase($email));
        $stmt->execute();
        $usuario = $stmt->fetch();

            if (!$usuario) {
                throw new Exception('E-mail ou senha inválidos.');
            }

            // Se usuário existe mas está inativo, informar especificamente
            if ((int)($usuario['ativo'] ?? 0) !== 1) {
                throw new Exception('Usuário inativo. Entre em contato com o administrador.');
            }

            // Verificar senha
            if (!password_verify($senha, $usuario['senha'])) {
                throw new Exception('E-mail ou senha inválidos.');
            }

        // Login bem-sucedido
        // Regenerate session id to prevent fixation and ensure session persistence
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = to_uppercase($usuario['email']);
        // Set role flags without relying on old 'tipo' column
        if (isset($usuario['tipo'])) {
            $t = (string) $usuario['tipo'];
            $_SESSION['is_admin'] = (stripos($t, 'administrador') !== false || stripos($t, 'acessor') !== false);
            $_SESSION['is_doador'] = (stripos($t, 'doador') !== false || stripos($t, 'conjuge') !== false);
        } elseif (isset($usuario['is_admin']) || isset($usuario['is_doador'])) {
            $_SESSION['is_admin'] = !empty($usuario['is_admin']);
            $_SESSION['is_doador'] = !empty($usuario['is_doador']);
        } else {
            // Fallback: treat user id=1 as admin for backward compatibility
            $_SESSION['is_admin'] = ((int)$usuario['id'] === 1);
            $_SESSION['is_doador'] = false;
        }

        // Log debug info for session persistence issues
        error_log(sprintf('LOGIN_SUCCESS: user_id=%s session_id=%s client=%s', $usuario['id'], session_id(), $_SERVER['REMOTE_ADDR'] ?? 'cli'));

        // ensure session data is written before redirect
        session_write_close();

        header('Location: index.php');
        exit;

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 15px;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-body {
            padding: 1.25rem 2rem 1.25rem; /* less top/bottom space */
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 500;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-body">
                <?php if ($sucesso): ?>
                    <div class="alert alert-success fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php 
                            // Corrige possíveis problemas de codificação e então aplica uppercase
                            echo to_uppercase(htmlspecialchars(\voku\helper\UTF8::fix_utf8($sucesso), ENT_QUOTES, 'UTF-8')); 
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($erro): ?>
                    <div class="alert alert-danger fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php 
                            echo to_uppercase(htmlspecialchars(\voku\helper\UTF8::fix_utf8($erro), ENT_QUOTES, 'UTF-8'));
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope me-1"></i>
                            EMAIL
                        </label>
                        <input type="email" class="form-control text-uppercase" id="email" name="email" 
                               placeholder="SEU@EMAIL.COM" required autofocus
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" style="text-transform:uppercase;">
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">
                            <i class="bi bi-lock me-1"></i>
                            SENHA
                        </label>
                        <input type="password" class="form-control" id="senha" name="senha" 
                               placeholder="DIGITE SUA SENHA" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            ENTRAR
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts: show for 3s, then fade out smoothly over 1s, then remove
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.alert').forEach(function (alertEl) {
                // Ensure we can transition opacity/height
                alertEl.style.transition = 'opacity 1s ease, max-height 1s ease, margin 1s ease, padding 1s ease';
                alertEl.style.overflow = 'hidden';

                // Wait 3 seconds, then fade
                setTimeout(function () {
                    // start fade: opacity -> 0 and collapse spacing
                    alertEl.style.opacity = '0';
                    alertEl.style.maxHeight = '0';
                    alertEl.style.margin = '0';
                    alertEl.style.padding = '0';

                    // remove element after 1s (duration of fade)
                    setTimeout(function () {
                        if (alertEl.parentNode) alertEl.parentNode.removeChild(alertEl);
                    }, 1000);
                }, 3000);
            });
        });
    </script>
</body>
</html>





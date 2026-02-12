<?php
define('SKIP_AUTH', true);
session_start();
require_once __DIR__ . '/../config/bootstrap.php';


unset($_SESSION['public_acesso'], $_SESSION['public_planilha_id'], $_SESSION['public_comum']);

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comum_id = (int)($_POST['comum_id'] ?? $_POST['planilha_id'] ?? 0);

    try {
        if ($comum_id <= 0) {
            throw new Exception('Selecione uma Comum válida.');
        }


        $table = detectar_tabela_comuns($conexao);
        $stmt = $conexao->prepare("SELECT id, descricao as comum FROM `{$table}` WHERE id = :id");
        $stmt->bindValue(':id', $comum_id, PDO::PARAM_INT);
        $stmt->execute();
        $planilha = $stmt->fetch();

        if (!$planilha) {
            throw new Exception('Comum não encontrada ou inativa.');
        }


        $_SESSION['public_acesso'] = true;

        $_SESSION['public_planilha_id'] = $planilha['id'];
        $_SESSION['public_comum_id'] = $planilha['id'];
        $_SESSION['public_comum'] = $planilha['comum'];

        header('Location: /comuns?contexto=planilha&id=' . urlencode($planilha['id']) . '&comum_id=' . urlencode($planilha['id']) . '&publico=1');
        exit;
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}


try {
    $table = detectar_tabela_comuns($conexao);
    $stmt = $conexao->query("SELECT id, descricao as comum FROM `{$table}` WHERE 1 ORDER BY descricao ASC");
    $comuns = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = "Erro ao carregar lista de comuns: " . $e->getMessage();
    $comuns = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinar Documentos - Acesso Público</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .box {
            max-width: 420px;
            width: 100%;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .2);
            overflow: hidden;
        }

        .box-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 1.5rem;
            text-align: center;
        }

        .box-body {
            padding: 1.25rem 1.25rem 1.5rem;
        }


        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .box {
            max-width: 420px;
            width: 100%;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .2);
            overflow: hidden;
        }

        .box-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 1.5rem;
            text-align: center;
        }

        .box-body {
            padding: 1.25rem 1.25rem 1.5rem;
        }
    </style>
</head>

<body>
    <div class="box">
        <div class="box-header">
            <i class="bi bi-pen fs-1 d-block mb-2"></i>
            <h4 class="mb-0">Assinar Documentos</h4>
            <small>Acesso público</small>
        </div>
        <div class="box-body">
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($erro); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="comum_id" class="form-label">Selecione sua Comum <span class="text-danger">*</span></label>
                    <select id="comum_id" name="comum_id" class="form-select" required>
                        <option value="">-- Escolha --</option>
                        <?php foreach ($comuns as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['comum']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-right-circle me-1"></i>
                        Continuar
                    </button>
                    <a href="/login" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>
                        Voltar ao login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
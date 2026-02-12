<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

$idParam = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idParam <= 0) {
    header('Location: /dependencias');
    exit;
}


$pageTitle = 'EDITAR DEPÊNDÊNCIA';
$backUrl = '/dependencias';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($dependencia)): ?>
    <form method="POST" id="formDependencia">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2"></i>
                EDITAR DEPENDÊNCIA
            </div>
            <div class="card-body">
                <!-- Campo 'codigo' removido conforme alteração de schema (coluna será excluída) -->

                <div class="mb-3">
                    <label for="descricao" class="form-label"><?php echo htmlspecialchars(to_uppercase('Descrição'), ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                    <textarea class="form-control text-uppercase" id="descricao" name="descricao" rows="3" required><?php echo htmlspecialchars($dependencia['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <small class="text-muted"><?php echo htmlspecialchars(to_uppercase('Descrição da dependência'), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-lg me-1"></i>
                ATUALIZAR DEPENDÊNCIA
            </button>
        </div>
    </form>

    <script>
        // Validação do formulário
        document.getElementById('formDependencia').addEventListener('submit', function(e) {
            const descricao = document.getElementById('descricao').value.trim();

            if (!descricao) {
                e.preventDefault();
                alert('<?php echo htmlspecialchars(to_uppercase("A descrição é obrigatória!"), ENT_QUOTES, 'UTF-8'); ?>');
                return false;
            }
        });
    </script>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
$tempFile = sys_get_temp_dir() . '/temp_editar_dependencia_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';


if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

include __DIR__ . '/../../../app/controllers/create/DependenciaCreateController.php';

$pageTitle = 'Nova DEPENDÊNCIA';
$backUrl = './dependencias_listar.php';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="bi bi-plus-circle me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Cadastrar nova dependência'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row g-3">
                <!-- campo codigo removido conforme solicitado -->
                <div class="col-12">
                    <label for="descricao" class="form-label"><?php echo htmlspecialchars(to_uppercase('Descrição'), ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" id="descricao" name="descricao" required
                           placeholder="<?php echo htmlspecialchars(to_uppercase('Digite a descrição'), ENT_QUOTES, 'UTF-8'); ?>"
                           value="<?php echo htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg me-2"></i>
                    <?php echo htmlspecialchars(to_uppercase('Cadastrar dependência'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <!-- botão Voltar removido conforme solicitado -->
            </div>
        </form>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = sys_get_temp_dir() . '/temp_create_dependencia_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>



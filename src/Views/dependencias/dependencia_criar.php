<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';


$pageTitle = 'Nova DEPENDÊNCIA';
$backUrl = '/dependencias';

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
        <form method="POST" action="" id="formDependenciaCreate">
            <div class="row g-3">
                <!-- campo codigo removido conforme solicitado -->
                <div class="col-12 mb-3">
                    <label for="descricao" class="form-label"><?php echo htmlspecialchars(to_uppercase('Descrição'), ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                    <textarea class="form-control text-uppercase" id="descricao" name="descricao" rows="3" required placeholder="<?php echo htmlspecialchars(to_uppercase('Digite a descrição'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <small class="text-muted"><?php echo htmlspecialchars(to_uppercase('Descrição da dependência'), ENT_QUOTES, 'UTF-8'); ?></small>
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

        <script>
            // Validação do formulário (criar)
            document.getElementById('formDependenciaCreate').addEventListener('submit', function(e) {
                const descricao = document.getElementById('descricao').value.trim();
                if (!descricao) {
                    e.preventDefault();
                    alert('<?php echo htmlspecialchars(to_uppercase("A descrição é obrigatória!"), ENT_QUOTES, 'UTF-8'); ?>');
                    return false;
                }
            });
        </script>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
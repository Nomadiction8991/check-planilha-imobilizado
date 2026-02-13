<?php
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';


$pageTitle = to_uppercase('observacoes');
$filtroStatus = $filtro_status ?? ($filtro_STATUS ?? '');
$backUrl = getReturnUrl($comum_id, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtroStatus);

$produtoDados = $produto ?? [];
$descricaoCompleta = isset($descricaoCompleta) ? $descricaoCompleta : trim($produtoDados['editado_descricao_completa'] ?? $produtoDados['descricao_completa'] ?? $produtoDados['descricao'] ?? '');
$codigoProduto = $produtoDados['codigo'] ?? '';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-box-seam me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('produto'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <div class="card-body">
        <div class="row g-2 small">
            <div class="col-12"><?php echo htmlspecialchars($descricaoCompleta, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>
</div>

<form method="POST">
    <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtroStatus, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="card mb-3">
        <div class="card-body">
            <label for="observacoes" class="form-label">
                <i class="bi bi-chat-square-text me-2"></i>
                <?php echo htmlspecialchars(to_uppercase('observações'), ENT_QUOTES, 'UTF-8'); ?>
            </label>
            <textarea class="form-control text-uppercase" id="observacoes" name="observacoes" rows="6"
                placeholder="<?php echo htmlspecialchars(to_uppercase('digite as observações do produto...'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(to_uppercase($check['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            <div class="form-text"><?php echo htmlspecialchars(to_uppercase('deixe em branco para remover as observações'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-save me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('salvar observações'), ENT_QUOTES, 'UTF-8'); ?>
    </button>
</form>

<?php
$contentHtml = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'OBSERVAÇÕES';
$filtroStatus = $filtro_status ?? ($filtro_STATUS ?? '');
$backUrl = getReturnUrl($comum_id ?? null, $pagina ?? null, $filtro_nome ?? null, $filtro_dependencia ?? null, $filtro_codigo ?? null, $filtroStatus);

$produtoDados = $produto ?? [];
$descricaoCompleta = isset($descricaoCompleta)
    ? $descricaoCompleta
    : trim(
        ($produtoDados['editado_bem'] ?? '') !== ''
            ? trim(($produtoDados['editado_bem'] ?? '') . ' ' . ($produtoDados['editado_complemento'] ?? ''))
            : trim(($produtoDados['bem'] ?? '') . ' ' . ($produtoDados['complemento'] ?? ''))
    );
$codigoProduto = $produtoDados['codigo'] ?? '';
$idProdutoObs = $id_produto ?? ($produtoDados['id_produto'] ?? 0);

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <?php
    $isSuccess = ($tipo_mensagem ?? '') === 'success';
    $msgStyle  = $isSuccess ? 'background:#f0fdf4;border:1px solid #86efac;color:#166534' : 'background:#fef2f2;border:1px solid #fecaca;color:#991b1b';
    $msgIcon   = $isSuccess ? 'bi-check-circle' : 'bi-exclamation-triangle';
    ?>
    <div style="<?= $msgStyle ?>;border-radius:2px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:flex-start;gap:10px;font-size:13px" role="alert">
        <i class="bi <?= $msgIcon ?>" style="margin-top:2px;flex-shrink:0"></i>
        <span style="flex:1"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></span>
        <button type="button" style="background:none;border:none;cursor:pointer;font-size:16px;line-height:1;color:inherit;padding:0" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="bi bi-box-seam text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252">PRODUTO</span>
    </div>
    <div class="px-5 py-3" style="font-size:13px;color:#262626">
        <?= htmlspecialchars($descricaoCompleta, ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>

<form method="POST" action="/products/observation?id_produto=<?= (int)$idProdutoObs ?>">
    <input type="hidden" name="produto_id" value="<?= (int)$idProdutoObs ?>">
    <input type="hidden" name="pagina" value="<?= (int)($pagina ?? 1) ?>">
    <input type="hidden" name="nome" value="<?= htmlspecialchars($filtro_nome ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="dependencia" value="<?= htmlspecialchars($filtro_dependencia ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="filtro_codigo" value="<?= htmlspecialchars($filtro_codigo ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="status" value="<?= htmlspecialchars($filtroStatus, ENT_QUOTES, 'UTF-8') ?>">

    <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="px-5 py-4">
            <label for="observacoes" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">
                <i class="bi bi-chat-square-text" style="margin-right:4px"></i>OBSERVAÇÕES
            </label>
            <textarea
                class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                style="border-radius:2px;resize:vertical"
                id="observacoes" name="observacoes" rows="6"
                placeholder="Digite as observações do produto..."><?= htmlspecialchars($check['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <small style="font-size:11px;color:#808080">Deixe em branco para remover as observações.</small>
        </div>
    </div>

    <button type="submit"
        class="w-full px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center justify-center gap-2"
        style="border-radius:2px">
        <i class="bi bi-save"></i>
        SALVAR OBSERVAÇÕES
    </button>
</form>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>

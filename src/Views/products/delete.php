<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'Excluir PRODUTO';
$backUrl = '/products/view?comum_id=' . urlencode((string) ($comum_id ?? $id_planilha ?? ''));
$filtrosQuery = gerarParametrosFiltro();
if ($filtrosQuery !== '') {
    $backUrl .= '&' . $filtrosQuery;
}

ob_start();
?>

<div style="background:#fefce8;border:1px solid #fde047;color:#b45309;border-radius:2px;padding:12px 14px;margin-bottom:16px;font-size:13px">
    <strong>Atenção:</strong> Tem certeza que deseja excluir este PRODUTO? Esta ação <strong>NÃO</strong> pode ser desfeita.
    <?php if (!empty($erros)): ?>
        <ul style="margin:8px 0 0;padding-left:16px">
            <?php foreach ($erros as $erro): ?>
                <li><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<form method="POST" id="form-PRODUTO">
    <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="px-5 py-4" style="display:flex;flex-direction:column;gap:12px">

            <?php if (!empty($PRODUTO['codigo'])): ?>
                <div>
                    <label style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">CÓDIGO</label>
                    <input type="text"
                        class="w-full px-3 py-2 border border-neutral-200 text-sm"
                        style="border-radius:2px;background:#f5f5f5;color:#808080"
                        value="<?= htmlspecialchars($PRODUTO['codigo'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                </div>
            <?php endif; ?>

            <div>
                <label style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Tipo de Bem</label>
                <input type="text"
                    class="w-full px-3 py-2 border border-neutral-200 text-sm"
                    style="border-radius:2px;background:#f5f5f5;color:#808080"
                    value="<?= htmlspecialchars(($PRODUTO['tipo_codigo'] ?? '') . ' - ' . ($PRODUTO['tipo_descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" disabled>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Bem</label>
                <input type="text"
                    class="w-full px-3 py-2 border border-neutral-200 text-sm"
                    style="border-radius:2px;background:#f5f5f5;color:#808080"
                    value="<?= htmlspecialchars($PRODUTO['tipo_ben'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Complemento</label>
                <textarea
                    class="w-full px-3 py-2 border border-neutral-200 text-sm"
                    style="border-radius:2px;background:#f5f5f5;color:#808080;resize:none"
                    rows="3" disabled><?= htmlspecialchars($PRODUTO['complemento'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Dependência</label>
                <input type="text"
                    class="w-full px-3 py-2 border border-neutral-200 text-sm"
                    style="border-radius:2px;background:#f5f5f5;color:#808080"
                    value="<?= htmlspecialchars($PRODUTO['dependencia_descricao'] ?? $PRODUTO['dependencia'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Status</label>
                <div style="display:flex;gap:6px">
                    <?php if (isset($PRODUTO['condicao_141']) && ($PRODUTO['condicao_141'] == 1 || $PRODUTO['condicao_141'] == 3)): ?>
                        <span style="display:inline-block;padding:2px 7px;font-size:11px;font-weight:600;background:#fefce8;color:#b45309;border:1px solid #fde047;border-radius:2px">Nota</span>
                    <?php else: ?>
                        <span style="display:inline-block;padding:2px 7px;font-size:11px;font-weight:600;background:#f5f5f5;color:#a3a3a3;border:1px solid #e5e5e5;border-radius:2px">Sem nota</span>
                    <?php endif; ?>
                    <?php if ($PRODUTO['imprimir_14_1'] == 1): ?>
                        <span style="display:inline-block;padding:2px 7px;font-size:11px;font-weight:600;background:#000;color:#fff;border-radius:2px">14.1</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <button type="submit"
        class="w-full px-4 py-2 text-white text-sm font-medium flex items-center justify-center gap-2"
        style="background:#991b1b;border:1px solid #991b1b;border-radius:2px">
        <i class="bi bi-trash"></i>
        Confirmar Exclusão
    </button>
</form>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>

<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'EDITAR PRODUTO';
$backUrl = '/products/view?comum_id=' . urlencode((string) ($comum_id ?? $id_planilha ?? ''));
$filtrosQuery = gerarParametrosFiltro();
if ($filtrosQuery !== '') {
    $backUrl .= '&' . $filtrosQuery;
}

ob_start();
?>

<?php if (!empty($erros)): ?>
    <div style="background:#fafafa;border:1px solid #000;color:#171717;border-radius:2px;padding:10px 14px;margin-bottom:16px;font-size:13px" role="alert">
        <strong style="display:block;margin-bottom:4px">Erros encontrados:</strong>
        <ul style="margin:0;padding-left:16px">
            <?php foreach ($erros as $erro): ?>
                <li><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" id="form-PRODUTO" class="needs-validation" novalidate>
    <div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="px-5 py-4" style="display:flex;flex-direction:column;gap:12px">

            <div>
                <label for="codigo" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">
                    CÓDIGO <span style="color:#808080">(opcional)</span>
                </label>
                <input type="text" id="codigo" name="codigo"
                    class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                    style="border-radius:2px"
                    value="<?= htmlspecialchars($PRODUTO['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="CÓDIGO gerado por outro sistema">
                <small style="font-size:11px;color:#808080">Campo opcional. Não será incluído na descrição completa.</small>
            </div>

            <div>
                <label for="id_tipo_ben" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Tipos de Bens</label>
                <select id="id_tipo_ben" name="id_tipo_ben"
                    class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                    style="border-radius:2px" required>
                    <option value="">SELECIONE UM TIPO DE BEM</option>
                    <?php foreach ($tipos_bens as $tipo): ?>
                        <option value="<?= $tipo['id'] ?>" data-descricao="<?= htmlspecialchars($tipo['descricao']) ?>"
                            <?= ($PRODUTO['tipo_bem_id'] == $tipo['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tipo['codigo'] . ' - ' . $tipo['descricao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="text-xs mt-1" style="display:none;color:#525252">SELECIONE UM TIPO DE BEM.</div>
            </div>

            <div>
                <label for="tipo_ben" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Bem</label>
                <select id="tipo_ben" name="tipo_ben"
                    class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                    style="border-radius:2px" required>
                    <option value="">PRIMEIRO SELECIONE UM TIPO DE BEM</option>
                </select>
                <div class="text-xs mt-1" style="display:none;color:#525252">SELECIONE UM BEM.</div>
            </div>

            <div>
                <label for="complemento" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">COMPLEMENTO</label>
                <textarea id="complemento" name="complemento"
                    class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                    style="border-radius:2px;resize:vertical"
                    rows="3" placeholder="Digite o complemento do PRODUTO" required><?= htmlspecialchars($PRODUTO['complemento'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="text-xs mt-1" style="display:none;color:#525252">Informe o complemento.</div>
            </div>

            <div>
                <label for="id_dependencia" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">DEPENDÊNCIA</label>
                <select id="id_dependencia" name="id_dependencia"
                    class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                    style="border-radius:2px" required>
                    <option value="">Selecione uma DEPENDÊNCIA</option>
                    <?php foreach ($dependencias as $dep): ?>
                        <option value="<?= $dep['id'] ?>" <?= ($PRODUTO['dependencia_id'] == $dep['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dep['descricao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="text-xs mt-1" style="display:none;color:#525252">Selecione a DEPENDÊNCIA.</div>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">STATUS</label>
                <div style="display:flex;align-items:center;gap:8px">
                    <input class="w-4 h-4" style="accent-color:#000;cursor:pointer"
                        type="checkbox" id="imprimir_14_1" name="imprimir_14_1" value="1"
                        <?= ($PRODUTO['imprimir_14_1'] == 1) ? 'checked' : '' ?>>
                    <label style="font-size:13px;cursor:pointer" for="imprimir_14_1">IMPRIMIR 14.1</label>
                </div>
            </div>
        </div>
    </div>

    <button type="submit"
        class="w-full px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center justify-center gap-2"
        style="border-radius:2px">
        <i class="bi bi-save"></i>
        ATUALIZAR PRODUTO
    </button>
</form>

<script>
    window._produtoBem = <?= json_encode($PRODUTO['bem'] ?? '') ?>;
</script>
<script src="/assets/js/products/update.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>

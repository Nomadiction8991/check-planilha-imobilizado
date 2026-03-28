<?php

$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'Nova DEPENDÊNCIA';
$backUrl = '/departments';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <?php
    $msgStyle  = 'background:#fafafa;border:1px solid #d4d4d4;color:#171717';
    $msgIcon   = 'bi-info-circle';
    ?>
    <div style="<?= $msgStyle ?>;border-radius:2px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:flex-start;gap:10px;font-size:13px" role="alert">
        <i class="bi <?= $msgIcon ?>" style="margin-top:2px;flex-shrink:0"></i>
        <span style="flex:1"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></span>
        <button type="button" style="background:none;border:none;cursor:pointer;font-size:16px;line-height:1;color:inherit;padding:0" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="bi bi-plus-circle text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252">CADASTRAR NOVA DEPENDÊNCIA</span>
    </div>
    <div class="p-5">
        <form method="POST" action="" id="formDependenciaCreate">
            <div class="mb-4">
                <label for="descricao" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">
                    Descrição <span style="color:#991b1b">*</span>
                </label>
                <textarea
                    class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                    style="border-radius:2px;resize:vertical"
                    id="descricao" name="descricao" rows="3" required
                    placeholder="Digite a descrição"><?= htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <small style="font-size:11px;color:#808080">Descrição da dependência</small>
            </div>

            <button type="submit"
                class="w-full px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center justify-center gap-2"
                style="border-radius:2px">
                <i class="bi bi-check-lg"></i>Cadastrar Dependência
            </button>
            <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>"
                class="w-full mt-2 px-4 py-2 border border-neutral-300 text-neutral-700 text-sm font-medium transition flex items-center justify-center gap-2"
                style="border-radius:2px;text-decoration:none"
                onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background=''">
                <i class="bi bi-x-lg"></i>Cancelar
            </a>
        </form>
    </div>
</div>

<script>
document.getElementById('formDependenciaCreate').addEventListener('submit', function(e) {
    const descricao = document.getElementById('descricao').value.trim();
    if (!descricao) {
        e.preventDefault();
        alert('A descrição é obrigatória!');
    }
});
</script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>

<?php
/**
 * View: Editar Tipo de Bem
 */
?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="bi bi-pencil-square text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252">EDITAR TIPO DE BEM</span>
    </div>
    <div class="p-5">

        <?php if (!empty($_SESSION['mensagem'])): ?>
            <?php
            $isSuccess = ($_SESSION['tipo_mensagem'] ?? '') === 'success';
            $style = $isSuccess
                ? 'background:#f0fdf4;border:1px solid #86efac;color:#166534'
                : 'background:#fef2f2;border:1px solid #fecaca;color:#991b1b';
            $icon = $isSuccess ? 'bi-check-circle' : 'bi-exclamation-triangle';
            ?>
            <div style="<?= $style ?>;border-radius:2px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:flex-start;gap:10px;font-size:13px" role="alert">
                <i class="bi <?= $icon ?>" style="margin-top:2px;flex-shrink:0"></i>
                <span style="flex:1"><?= htmlspecialchars($_SESSION['mensagem'], ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" style="background:none;border:none;cursor:pointer;font-size:16px;line-height:1;color:inherit;padding:0" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
            <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:2px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:flex-start;gap:10px;font-size:13px" role="alert">
                <i class="bi bi-exclamation-triangle" style="margin-top:2px;flex-shrink:0"></i>
                <span style="flex:1"><?= htmlspecialchars($_GET['erro'], ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" style="background:none;border:none;cursor:pointer;font-size:16px;line-height:1;color:inherit;padding:0" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <form method="POST" action="/asset-types/<?= (int)$tipo['id'] ?>/edit" class="space-y-4">
            <input type="hidden" name="id" value="<?= (int)$tipo['id'] ?>">

            <div>
                <label for="descricao" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">
                    Descrição <span style="color:#991b1b">*</span>
                </label>
                <input
                    type="text"
                    class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                    style="border-radius:2px"
                    id="descricao"
                    name="descricao"
                    value="<?= htmlspecialchars($tipo['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                    maxlength="255"
                    placeholder="EX: IMÓVEIS">
            </div>

            <div style="display:flex;flex-direction:column;gap:8px;padding-top:16px;border-top:1px solid #e5e5e5">
                <button type="submit"
                    class="w-full px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center justify-center gap-2"
                    style="border-radius:2px">
                    <i class="bi bi-check-lg"></i>Salvar Alterações
                </button>
                <a href="<?= htmlspecialchars($backUrl ?? '/asset-types', ENT_QUOTES, 'UTF-8') ?>"
                    style="display:flex;align-items:center;justify-content:center;gap:8px;padding:8px 16px;border:1px solid #d4d4d4;color:#525252;font-size:13px;text-decoration:none;border-radius:2px;transition:background 120ms"
                    onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background=''">
                    <i class="bi bi-x-lg"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

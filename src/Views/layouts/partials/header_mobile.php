<?php


/**
 * Partial: header_mobile.php — incluído pelo app.php como fallback legacy.
 * Em uso normal o header mobile é renderizado diretamente dentro do app.php.
 */
$pageTitle = $pageTitle ?? 'TÍTULO DA PÁGINA';
$userName = $userName ?? '';
$comuns = $comuns ?? [];
$comumAtualId = $comumAtualId ?? null;

function _fmtCodigoComum($codigo)
{
    // formata número como "BR 00-000". Se não for numérico, retorna original.
    if (!is_numeric($codigo)) return $codigo;
    $s = str_pad((string)$codigo, 5, '0', STR_PAD_LEFT);
    return 'BR ' . substr($s, 0, 2) . '-' . substr($s, 2);
}
?>

<div class="app-header header-mobile w-full bg-black text-white border-b border-neutral-700 px-4 py-3 flex items-center justify-between gap-4">
    <div class="header-left flex items-center gap-3">
        <button onclick="openMenuDrawer()" class="btn-menu inline-flex items-center justify-center w-10 h-10 hover:bg-neutral-900 rounded transition" aria-label="Abrir menu">
            <i class="bi bi-list text-xl"></i>
        </button>

        <div class="header-title-section flex flex-col">
            <h1 class="app-title text-lg font-semibold text-neutral-300"><?= strtoupper(htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8')) ?></h1>
            <div class="user-name text-sm text-neutral-400"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>

    <div class="header-actions">
        <?php if (!empty($comuns)): ?>
            <select id="comum-selector" class="px-3 py-2 bg-neutral-900 text-white border border-neutral-700 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-black transition">
                <?php foreach ($comuns as $comum): ?>
                    <option value="<?= (int)$comum['id'] ?>" <?= (int)$comum['id'] === (int)$comumAtualId ? 'selected' : '' ?>>
                        <?php
                        $label = _fmtCodigoComum($comum['codigo']);
                        if (!empty($comum['descricao'])) {
                            $label .= ' - ' . strtoupper($comum['descricao']);
                        }
                        echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
</div>

<script src="/assets/js/layouts/header-mobile.js"></script>
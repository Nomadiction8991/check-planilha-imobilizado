<?php


/**
 * Partial: header_mobile.php
 * Cabeçalho móvel com seletor de comum
 * Variáveis aceitas (opcionais):
 *  - $pageTitle (string)  -> título da página (renderizado em maiúsculas)
 *  - $userName  (string)  -> nome do usuário logado (pode ficar vazio)
 *  - $menuPath  (string)  -> caminho a ser usado no botão de menu (default: '/menu')
 *  - $comuns (array)      -> lista de comuns disponíveis
 *  - $comumAtualId (int)  -> ID da comum selecionada
 */
$menuPath = $menuPath ?? '/menu';
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

<div class="app-header header-mobile">
    <div class="header-left">
        <a href="<?= $menuPath ?>" class="btn-menu" aria-label="Abrir menu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </a>

        <div class="header-title-section">
            <h1 class="app-title"><?= strtoupper(htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8')) ?></h1>
            <div class="user-name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>

    <div class="header-actions">
        <?php if (!empty($comuns)): ?>
            <select id="comum-selector" class="form-select form-select-sm" aria-label="Selecionar Comum">
                <?php foreach ($comuns as $comum): ?>
                    <option value="<?= (int)$comum['id'] ?>" <?= (int)$comum['id'] === (int)$comumAtualId ? 'selected' : '' ?>>
                        <?= htmlspecialchars(_fmtCodigoComum($comum['codigo']), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
</div>

<script src="/assets/js/layouts/header-mobile.js"></script>
<?php

/**
 * Partial: header_mobile.php
 * Cabeçalho móvel conforme o mock enviado pelo usuário.
 * Variáveis aceitas (opcionais):
 *  - $pageTitle (string)  -> título da página (renderizado em maiúsculas)
 *  - $userName  (string)  -> nome do usuário logado (pode ficar vazio)
 *  - $menuPath  (string)  -> caminho a ser usado no botão de menu (default: '/menu')
 *
 * Observações:
 * - Esta partial só cria a marcação; não altera rotas nem cria a página de menu.
 * - Usa classes já presentes no layout (`app-header`, `btn-menu`, `app-title`, `user-name`).
 * - Não é incluída em nenhum layout automaticamente — só disponibiliza a view.
 */
$menuPath = $menuPath ?? '/menu';
$pageTitle = $pageTitle ?? 'TÍTULO DA PÁGINA';
$userName = $userName ?? '';
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
        <!-- Espaço reservado para ações futuras (ícones, botão PWA, etc.) -->
    </div>
</div>
<?php


// View: Menu (página que centraliza lógica de cabeçalho / footer / navegação)
// Esta view é carregada por ViewRenderer::render('menu') e é exibida dentro de `app.php`.

$pageTitle = $pageTitle ?? 'Menu';
// permitir que a view carregue CSS específico do menu
$customCssPath = $customCssPath ?? '/assets/css/menu.css';
?>

<nav class="menu-list" aria-label="Menu principal">
    <ul class="menu-root">
        <!-- LISTAGENS -->
        <li class="menu-item has-sub">
            <button class="menu-toggle" aria-expanded="false">
                <i class="bi bi-list-ul" aria-hidden="true"></i>Listagens
            </button>
            <ul class="submenu">
                <li><a href="/products/view"><i class="bi bi-eye" aria-hidden="true"></i>Produtos</a></li>
                <li><a href="/churches"><i class="bi bi-building" aria-hidden="true"></i>Igrejas</a></li>
                <li><a href="/asset-types"><i class="bi bi-box-seam" aria-hidden="true"></i>Tipos de Bens</a></li>
                <li><a href="/users"><i class="bi bi-people" aria-hidden="true"></i>Usuários</a></li>
            </ul>
        </li>

        <!-- CADASTROS -->
        <li class="menu-item has-sub">
            <button class="menu-toggle" aria-expanded="false">
                <i class="bi bi-pencil-square" aria-hidden="true"></i>Cadastros
            </button>
            <ul class="submenu">
                <li><a href="/products/create"><i class="bi bi-plus-circle" aria-hidden="true"></i>Produto</a></li>
                <li><a href="/asset-types/create"><i class="bi bi-box-seam" aria-hidden="true"></i>Tipo de Bem</a></li>
                <li><a href="/departments"><i class="bi bi-link-45deg" aria-hidden="true"></i>Dependências</a></li>
                <li><a href="/users/create"><i class="bi bi-person-plus" aria-hidden="true"></i>Usuário</a></li>
            </ul>
        </li>

        <!-- RELATÓRIOS -->
        <li class="menu-item has-sub">
            <button class="menu-toggle" aria-expanded="false">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>Relatórios
            </button>
            <ul class="submenu">
                <li><a href="/reports/14-1"><i class="bi bi-journal-text" aria-hidden="true"></i>14.1</a></li>
                <li><a href="/reports/view"><i class="bi bi-eye-fill" aria-hidden="true"></i>Alterações</a></li>
            </ul>
        </li>

        <!-- OUTROS -->
        <li class="menu-item has-sub">
            <button class="menu-toggle" aria-expanded="false">
                <i class="bi bi-three-dots" aria-hidden="true"></i>Outros
            </button>
            <ul class="submenu">
                <li><a href="/spreadsheets/import"><i class="bi bi-upload" aria-hidden="true"></i>Importar Planilha</a></li>
                <li><a href="/spreadsheets/import-errors"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i>Erros de Importação</a></li>
                <li><a href="/products/label"><i class="bi bi-tags" aria-hidden="true"></i>Códigos de etiquetas</a></li>
            </ul>
        </li>
    </ul>
</nav>

<script src="/assets/js/menu.js"></script>
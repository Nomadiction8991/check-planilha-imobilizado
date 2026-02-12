<?php
// View: Menu (página que centraliza lógica de cabeçalho / footer / navegação)
// Esta view é carregada por ViewRenderer::render('menu') e é exibida dentro de `app.php`.

$pageTitle = $pageTitle ?? 'Menu';
// permitir que a view carregue CSS específico do menu
$customCssPath = $customCssPath ?? '/assets/css/menu.css';
// permite que o layout exiba o nome do usuário caso esteja disponível
$userName = $userName ?? ($usuario['nome'] ?? 'Usuário');
?>

<nav class="menu-list" aria-label="Menu principal">
    <ul class="menu-root">
        <!-- LISTAGENS -->
        <li class="menu-item has-sub">
            <button class="menu-toggle" aria-expanded="false">
                <i class="bi bi-list-ul" aria-hidden="true"></i>Listagens
            </button>
            <ul class="submenu">
                <li><a href="/planilhas/visualizar"><i class="bi bi-eye" aria-hidden="true"></i>Produtos</a></li>
                <li><a href="/produtos"><i class="bi bi-list" aria-hidden="true"></i>Produtos Cadastrados</a></li>
                <li><a href="/comuns"><i class="bi bi-building" aria-hidden="true"></i>Igrejas</a></li>
            </ul>
        </li>

        <!-- CADASTROS -->
        <li class="menu-item has-sub">
            <button class="menu-toggle" aria-expanded="false">
                <i class="bi bi-pencil-square" aria-hidden="true"></i>Cadastros
            </button>
            <ul class="submenu">
                <li><a href="/produtos/criar"><i class="bi bi-plus-circle" aria-hidden="true"></i>Produto</a></li>
                <li><a href="/dependencias"><i class="bi bi-link-45deg" aria-hidden="true"></i>Dependências</a></li>
            </ul>
        </li>

        <!-- RELATÓRIOS -->
        <li class="menu-item has-sub">
            <button class="menu-toggle" aria-expanded="false">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>Relatórios
            </button>
            <ul class="submenu">
                <li><a href="/relatorios/14-1"><i class="bi bi-journal-text" aria-hidden="true"></i>14.1</a></li>
                <li><a href="/relatorios/visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i>Alterações</a></li>
            </ul>
        </li>

        <!-- OUTROS -->
        <li class="menu-item has-sub">
            <button class="menu-toggle" aria-expanded="false">
                <i class="bi bi-three-dots" aria-hidden="true"></i>Outros
            </button>
            <ul class="submenu">
                <li><a href="/planilhas/importar"><i class="bi bi-upload" aria-hidden="true"></i>Importar Planilha</a></li>
                <li><a href="/planilhas/progresso"><i class="bi bi-bar-chart-line" aria-hidden="true"></i>Progresso de Importação</a></li>
                <li><a href="/produtos/etiqueta"><i class="bi bi-tags" aria-hidden="true"></i>Códigos de etiquetas</a></li>
            </ul>
        </li>
    </ul>
</nav>

<script>
    // comportamento simples para abrir/fechar submenus
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.menu-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const expanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                this.parentElement.classList.toggle('open');
            });
        });
    });
</script>
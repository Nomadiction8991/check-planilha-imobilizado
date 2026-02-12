<?php
// View: Menu (página que centraliza lógica de cabeçalho / footer / navegação)
// Esta view é carregada por ViewRenderer::render('menu') e é exibida dentro de `app.php`.

$pageTitle = $pageTitle ?? 'Menu';
// permitir que a view carregue CSS específico do menu
$customCssPath = $customCssPath ?? '/assets/css/menu.css';
// permite que o layout exiba o nome do usuário caso esteja disponível
$userName = $userName ?? ($usuario['nome'] ?? 'Usuário');
?>

<div class="conteudo">
    <nav class="menu-list" aria-label="Menu principal">
        <ul class="menu-root">
            <li class="menu-item">
                <a href="/comuns">Início</a>
            </li>

            <li class="menu-item has-sub">
                <button class="menu-toggle" aria-expanded="false">Planilhas</button>
                <ul class="submenu">
                    <li><a href="/planilhas/importar">Importar Planilha</a></li>
                    <li><a href="/planilhas/visualizar">Visualizar Planilha</a></li>
                    <li><a href="/planilhas/progresso">Progresso de Importação</a></li>
                </ul>
            </li>

            <li class="menu-item has-sub">
                <button class="menu-toggle" aria-expanded="false">Produtos</button>
                <ul class="submenu">
                    <li><a href="/produtos">Listar Produtos</a></li>
                    <li><a href="/produtos/criar">Criar Produto</a></li>
                    <li><a href="/produtos/etiqueta">Copiar Etiquetas</a></li>
                </ul>
            </li>

            <li class="menu-item">
                <a href="/dependencias">Dependências</a>
            </li>

            <li class="menu-item">
                <a href="/usuarios">Usuários</a>
            </li>

            <li class="menu-item has-sub">
                <button class="menu-toggle" aria-expanded="false">Relatórios</button>
                <ul class="submenu">
                    <li><a href="/relatorios/14-1">Relatório 14.1</a></li>
                    <li><a href="/relatorios/visualizar">Visualizar Relatório</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</div>

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
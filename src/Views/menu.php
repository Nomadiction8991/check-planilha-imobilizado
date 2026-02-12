<?php
// View: Menu (página que centraliza lógica de cabeçalho / footer / navegação)
// Esta view é carregada por ViewRenderer::render('menu') e é exibida dentro de `app.php`.

$pageTitle = $pageTitle ?? 'Menu';
// permitir que a view carregue CSS específico do menu
$customCssPath = $customCssPath ?? '/public/assets/css/menu.css';
// permite que o layout exiba o nome do usuário caso esteja disponível
$userName = $userName ?? ($usuario['nome'] ?? 'Usuário');
?>

<div class="conteudo">
    <nav class="menu" aria-label="Menu principal">
        <a class="opcao op1" href="/comuns">Início</a>
        <a class="opcao op2" href="/planilhas/importar">Importar Planilha</a>
        <a class="opcao op3" href="/planilhas/visualizar">Visualizar Planilha</a>
        <a class="opcao op4" href="/produtos">Produtos</a>
        <a class="opcao op5" href="/dependencias">Dependências</a>
        <a class="opcao op1" href="/usuarios">Usuários</a>
        <a class="opcao op2" href="/relatorios/14-1">Relatório 14.1</a>
    </nav>
</div>
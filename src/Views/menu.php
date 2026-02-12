<?php
// View: Menu (página que centraliza lógica de cabeçalho / footer / navegação)
// Esta view é carregada por ViewRenderer::render('menu') e é exibida dentro de `app.php`.

$pageTitle = $pageTitle ?? 'Menu';
// permitir que a view carregue CSS específico do menu
$customCssPath = $customCssPath ?? '/assets/css/menu.css';
// permite que o layout exiba o nome do usuário caso esteja disponível
$userName = $userName ?? ($usuario['nome'] ?? 'Usuário');
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h2 class="card-title">Menu — Gerenciar cabeçalho e rodapé</h2>
            <p class="card-text text-muted">Página dedicada para visualizar e alterar a lógica de navegação, cabeçalho e rodapé. Use os links abaixo para navegar pelas seções do sistema. Esta view também recebe as variáveis de layout (<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>).</p>

            <div class="row gy-3">
                <div class="col-12">
                    <div class="card shadow-sm-custom">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Itens do Menu</h5>
                            <div class="list-group">
                                <a href="/comuns" class="list-group-item list-group-item-action">Início</a>
                                <a href="/planilhas/importar" class="list-group-item list-group-item-action">Importar Planilha</a>
                                <a href="/planilhas/visualizar" class="list-group-item list-group-item-action">Visualizar Planilha</a>
                                <a href="/produtos" class="list-group-item list-group-item-action">Produtos</a>
                                <a href="/dependencias" class="list-group-item list-group-item-action">Dependências</a>
                                <a href="/usuarios" class="list-group-item list-group-item-action">Usuários</a>
                                <a href="/relatorios/14-1" class="list-group-item list-group-item-action">Relatório 14.1</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="card shadow-sm-custom">
                        <div class="card-body">
                            <h5 class="card-title">Cabeçalho</h5>
                            <p class="card-text">Variáveis do cabeçalho (título e usuário) são recebidas por esta view e renderizadas pelo layout.</p>
                            <p class="mb-0"><strong>Título atual:</strong> <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="mb-0"><strong>Usuário:</strong> <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="card shadow-sm-custom">
                        <div class="card-body">
                            <h5 class="card-title">Rodapé</h5>
                            <p class="card-text">Aqui você pode controlar comportamentos do rodapé (ex.: visibilidade dos labels, ações padrão dos botões).</p>
                            <div class="d-flex gap-2 mt-2">
                                <button class="btn btn-outline-primary btn-sm">Configurar labels</button>
                                <button class="btn btn-outline-secondary btn-sm">Testar ações</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

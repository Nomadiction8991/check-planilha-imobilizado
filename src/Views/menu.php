<?php
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';

// Menu como página/modal separada
$pageTitle = 'Menu de Navegação';
$backUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/comuns';

ob_start();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="text-center mb-4">
                <i class="bi bi-list-ul me-2"></i>
                Menu de Navegação
            </h2>

            <!-- Início -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-house-door text-primary me-2"></i>
                        Início
                    </h5>
                    <a href="/comuns" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-house-door me-2"></i>
                        Página Inicial
                    </a>
                </div>
            </div>

            <!-- Planilhas -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>
                        Planilhas
                    </h5>
                    <div class="d-grid gap-2">
                        <a href="/planilhas/importar" class="btn btn-outline-success">
                            <i class="bi bi-upload me-2"></i>
                            Importar Planilha
                        </a>
                        <a href="/planilhas/visualizar" class="btn btn-outline-success">
                            <i class="bi bi-eye me-2"></i>
                            Visualizar Planilha
                        </a>
                        <a href="/planilhas/progresso" class="btn btn-outline-success">
                            <i class="bi bi-bar-chart-line me-2"></i>
                            Progresso de Importação
                        </a>
                    </div>
                </div>
            </div>

            <!-- Produtos -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-box-seam text-info me-2"></i>
                        Produtos
                    </h5>
                    <div class="d-grid gap-2">
                        <a href="/produtos" class="btn btn-outline-info">
                            <i class="bi bi-list-ul me-2"></i>
                            Listar Produtos
                        </a>
                        <a href="/produtos/criar" class="btn btn-outline-info">
                            <i class="bi bi-plus-circle me-2"></i>
                            Criar Produto
                        </a>
                        <a href="/produtos/editar" class="btn btn-outline-info">
                            <i class="bi bi-pencil me-2"></i>
                            Editar Produto
                        </a>
                        <a href="/produtos/etiqueta" class="btn btn-outline-info">
                            <i class="bi bi-tags me-2"></i>
                            Copiar Etiquetas
                        </a>
                    </div>
                </div>
            </div>

            <!-- Dependências -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-diagram-3 text-warning me-2"></i>
                        Dependências
                    </h5>
                    <div class="d-grid gap-2">
                        <a href="/dependencias" class="btn btn-outline-warning">
                            <i class="bi bi-list-ul me-2"></i>
                            Listar Dependências
                        </a>
                        <a href="/dependencias/criar" class="btn btn-outline-warning">
                            <i class="bi bi-plus-circle me-2"></i>
                            Criar Dependência
                        </a>
                        <a href="/dependencias/editar" class="btn btn-outline-warning">
                            <i class="bi bi-pencil me-2"></i>
                            Editar Dependência
                        </a>
                    </div>
                </div>
            </div>

            <!-- Usuários -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-people text-secondary me-2"></i>
                        Usuários
                    </h5>
                    <div class="d-grid gap-2">
                        <a href="/usuarios" class="btn btn-outline-secondary">
                            <i class="bi bi-list-ul me-2"></i>
                            Listar Usuários
                        </a>
                        <a href="/usuarios/criar" class="btn btn-outline-secondary">
                            <i class="bi bi-plus-circle me-2"></i>
                            Criar Usuário
                        </a>
                        <a href="/usuarios/editar" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil me-2"></i>
                            Editar Usuário
                        </a>
                    </div>
                </div>
            </div>

            <!-- Relatórios -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-file-earmark-text text-danger me-2"></i>
                        Relatórios
                    </h5>
                    <div class="d-grid gap-2">
                        <a href="/relatorios/14-1" class="btn btn-outline-danger">
                            <i class="bi bi-file-earmark-pdf me-2"></i>
                            Relatório 14.1
                        </a>
                        <a href="/relatorios/visualizar" class="btn btn-outline-danger">
                            <i class="bi bi-eye me-2"></i>
                            Visualizar Relatório
                        </a>
                        <a href="/relatorios/assinatura" class="btn btn-outline-danger">
                            <i class="bi bi-pen me-2"></i>
                            Assinatura Digital
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sair -->
            <div class="card">
                <div class="card-body">
                    <a href="/logout" class="btn btn-outline-danger w-100">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        Sair do Sistema
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
include __DIR__ . '/../layouts/app_wrapper.php';
?>
<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
$pageTitle = 'PROGRESSO DA IMPORTAÇÃO';
$backUrl = null;
$importacaoId = $importacao_id ?? 0;

ob_start();
?>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <i class="bi bi-hourglass-split me-2"></i>
            PROCESSANDO IMPORTAÇÃO
        </div>
        <div class="card-body">
            <!-- Status -->
            <div class="mb-3 text-center">
                <h6 id="status-text">Preparando importação...</h6>
            </div>

            <!-- Barra de Progresso -->
            <div class="mb-3">
                <div class="progress" style="height: 25px;">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                        role="progressbar"
                        style="width: 0%"
                        aria-valuenow="0"
                        aria-valuemin="0"
                        aria-valuemax="100">
                        <span id="progress-text" style="font-size: 0.85rem;">0%</span>
                    </div>
                </div>
            </div>

            <!-- Informações Detalhadas (2 colunas para caber no mobile 400px) -->
            <div class="row text-center g-2">
                <div class="col-6">
                    <div class="border rounded p-2 mb-1">
                        <small class="text-muted d-block">TOTAL</small>
                        <strong id="total-linhas">-</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2 mb-1">
                        <small class="text-muted d-block">PROCESSADAS</small>
                        <strong id="linhas-processadas" class="text-primary">-</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2 mb-1">
                        <small class="text-muted d-block">SUCESSO</small>
                        <strong id="linhas-sucesso" class="text-success">-</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2 mb-1">
                        <small class="text-muted d-block">ERROS</small>
                        <strong id="linhas-erro" class="text-danger">-</strong>
                    </div>
                </div>
            </div>

            <!-- Status do Arquivo -->
            <div class="mt-2">
                <small class="text-muted">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    <span id="arquivo-nome">Carregando...</span>
                </small>
            </div>

            <!-- Mensagem de Erro -->
            <div id="erro-container" class="alert alert-danger mt-3" style="display: none;">
                <h6><i class="bi bi-exclamation-triangle me-2"></i>ERRO</h6>
                <p id="erro-mensagem" class="mb-0"></p>
            </div>

            <!-- Mensagem de Sucesso -->
            <div id="sucesso-container" class="alert alert-success mt-3" style="display: none;">
                <h6><i class="bi bi-check-circle me-2"></i>IMPORTAÇÃO CONCLUÍDA!</h6>
                <p class="mb-0">
                    <span id="sucesso-linhas">0</span> linhas importadas com sucesso.
                    <span id="sucesso-erros-txt"></span>
                </p>
                <div class="mt-3 d-grid gap-2">
                    <a href="/spreadsheets/view" class="btn btn-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>VISUALIZAR PRODUTOS
                    </a>
                    <a href="/spreadsheets/import" class="btn btn-secondary btn-sm">
                        <i class="bi bi-upload me-1"></i>NOVA IMPORTAÇÃO
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window._importacaoId = <?= (int)$importacaoId ?>;
</script>
<script src="/assets/js/spreadsheets/import-progress.js"></script>

<link href="/assets/css/planilhas/importacao_progresso.css" rel="stylesheet">

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
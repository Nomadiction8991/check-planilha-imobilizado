<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
$pageTitle = 'PROGRESSO DA IMPORTAÇÃO';
$backUrl = null;
$importacaoId = $importacao_id ?? 0;

ob_start();
?>

<div class="w-full max-w-2xl mx-auto py-4">
    <div class="border border-neutral-200 bg-white" style="border-radius:2px">
        <div class="bg-neutral-50 px-4 py-3 border-b border-neutral-200">
            <i class="bi bi-hourglass-split me-2"></i>
            PROCESSANDO IMPORTAÇÃO
        </div>
        <div class="p-4">
            <!-- Status -->
            <div class="mb-4 text-center">
                <h6 id="status-text" class="text-gray-700">Preparando importação...</h6>
            </div>

            <!-- Barra de Progresso -->
            <div class="mb-4">
                <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div id="progress-bar" class="h-full bg-black transition-all duration-300"
                        style="width: 0%"
                        aria-valuenow="0"
                        aria-valuemin="0"
                        aria-valuemax="100">
                    </div>
                </div>
                <div class="text-center mt-2 text-sm font-semibold text-gray-700">
                    <span id="progress-text">0%</span>
                </div>
            </div>

            <!-- Informações Detalhadas (2 colunas para caber no mobile 400px) -->
            <div class="grid grid-cols-2 gap-2 text-center mb-4">
                <div class="border border-neutral-200 rounded px-2 py-2">
                    <small class="text-gray-500 block">TOTAL</small>
                    <strong id="total-linhas" class="text-gray-900">-</strong>
                </div>
                <div class="border border-neutral-200 rounded px-2 py-2">
                    <small class="text-gray-500 block">PROCESSADAS</small>
                    <strong id="linhas-processadas" class="text-black">-</strong>
                </div>
                <div class="border border-neutral-200 rounded px-2 py-2">
                    <small class="text-gray-500 block">SUCESSO</small>
                    <strong id="linhas-sucesso" class="text-black">-</strong>
                </div>
                <div class="border border-neutral-200 rounded px-2 py-2">
                    <small class="text-gray-500 block">ERROS</small>
                    <strong id="linhas-erro" class="text-black">-</strong>
                </div>
            </div>

            <!-- Status do Arquivo -->
            <div class="mt-3">
                <small class="text-gray-500">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    <span id="arquivo-nome">Carregando...</span>
                </small>
            </div>

            <!-- Mensagem de Erro -->
            <div id="erro-container" class="bg-neutral-50 border border-black rounded px-4 py-3 mt-4 hidden">
                <h6 class="font-semibold text-black"><i class="bi bi-exclamation-circle me-2"></i>ERRO</h6>
                <p id="erro-mensagem" class="text-black text-sm mt-1"></p>
            </div>

            <!-- Mensagem de Sucesso -->
            <div id="sucesso-container" class="bg-neutral-50 border border-neutral-300 rounded px-4 py-3 mt-4 hidden">
                <h6 class="font-semibold text-black"><i class="bi bi-check-circle me-2"></i>IMPORTAÇÃO CONCLUÍDA</h6>
                <p class="text-black text-sm mt-2">
                    <span id="sucesso-linhas">0</span> linhas importadas com sucesso.
                    <span id="sucesso-erros-txt"></span>
                </p>
                <div class="mt-3 flex flex-col gap-2">
                    <a href="/products/view" class="w-full px-3 py-2 bg-black text-white font-semibold rounded transition text-center text-sm">
                        <i class="bi bi-eye me-1"></i>VISUALIZAR PRODUTOS
                    </a>
                    <a href="/spreadsheets/import" class="w-full px-3 py-2 border border-neutral-300 text-neutral-700 font-semibold rounded transition text-center text-sm" style="text-decoration:none" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background=''">
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

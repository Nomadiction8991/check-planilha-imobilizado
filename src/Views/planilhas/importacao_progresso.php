<?php
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
                    <a href="/planilhas/visualizar" class="btn btn-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>VISUALIZAR PRODUTOS
                    </a>
                    <a href="/planilhas/importar" class="btn btn-secondary btn-sm">
                        <i class="bi bi-upload me-1"></i>NOVA IMPORTAÇÃO
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const importacaoId = <?= (int)$importacaoId ?>;
    let processamentoIniciado = false;
    let intervaloAtualizacao = null;

    function iniciarProcessamento() {
        if (processamentoIniciado) return;
        processamentoIniciado = true;

        fetch('/planilhas/processar-arquivo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + importacaoId
            })
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    mostrarErro(data.erro);
                    pararAtualizacao();
                }
            })
            .catch(error => console.error('Erro ao processar:', error));
    }

    function atualizarProgresso() {
        fetch('/planilhas/api/progresso?id=' + importacaoId)
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    mostrarErro(data.erro);
                    pararAtualizacao();
                    return;
                }

                document.getElementById('total-linhas').textContent = data.total_linhas.toLocaleString();
                document.getElementById('linhas-processadas').textContent = data.linhas_processadas.toLocaleString();
                document.getElementById('linhas-sucesso').textContent = data.linhas_sucesso.toLocaleString();
                document.getElementById('linhas-erro').textContent = data.linhas_erro.toLocaleString();
                document.getElementById('arquivo-nome').textContent = data.arquivo_nome;

                const porcentagem = Math.round(data.porcentagem);
                const progressBar = document.getElementById('progress-bar');
                const progressText = document.getElementById('progress-text');

                progressBar.style.width = porcentagem + '%';
                progressBar.setAttribute('aria-valuenow', porcentagem);
                progressText.textContent = porcentagem + '%';

                let statusTexto = 'Processando...';
                if (data.status === 'aguardando') {
                    statusTexto = 'Aguardando início...';
                } else if (data.status === 'processando') {
                    statusTexto = 'Processando linhas...';
                } else if (data.status === 'concluida') {
                    statusTexto = 'Importação concluída!';
                    mostrarSucesso(data.linhas_sucesso, data.linhas_erro);
                    pararAtualizacao();
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.classList.add('bg-success');
                } else if (data.status === 'erro') {
                    statusTexto = 'Erro na importação';
                    mostrarErro(data.mensagem_erro || 'Erro desconhecido');
                    pararAtualizacao();
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.classList.add('bg-danger');
                }

                document.getElementById('status-text').textContent = statusTexto;
            })
            .catch(error => console.error('Erro ao buscar progresso:', error));
    }

    function mostrarErro(mensagem) {
        document.getElementById('erro-mensagem').textContent = mensagem;
        document.getElementById('erro-container').style.display = 'block';
    }

    function mostrarSucesso(sucesso, erros) {
        document.getElementById('sucesso-linhas').textContent = sucesso.toLocaleString();
        if (erros > 0) {
            document.getElementById('sucesso-erros-txt').textContent = erros + ' linha(s) com erro.';
        }
        document.getElementById('sucesso-container').style.display = 'block';
    }

    function pararAtualizacao() {
        if (intervaloAtualizacao) {
            clearInterval(intervaloAtualizacao);
            intervaloAtualizacao = null;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        atualizarProgresso();
        setTimeout(iniciarProcessamento, 500);
        intervaloAtualizacao = setInterval(atualizarProgresso, 1500);
    });

    window.addEventListener('beforeunload', function() { pararAtualizacao(); });
</script>

<style>
    .progress { background-color: #e9ecef; border-radius: 0.5rem; overflow: hidden; }
    .progress-bar { font-weight: 600; display: flex; align-items: center; justify-content: center; transition: width 0.3s ease; }
    #progress-text { color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.3); }
</style>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/../../../storage/tmp/temp_progresso_' . uniqid() . '.php';
file_put_contents($contentFile, $contentHtml);
include __DIR__ . '/../layouts/app.php';
@unlink($contentFile);
?>
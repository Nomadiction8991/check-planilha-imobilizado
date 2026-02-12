<?php
$pageTitle = 'PROGRESSO DA IMPORTAÇÃO';
$backUrl = null;
$importacaoId = $importacao_id ?? 0;
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
                <h5 id="status-text">Preparando importação...</h5>
            </div>

            <!-- Barra de Progresso -->
            <div class="mb-3">
                <div class="progress" style="height: 30px;">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                        role="progressbar"
                        style="width: 0%"
                        aria-valuenow="0"
                        aria-valuemin="0"
                        aria-valuemax="100">
                        <span id="progress-text">0%</span>
                    </div>
                </div>
            </div>

            <!-- Informações Detalhadas -->
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="border rounded p-3 mb-2">
                        <h6 class="text-muted mb-1">TOTAL DE LINHAS</h6>
                        <h4 id="total-linhas" class="mb-0">-</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 mb-2">
                        <h6 class="text-muted mb-1">PROCESSADAS</h6>
                        <h4 id="linhas-processadas" class="mb-0 text-primary">-</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 mb-2">
                        <h6 class="text-muted mb-1">SUCESSO</h6>
                        <h4 id="linhas-sucesso" class="mb-0 text-success">-</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 mb-2">
                        <h6 class="text-muted mb-1">ERROS</h6>
                        <h4 id="linhas-erro" class="mb-0 text-danger">-</h4>
                    </div>
                </div>
            </div>

            <!-- Status do Arquivo -->
            <div class="mt-3">
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
                <div class="mt-3">
                    <a href="/planilhas/visualizar" class="btn btn-primary">
                        <i class="bi bi-eye me-2"></i>VISUALIZAR PRODUTOS
                    </a>
                    <a href="/planilhas/importar" class="btn btn-secondary">
                        <i class="bi bi-upload me-2"></i>NOVA IMPORTAÇÃO
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const importacaoId = <?= $importacaoId ?>;
    let processamentoIniciado = false;
    let intervaloAtualizacao = null;

    // Inicia processamento
    function iniciarProcessamento() {
        if (processamentoIniciado) return;
        processamentoIniciado = true;

        fetch('/planilhas/processar-arquivo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + importacaoId
            })
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    mostrarErro(data.erro);
                    pararAtualizacao();
                }
            })
            .catch(error => {
                console.error('Erro ao processar:', error);
            });
    }

    // Atualiza progresso
    function atualizarProgresso() {
        fetch('/planilhas/api/progresso?id=' + importacaoId)
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    mostrarErro(data.erro);
                    pararAtualizacao();
                    return;
                }

                // Atualiza informações
                document.getElementById('total-linhas').textContent = data.total_linhas.toLocaleString();
                document.getElementById('linhas-processadas').textContent = data.linhas_processadas.toLocaleString();
                document.getElementById('linhas-sucesso').textContent = data.linhas_sucesso.toLocaleString();
                document.getElementById('linhas-erro').textContent = data.linhas_erro.toLocaleString();
                document.getElementById('arquivo-nome').textContent = data.arquivo_nome;

                // Atualiza barra de progresso
                const porcentagem = Math.round(data.porcentagem);
                const progressBar = document.getElementById('progress-bar');
                const progressText = document.getElementById('progress-text');

                progressBar.style.width = porcentagem + '%';
                progressBar.setAttribute('aria-valuenow', porcentagem);
                progressText.textContent = porcentagem + '%';

                // Atualiza status
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
            .catch(error => {
                console.error('Erro ao buscar progresso:', error);
            });
    }

    function mostrarErro(mensagem) {
        document.getElementById('erro-mensagem').textContent = mensagem;
        document.getElementById('erro-container').style.display = 'block';
    }

    function mostrarSucesso(sucesso, erros) {
        document.getElementById('sucesso-linhas').textContent = sucesso.toLocaleString();

        if (erros > 0) {
            document.getElementById('sucesso-erros-txt').textContent =
                erros + ' linha(s) com erro.';
        }

        document.getElementById('sucesso-container').style.display = 'block';
    }

    function pararAtualizacao() {
        if (intervaloAtualizacao) {
            clearInterval(intervaloAtualizacao);
            intervaloAtualizacao = null;
        }
    }

    // Inicia quando a página carregar
    document.addEventListener('DOMContentLoaded', function() {
        // Primeira atualização imediata
        atualizarProgresso();

        // Inicia processamento em background
        setTimeout(iniciarProcessamento, 500);

        // Atualiza a cada 1 segundo
        intervaloAtualizacao = setInterval(atualizarProgresso, 1000);
    });

    // Para de atualizar se o usuário sair da página
    window.addEventListener('beforeunload', function() {
        pararAtualizacao();
    });
</script>

<style>
    .progress {
        background-color: #e9ecef;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .progress-bar {
        font-size: 1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: width 0.3s ease;
    }

    #progress-text {
        color: white;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

    .border.rounded {
        transition: all 0.3s ease;
    }

    .border.rounded:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
</style>
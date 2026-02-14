const importacaoId = window._importacaoId;
let processamentoIniciado = false;
let intervaloAtualizacao = null;

function iniciarProcessamento() {
    if (processamentoIniciado) return;
    processamentoIniciado = true;

    fetch('/spreadsheets/process-file', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
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
        .catch(error => console.error('Erro ao processar:', error));
}

function atualizarProgresso() {
    fetch('/spreadsheets/api/progress?id=' + importacaoId)
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

window.addEventListener('beforeunload', function() {
    pararAtualizacao();
});

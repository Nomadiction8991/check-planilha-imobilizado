(() => {
    'use strict';

    const config = window.importProgressConfig || {};
    let started = false;
    let intervalId = null;
    let startTimeoutId = null;
    let terminalState = false;
    let finalState = null;

    function stopPolling() {
        if (intervalId !== null) {
            clearInterval(intervalId);
            intervalId = null;
        }

        if (startTimeoutId !== null) {
            clearTimeout(startTimeoutId);
            startTimeoutId = null;
        }
    }

    function showError(message) {
        if (finalState === 'success') {
            return;
        }

        finalState = 'error';

        const container = document.getElementById('erro-container');
        const target = document.getElementById('erro-mensagem');
        const successContainer = document.getElementById('sucesso-container');
        if (target) {
            target.textContent = message;
        }
        if (successContainer) {
            successContainer.style.display = 'none';
        }
        if (container) {
            container.style.display = 'grid';
        }
    }

    function showSuccess(success, errors) {
        finalState = 'success';

        const container = document.getElementById('sucesso-container');
        const successTarget = document.getElementById('sucesso-linhas');
        const errorTarget = document.getElementById('sucesso-erros-txt');
        const errorLink = document.getElementById('erros-importacao-link');
        const errorContainer = document.getElementById('erro-container');

        if (successTarget) {
            successTarget.textContent = String(success);
        }
        if (errorTarget) {
            errorTarget.textContent = errors > 0 ? `${errors} linha(s) com erro.` : '';
        }
        if (errorLink) {
            errorLink.style.display = errors > 0 ? 'inline-flex' : 'none';
        }
        if (errorContainer) {
            errorContainer.style.display = 'none';
        }
        if (container) {
            container.style.display = 'grid';
        }
    }

    async function startProcessing() {
        if (started || terminalState) {
            return;
        }

        started = true;

        try {
            const progressResponse = await fetch(config.progressUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const progressData = await progressResponse.json();

            if (progressResponse.ok && progressData.success === true) {
                if (progressData.status === 'concluida') {
                    terminalState = true;
                    showSuccess(progressData.linhas_sucesso, progressData.linhas_erro);
                    stopPolling();
                    return;
                }

                if (progressData.status === 'erro') {
                    terminalState = true;
                    showError(progressData.mensagem_erro || 'Erro na importação.');
                    stopPolling();
                    return;
                }
            }
        } catch (error) {
            // Ignora a pré-checagem e tenta iniciar normalmente.
        }

        const response = await fetch(config.startUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();

        if (!response.ok || data.success !== true) {
            throw new Error(data.message || 'Erro ao iniciar processamento.');
        }
    }

    async function updateProgress() {
        const response = await fetch(config.progressUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();

        if (!response.ok || data.success !== true) {
            throw new Error(data.message || 'Erro ao consultar progresso.');
        }

        const percentage = Math.round(data.porcentagem);
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const statusText = document.getElementById('status-text');
        const progressShell = document.getElementById('processing-shell');
        const arquivoNome = document.getElementById('arquivo-nome');
        const totalLinhas = document.getElementById('total-linhas');
        const linhasProcessadas = document.getElementById('linhas-processadas');
        const linhasSucesso = document.getElementById('linhas-sucesso');
        const linhasErro = document.getElementById('linhas-erro');

        if (arquivoNome) {
            arquivoNome.textContent = data.arquivo_nome || '';
        }
        if (totalLinhas) {
            totalLinhas.textContent = String(data.total_linhas ?? 0);
        }
        if (linhasProcessadas) {
            linhasProcessadas.textContent = String(data.linhas_processadas ?? 0);
        }
        if (linhasSucesso) {
            linhasSucesso.textContent = String(data.linhas_sucesso ?? 0);
        }
        if (linhasErro) {
            linhasErro.textContent = String(data.linhas_erro ?? 0);
        }

        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
            progressBar.classList.toggle('is-complete', data.status === 'concluida');
        }

        if (progressShell) {
            progressShell.classList.toggle('is-complete', data.status === 'concluida');
        }
        if (progressText) {
            progressText.textContent = `${percentage}%`;
        }

        if (statusText) {
            statusText.textContent = data.status === 'concluida'
                ? 'Importação concluída!'
                : (data.status === 'erro' ? 'Erro na importação' : 'Processando linhas...');
        }

        if (data.status === 'concluida') {
            terminalState = true;
            showSuccess(data.linhas_sucesso, data.linhas_erro);
            stopPolling();
        }

        if (data.status === 'erro') {
            terminalState = true;
            showError(data.mensagem_erro || 'Erro na importação.');
            stopPolling();
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        try {
            await updateProgress();
            if (terminalState) {
                return;
            }

            startTimeoutId = window.setTimeout(async () => {
                if (terminalState) {
                    return;
                }

                try {
                    await startProcessing();
                } catch (error) {
                    showError(error.message);
                    stopPolling();
                }
            }, 300);
            intervalId = setInterval(async () => {
                if (terminalState) {
                    stopPolling();
                    return;
                }

                try {
                    await updateProgress();
                } catch (error) {
                    showError(error.message);
                    stopPolling();
                }
            }, 1200);
        } catch (error) {
            showError(error.message);
            stopPolling();
        }
    });

    window.addEventListener('beforeunload', stopPolling);
})();

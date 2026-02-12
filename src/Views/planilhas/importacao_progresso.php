<?php
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';

$pageTitle = 'Progresso da Importação';

$backUrl = null;
$jobId = $_GET['job'] ?? '';

if ($jobId === '') {
    $_SESSION['mensagem'] = 'Job de importação não informado.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ' . base_url('/planilhas/importar'));
    exit;
}

ob_start();
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-cloud-upload"></i>
            <span id="job-id"><?php echo htmlspecialchars($jobId, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>
    <div class="card-body">
        <p class="mb-3">Estamos processando sua planilha em lotes de 200 registros para evitar travamentos. Esta página irá atualizar o progresso automaticamente.</p>

        <div class="progress mb-3" style="height: 32px;">
            <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">0%</div>
        </div>

        <div class="row row-cols-1 g-2 mb-3 text-start">
            <div class="col"><strong>Processados:</strong> <span id="processed">0</span></div>
            <div class="col"><strong>Total estimado:</strong> <span id="total">0</span></div>
            <div class="col"><strong>Novos:</strong> <span id="novos">0</span></div>
            <div class="col"><strong>Atualizados:</strong> <span id="atualizados">0</span></div>
            <div class="col"><strong>Excluídos:</strong> <span id="excluidos">0</span></div>
        </div>

        <div id="alert-area"></div>

        <div id="errors-note" class="alert alert-warning mt-3" role="alert" style="display: none;">
            <strong>Erros acumulados:</strong>
            <ul id="errors-list" class="mb-0 mt-2"></ul>
        </div>

        <div class="mt-3">
            <div class="fw-bold mb-2">Log</div>
            <div id="log-box" class="border rounded p-2 bg-light" style="max-height: 220px; overflow-y: auto; font-size: 0.9rem;"></div>
        </div>

        <div id="done-actions" class="text-center mt-4" style="display: none;">
            <p class="mb-2">Importação finalizada.</p>
            <a id="go-comuns" class="btn btn-primary" style="display:none;">Voltar para listagem de comuns</a>
        </div>

        <div class="text-center mt-4" id="processing-note">
            <p class="mt-2 mb-0">Processando lotes. Você pode manter esta aba aberta; ao concluir, use o botão para voltar.</p>
        </div>

        <div class="mt-3">
            <button id="cancel-btn" type="button" class="btn btn-outline-danger w-100 py-3">Cancelar</button>
            <button id="conclude-btn" type="button" class="btn btn-success w-100 py-3" style="display:none; margin-top:8px;">Concluir</button>
        </div>
    </div>
</div>

<script>
    (function() {
        const baseUrl = <?php echo json_encode(base_url()); ?>;
        const jobId = <?php echo json_encode($jobId); ?>;
        const progressBar = document.getElementById('progress-bar');
        const processedEl = document.getElementById('processed');
        const totalEl = document.getElementById('total');
        const novosEl = document.getElementById('novos');
        const atualizadosEl = document.getElementById('atualizados');
        const excluidosEl = document.getElementById('excluidos');
        const alertArea = document.getElementById('alert-area');
        const cancelBtn = document.getElementById('cancel-btn');
        const errorsNote = document.getElementById('errors-note');
        const errorsList = document.getElementById('errors-list');
        const doneActions = document.getElementById('done-actions');
        const goComuns = document.getElementById('go-comuns');
        const processingNote = document.getElementById('processing-note');
        const logBox = document.getElementById('log-box');

        const errorsAccum = new Set();

        let canceled = false;
        let fatal = false;

        function setProgress(percent) {
            const pct = Math.max(0, Math.min(100, percent));
            progressBar.style.width = pct + '%';
            progressBar.setAttribute('aria-valuenow', pct);
            progressBar.textContent = pct.toFixed(2) + '%';
        }

        function showAlert(type, message) {
            alertArea.innerHTML = '<div class="alert alert-' + type + '" role="alert">' + message + '</div>';
        }

        function formatLogEntry(entry) {
            const ts = entry.ts ? new Date(entry.ts * 1000) : new Date();
            const stamp = ts.toLocaleTimeString('pt-BR');
            const lvl = (entry.level || 'info').toUpperCase();
            const msg = entry.message || '';
            return '[' + stamp + '] ' + lvl + ': ' + msg;
        }

        function renderLog(entries) {
            if (!logBox) return;
            logBox.innerHTML = '';
            entries.forEach(e => {
                const div = document.createElement('div');
                div.textContent = formatLogEntry(e);
                logBox.appendChild(div);
            });
            logBox.scrollTop = logBox.scrollHeight;
        }

        function appendLog(level, message) {
            if (!logBox) return;
            const entry = {
                ts: Math.floor(Date.now() / 1000),
                level,
                message
            };
            const div = document.createElement('div');
            div.textContent = formatLogEntry(entry);
            logBox.appendChild(div);
            logBox.scrollTop = logBox.scrollHeight;
        }

        function pushErrors(errs) {
            if (!errs || !errs.length) return;
            errs.forEach(e => {
                if (!errorsAccum.has(e)) {
                    errorsAccum.add(e);
                    const li = document.createElement('li');
                    li.textContent = e;
                    errorsList.appendChild(li);
                }
            });
            if (errorsAccum.size > 0) {
                errorsNote.style.display = '';
            }
        }

        async function cancelImport() {
            if (!confirm('Cancelar esta importação?')) {
                return;
            }
            cancelBtn.disabled = true;
            canceled = true;
            try {
                const resp = await fetch(baseUrl + 'planilhas/importar?action=cancel&job=' + encodeURIComponent(jobId), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await resp.json();
                if (!resp.ok) {
                    showAlert('danger', data.message || 'Falha ao cancelar importação.');
                    appendLog('erro', data.message || 'Falha ao cancelar importação.');
                    cancelBtn.disabled = false;
                    canceled = false;
                    return;
                }
                const msg = 'Importação cancelada.';
                showAlert('warning', msg);
                appendLog('aviso', msg);
            } catch (err) {
                showAlert('danger', 'Erro ao cancelar: ' + err);
                appendLog('erro', 'Erro ao cancelar: ' + err);
                cancelBtn.disabled = false;
                canceled = false;
            }
        }

        async function concludeImport() {
            const concludeBtn = document.getElementById('conclude-btn');
            concludeBtn.disabled = true;
            try {
                const resp = await fetch(baseUrl + 'planilhas/importar?action=finish&job=' + encodeURIComponent(jobId), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await resp.json();
                if (!resp.ok) {
                    showAlert('danger', data.message || 'Falha ao finalizar importação.');
                    appendLog('erro', data.message || 'Falha ao finalizar importação.');
                    concludeBtn.disabled = false;
                    return;
                }
                const redirect = data.redirect || (baseUrl + 'index.php');
                window.location.href = redirect;
            } catch (err) {
                showAlert('danger', 'Erro ao finalizar: ' + err);
                appendLog('erro', 'Erro ao finalizar: ' + err);
                concludeBtn.disabled = false;
            }
        }

        async function poll() {
            if (canceled) {
                return;
            }
            if (fatal) {
                return;
            }
            try {
                const resp = await fetch(baseUrl + 'planilhas/importar?action=process&job=' + encodeURIComponent(jobId), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!resp.ok) {
                    const text = await resp.text();
                    const msg = 'Erro ao processar: ' + text;
                    showAlert('danger', msg);
                    appendLog('erro', msg);
                    fatal = true;
                    return;
                }
                const data = await resp.json();

                if (data.total !== undefined) {
                    totalEl.textContent = data.total;
                }
                if (data.stats) {
                    processedEl.textContent = data.stats.processados ?? 0;
                    novosEl.textContent = data.stats.novos ?? 0;
                    atualizadosEl.textContent = data.stats.atualizados ?? 0;
                    excluidosEl.textContent = data.stats.excluidos ?? 0;
                }
                if (data.progress !== undefined) {
                    setProgress(data.progress);
                }

                if (data.log && Array.isArray(data.log)) {
                    renderLog(data.log);
                }

                if (data.errors && data.errors.length > 0) {
                    showAlert('warning', 'Erros até agora: ' + data.errors.slice(-3).join(' | '));
                    pushErrors(data.errors);
                    appendLog('aviso', 'Erros recebidos: ' + data.errors.slice(-3).join(' | '));
                }

                if (data.done) {
                    setProgress(100);
                    const message = data.message || 'Importação finalizada.';
                    const redirect = data.redirect || (baseUrl + 'index.php');
                    if (data.errors && data.errors.length > 0) {
                        showAlert('warning', message + ' Verifique as linhas com erro.');
                        pushErrors(data.errors);
                        appendLog('aviso', 'Finalizada com erros: ' + data.errors.length + ' ocorrência(s).');
                    } else {
                        showAlert('success', message);
                        appendLog('ok', 'Finalizada com sucesso.');
                    }
                    if (processingNote) processingNote.style.display = 'none';
                    if (doneActions) doneActions.style.display = '';
                    // esconder botão cancelar e exibir botão Concluir
                    try {
                        if (cancelBtn) cancelBtn.style.display = 'none';
                        const concludeBtn = document.getElementById('conclude-btn');
                        if (concludeBtn) {
                            concludeBtn.style.display = '';
                            concludeBtn.addEventListener('click', concludeImport);
                        }
                    } catch (e) {
                        // noop
                    }
                    if (goComuns) {
                        goComuns.setAttribute('href', redirect);
                        goComuns.style.display = 'inline-block';
                        goComuns.addEventListener('click', () => {
                            window.location.href = redirect;
                        });
                    }
                    return;
                }

                setTimeout(poll, 400);
            } catch (err) {
                showAlert('danger', 'Falha na requisição: ' + err);
                appendLog('erro', 'Falha na requisição: ' + err);
                setTimeout(poll, 3000);
            }
        }

        cancelBtn.addEventListener('click', cancelImport);
        poll();
    })();
</script>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/../../../temp_importar_planilha_content_' . uniqid() . '.php';
file_put_contents($contentFile, $contentHtml);
include_once __DIR__ . '/../layouts/app.php';
@unlink($contentFile);
?>
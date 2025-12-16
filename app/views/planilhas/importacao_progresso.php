<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Progresso da Importação';
$backUrl = base_url('index.php');
$jobId = $_GET['job'] ?? '';

if ($jobId === '') {
    $_SESSION['mensagem'] = 'Job de importação não informado.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ' . base_url('app/views/planilhas/planilha_importar.php'));
    exit;
}

ob_start();
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-cloud-upload"></i>
            <?php echo htmlspecialchars(to_uppercase('Importando planilha'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary" id="job-id">JOB: <?php echo htmlspecialchars($jobId, ENT_QUOTES, 'UTF-8'); ?></span>
            <button id="cancel-btn" type="button" class="btn btn-outline-danger btn-sm">Cancelar</button>
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

        <div class="text-center mt-4">
            <p class="mt-2 mb-0">Processando lotes. Você pode manter esta aba aberta; ao concluir, você será redirecionado.</p>
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

    const errorsAccum = new Set();

    let canceled = false;

    function setProgress(percent) {
        const pct = Math.max(0, Math.min(100, percent));
        progressBar.style.width = pct + '%';
        progressBar.setAttribute('aria-valuenow', pct);
        progressBar.textContent = pct.toFixed(2) + '%';
    }

    function showAlert(type, message) {
        alertArea.innerHTML = '<div class="alert alert-' + type + '" role="alert">' + message + '</div>';
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
            const resp = await fetch(baseUrl + 'app/controllers/create/ImportacaoPlanilhaController.php?action=cancel&job=' + encodeURIComponent(jobId), {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const data = await resp.json();
            if (!resp.ok) {
                showAlert('danger', data.message || 'Falha ao cancelar importação.');
                cancelBtn.disabled = false;
                canceled = false;
                return;
            }
            showAlert('warning', 'Importação cancelada. Redirecionando...');
            const redirect = data.redirect || (baseUrl + 'app/views/planilhas/planilha_importar.php');
            setTimeout(() => { window.location.href = redirect; }, 800);
        } catch (err) {
            showAlert('danger', 'Erro ao cancelar: ' + err);
            cancelBtn.disabled = false;
            canceled = false;
        }
    }

    async function poll() {
        if (canceled) {
            return;
        }
        try {
            const resp = await fetch(baseUrl + 'app/controllers/create/ImportacaoPlanilhaController.php?action=process&job=' + encodeURIComponent(jobId), {
                headers: { 'Accept': 'application/json' }
            });
            if (!resp.ok) {
                const text = await resp.text();
                showAlert('danger', 'Erro ao processar: ' + text);
                setTimeout(poll, 3000);
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

            if (data.errors && data.errors.length > 0) {
                showAlert('warning', 'Erros até agora: ' + data.errors.slice(-3).join(' | '));
                pushErrors(data.errors);
            }

            if (data.done) {
                setProgress(100);
                const message = data.message || 'Importação finalizada.';
                const redirect = data.redirect || (baseUrl + 'app/views/planilhas/planilha_importar.php');
                if (data.errors && data.errors.length > 0) {
                    showAlert('warning', message + ' Verifique as linhas com erro.');
                    pushErrors(data.errors);
                } else {
                    showAlert('success', message);
                }
                setTimeout(() => { window.location.href = redirect; }, 1500);
                return;
            }

            setTimeout(poll, 400);
        } catch (err) {
            showAlert('danger', 'Falha na requisição: ' + err);
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
include __DIR__ . '/../layouts/app_wrapper.php';
@unlink($contentFile);
?>

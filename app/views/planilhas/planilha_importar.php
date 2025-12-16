<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 
$pageTitle = 'Importar Planilha';
$backUrl = '../../../index.php';

$jobDir = realpath(__DIR__ . '/../../../storage/tmp');
$jobEmExecucao = null;
if ($jobDir !== false) {
    foreach (glob($jobDir . '/import_job_*.json') ?: [] as $jobFile) {
        $data = json_decode(@file_get_contents($jobFile), true);
        if (is_array($data)) {
            $status = $data['status'] ?? 'ready';
            if ($status !== 'done') {
                $jobEmExecucao = $data['id'] ?? basename($jobFile, '.json');
                break;
            }
        }
    }
}
 
ob_start();
?>

<?php if ($jobEmExecucao): ?>
<!-- Modal: job em andamento (mostra imediatamente e bloqueia nova importação) -->
<div class="modal fade" id="job-active-modal" tabindex="-1" aria-labelledby="job-active-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="job-active-title">Importação em andamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Já existe uma importação em progresso (ID: <?php echo htmlspecialchars($jobEmExecucao, ENT_QUOTES, 'UTF-8'); ?>). Conclua ou cancele antes de iniciar uma nova.
            </div>
            <div class="modal-footer">
                <a class="btn btn-primary" href="/app/views/planilhas/importacao_progresso.php?job=<?php echo urlencode($jobEmExecucao); ?>">Ver progresso</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<form action="../../../app/controllers/create/ImportacaoPlanilhaController.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
    <!-- Arquivo CSV -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-file-earmark-arrow-up me-2"></i>
            <?php echo htmlspecialchars(to_uppercase('Arquivo CSV'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="card-body">
            <label for="arquivo_csv" class="form-label text-uppercase">Arquivo CSV <span class="text-danger">*</span></label>
            <input type="file" class="form-control text-uppercase" id="arquivo_csv" name="arquivo_csv" accept=".csv" required>
            <div class="invalid-feedback">Selecione um arquivo CSV válido.</div>
        </div>
    </div>
 
    <!-- Configurações Básicas -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-gear me-2"></i>
            <?php echo htmlspecialchars(to_uppercase('Configurações básicas'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="pulo_linhas" class="form-label text-uppercase">Linhas iniciais a pular <span class="text-danger">*</span></label>
                <input type="number" class="form-control text-uppercase" id="pulo_linhas" name="pulo_linhas" value="25" min="0" required>
                <div class="invalid-feedback">Informe quantas linhas devem ser ignoradas.</div>
            </div>
            <div class="mb-3">
                <label for="posicao_data" class="form-label text-uppercase">Célula data <span class="text-danger">*</span></label>
                <input type="text" class="form-control text-uppercase" id="posicao_data" name="posicao_data" value="D13" required>

                <div class="invalid-feedback">Informe a localização da célula da data.</div>
            </div>
        </div>
    </div>
 
    <!-- Mapeamento de Colunas -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-columns-gap me-2"></i>
            <?php echo htmlspecialchars(to_uppercase('Mapeamento de colunas'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="mapeamento_codigo" class="form-label text-uppercase">Código <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center text-uppercase" id="mapeamento_codigo" name="mapeamento_codigo" value="A" maxlength="2" required>
                    <div class="invalid-feedback">Informe a coluna do código.</div>
                </div>
                <div class="col-md-6">
                    <label for="mapeamento_complemento" class="form-label text-uppercase">Complemento <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center text-uppercase" id="mapeamento_complemento" name="mapeamento_complemento" value="D" maxlength="2" required>
                    <div class="invalid-feedback">Informe a coluna do complemento.</div>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="mapeamento_dependencia" class="form-label text-uppercase">Dependência <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center text-uppercase" id="mapeamento_dependencia" name="mapeamento_dependencia" value="P" maxlength="2" required>
                    <div class="invalid-feedback">Informe a coluna da dependência.</div>
                </div>
                <div class="col-md-6">
                    <label for="coluna_localidade" class="form-label text-uppercase">Localidade <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center text-uppercase" id="coluna_localidade" name="coluna_localidade" value="K" maxlength="2" required>
                    <div class="invalid-feedback">Informe a coluna com o código de localidade.</div>
                </div>
            </div>
        </div>
    </div>
 
    <button type="submit" class="btn btn-primary w-100 text-uppercase">
        <i class="bi bi-upload me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Importar Planilha'), ENT_QUOTES, 'UTF-8'); ?>
    </button>
</form>

<script>
    (() => {
        'use strict';
        const jobActive = <?php echo $jobEmExecucao ? 'true' : 'false'; ?>;
        const jobModalEl = document.getElementById('job-active-modal');
        const jobModal = jobModalEl ? new bootstrap.Modal(jobModalEl) : null;
        const forms = document.querySelectorAll('.needs-validation');

        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    const action = form.getAttribute('action');
                    form.setAttribute('action', action + '?action=start');
                }
                form.classList.add('was-validated');
            }, false);
        });

        if (jobActive && jobModal) {
            jobModal.show();
        }
    })();
</script>
 
<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/../../../temp_importar_planilha_content_' . uniqid() . '.php';
file_put_contents($contentFile, $contentHtml);
include __DIR__ . '/../layouts/app_wrapper.php';
@unlink($contentFile);
?>




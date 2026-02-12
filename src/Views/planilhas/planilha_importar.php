<?php
require_once dirname(__DIR__, 2) . '/Helpers/BootstrapLoader.php';

$pageTitle = 'Importar Planilha';
$backUrl = base_url('/');

ob_start();
?>

<form action="/planilhas/importar" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
    <!-- Arquivo CSV -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-file-earmark-arrow-up me-2"></i>
            <?php echo htmlspecialchars(to_uppercase('Arquivo CSV'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="card-body">
            <label for="arquivo_csv" class="form-label text-uppercase">Arquivo CSV <span class="text-danger">*</span></label>
            <input type="file" class="form-control text-uppercase" id="arquivo_csv" name="arquivo_csv" accept=".csv,.txt" required>
            <div class="invalid-feedback">Selecione um arquivo CSV válido.</div>
            <div class="form-text small">
                <i class="bi bi-info-circle me-1"></i>
                O arquivo será analisado e você poderá conferir os dados antes de importar.
            </div>
        </div>
    </div>

    <!-- Informativo do Novo Fluxo -->
    <div class="card mb-3 border-info">
        <div class="card-body py-2">
            <h6 class="card-title mb-2">
                <i class="bi bi-diagram-3 me-2 text-info"></i>
                COMO FUNCIONA
            </h6>
            <div class="d-flex flex-column gap-1 small">
                <div>
                    <span class="badge bg-primary me-1">1</span>
                    <strong>Upload</strong> — Envie o arquivo CSV
                </div>
                <div>
                    <span class="badge bg-info me-1">2</span>
                    <strong>Conferência</strong> — Veja o que será importado, atualizado ou ignorado
                </div>
                <div>
                    <span class="badge bg-warning text-dark me-1">3</span>
                    <strong>Escolha</strong> — Selecione o que importar, pular ou excluir por registro
                </div>
                <div>
                    <span class="badge bg-success me-1">4</span>
                    <strong>Confirme</strong> — Processe apenas o que você selecionou
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 text-uppercase" id="btn-enviar">
        <i class="bi bi-upload me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Enviar e Analisar'), ENT_QUOTES, 'UTF-8'); ?>
    </button>
</form>

<script>
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');

        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    // Desabilita botão e mostra loading
                    const btn = document.getElementById('btn-enviar');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>ANALISANDO...';
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/../../../temp_importar_planilha_content_' . uniqid() . '.php';
file_put_contents($contentFile, $contentHtml);
include __DIR__ . '/../layouts/app.php';
@unlink($contentFile);
?>
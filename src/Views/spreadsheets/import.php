<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'Importar Planilha';
$backUrl = base_url('/spreadsheets/view');

ob_start();
?>

<?= \App\Helpers\AlertHelper::fromQuery() ?>

<form action="/spreadsheets/import" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
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

    <button type="submit" class="btn btn-primary w-100 text-uppercase" id="btn-enviar">
        <i class="bi bi-upload me-2"></i>
        <?php echo htmlspecialchars(to_uppercase('Enviar e Analisar'), ENT_QUOTES, 'UTF-8'); ?>
    </button>
</form>

<script src="/assets/js/spreadsheets/import.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';

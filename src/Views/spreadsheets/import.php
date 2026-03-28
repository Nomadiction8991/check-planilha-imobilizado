<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$pageTitle = 'Importar Planilha';
$backUrl = base_url('/products/view');

ob_start();
?>

<?= \App\Helpers\AlertHelper::fromQuery() ?>

<form action="/spreadsheets/import" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
    <!-- Arquivo CSV -->
    <div class="border border-neutral-200 mb-4" style="border-radius:2px">
        <div class="bg-neutral-50 px-5 py-3 border-b border-neutral-200">
            <i class="bi bi-file-earmark-arrow-up me-2"></i>
            <?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Arquivo CSV'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="p-4">
            <label for="arquivo_csv" class="block text-sm font-semibold mb-2">Arquivo CSV <span style="color:#525252">*</span></label>
            <input type="file" class="w-full px-3 py-2 border border-neutral-300 focus:outline-none focus:border-black" style="border-radius:2px" id="arquivo_csv" name="arquivo_csv" accept=".csv,.txt" required>
            <div class="hidden text-sm mt-2" style="color:#525252">Selecione um arquivo CSV válido.</div>
            <div class="text-sm mt-3" style="color:#808080">
                <i class="bi bi-info-circle me-1"></i>
                O arquivo será analisado e você poderá conferir os dados antes de importar.
            </div>
        </div>
    </div>

    <button type="submit" class="w-full px-4 py-2 bg-black text-white font-medium hover:bg-neutral-900 transition" style="border-radius:2px" id="btn-enviar">
        <i class="bi bi-upload me-2"></i>
        <?php echo htmlspecialchars(\App\Helpers\StringHelper::toUppercase('Enviar e Analisar'), ENT_QUOTES, 'UTF-8'); ?>
    </button>
</form>

<script src="/assets/js/spreadsheets/import.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';

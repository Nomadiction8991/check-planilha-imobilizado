<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

// CSS Tailwind para relatórios
$tailwindReportsCss = '/assets/css/reports/tailwind-reports.css';




ob_start();
?>

<link rel="stylesheet" href="<?php echo $tailwindReportsCss; ?>">

<div class="p-4 md:p-6">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-4">
        <h5 class="text-lg font-bold text-gray-900">Assinatura</h5>
        <div class="flex flex-wrap gap-2">
            <button id="btnFull" class="px-4 py-2 border border-neutral-300 text-sm hover:bg-neutral-50 transition" style="border-radius:2px">Iniciar em paisagem</button>
            <button id="btnSave" class="px-4 py-2 bg-black text-white text-sm hover:bg-neutral-800 transition" style="border-radius:2px">SALVAR</button>
            <button id="btnClear" class="px-4 py-2 border border-neutral-300 text-sm hover:bg-neutral-50 transition" style="border-radius:2px">LIMPAR</button>
            <button id="btnCancel" class="px-4 py-2 border border-black text-sm hover:bg-neutral-100 transition" style="border-radius:2px">CANCELAR</button>
        </div>
    </div>

    <div id="canvasWrapper" class="w-full border border-gray-300 overflow-auto bg-neutral-50 flex items-center justify-center" style="height: calc(100vh - 150px); -webkit-overflow-scrolling: touch; border-radius:2px;">
        <canvas id="sign_canvas" class="bg-white border border-gray-200 block" style="border-radius:2px"></canvas>
    </div>

    <div class="mt-4 text-sm text-gray-600">Após salvar, a tela volta para a página anterior.</div>
</div>

<script src="/assets/js/reports/signature.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>

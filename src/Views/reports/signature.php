<?php
$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';




ob_start();
?>

<div style="padding:12px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
        <h5>Assinatura (modo paisagem)</h5>
        <div>
            <button id="btnFull" class="btn btn-primary btn-sm">Iniciar em paisagem</button>
            <button id="btnSave" class="btn btn-success btn-sm">SALVAR</button>
            <button id="btnClear" class="btn btn-warning btn-sm">LIMPAR</button>
            <button id="btnCancel" class="btn btn-danger btn-sm">CANCELAR</button>
        </div>
    </div>

    <div id="canvasWrapper" style="width:100%; height:calc(100vh - 80px); overflow:auto; -webkit-overflow-scrolling:touch; display:flex; align-items:center; justify-content:center; background:#f8f9fa;">
        <canvas id="sign_canvas" style="background:#fff; border:1px solid #ddd; display:block;"></canvas>
    </div>

    <div class="mt-2 small text-muted">Dica: após salvar você SERÁ levado de volta Á  página anterior.</div>
</div>

<script src="/assets/js/reports/signature.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
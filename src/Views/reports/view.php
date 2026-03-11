<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

// Variáveis recebidas do RelatorioController
$id_planilha = $id_planilha ?? null;
$formulario = $formulario ?? '14.1';
$comum_id = $comum_id ?? $id_planilha;

if (!$id_planilha) {
    echo '<div class="alert alert-warning">Nenhuma planilha selecionada.</div>';
    return;
}

$dados = [];
$templatePath = $projectRoot . "/relatorios/" . basename($formulario) . ".html";
if (!file_exists($templatePath)) {
    echo '<div class="alert alert-danger">Template do formulário ' . htmlspecialchars($formulario, ENT_QUOTES, 'UTF-8') . ' não encontrado</div>';
    return;
}

$templateCompleto = file_get_contents($templatePath);

$start = strpos($templateCompleto, '<!-- A4-START -->');
$end = strpos($templateCompleto, '<!-- A4-END -->');
$a4Block = '';
$styleContent = '';

if ($start !== false && $end !== false) {
    $a4Block = trim(substr($templateCompleto, $start + strlen('<!-- A4-START -->'), $end - ($start + strlen('<!-- A4-START -->'))));

    if (preg_match('/<style>(.*?)<\/style>/s', $templateCompleto, $matches)) {
        $styleContent = $matches[1];
    }
}

$pageTitle = "Relatório {$formulario}";
$backUrl = '/products/view?id=' . urlencode($id_planilha);
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuRelatorio" data-bs-toggle="dropdown">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <button id="btnPrint" class="dropdown-item">
                    <i class="bi bi-printer me-2"></i>Imprimir
                </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="/logout">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';

// CSS customizado carregado via layout (arquivo externo não existe mais)

$reportFiller = new \App\Services\ReportFillerService();

ob_start();
?>

<?php if (!empty($styleContent)): ?>
    <style>
        <?php echo $styleContent; ?>
    </style>
<?php endif; ?>

<div class="paginas-container">
    <?php if (count($produtos) > 0): ?>
        <?php foreach ($produtos as $index => $produto): ?>
            <div class="pagina-card">
                <div class="pagina-header">
                    <span class="pagina-numero">
                        <i class="bi bi-file-earmark-text"></i>
                        Página <?php echo $index + 1; ?> de <?php echo count($produtos); ?>
                    </span>
                </div>

                <div class="a4-viewport">
                    <div class="a4-scaled">
                        <?php
                        $htmlPreenchido = $reportFiller->preencher($formulario, $a4Block, $produto, $planilha);
                        echo $htmlPreenchido;
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Nenhum produto encontrado para o relatório <?php echo htmlspecialchars($formulario); ?>.
        </div>
    <?php endif; ?>
</div>

<script src="/assets/js/reports/view.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
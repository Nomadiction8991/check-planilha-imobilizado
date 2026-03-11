<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';



$templatePath = $projectRoot . '/src/Views/reports/14-1.html';
$templateCompleto = '';
if (file_exists($templatePath)) {
    $templateCompleto = file_get_contents($templatePath);

    $start = strpos($templateCompleto, '<!-- A4-START -->');
    $end   = strpos($templateCompleto, '<!-- A4-END -->');
    if ($start !== false && $end !== false && $end > $start) {
        $a4Block = trim(substr($templateCompleto, $start + strlen('<!-- A4-START -->'), $end - ($start + strlen('<!-- A4-START -->'))));
    } else {
        $a4Block = '';
    }


    preg_match('/<style>(.*?)<\/style>/s', $templateCompleto, $matchesStyle);
    $styleContent = isset($matchesStyle[1]) ? $matchesStyle[1] : '';
} else {
    $a4Block = '';
    $styleContent = '';
}

$pageTitle = 'Relatório 14.1';
$backUrl = '/products/view?comum_id=' . urlencode($comum_id ?? $id_planilha);
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuRelatorio" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuRelatorio">
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


$customCssPath = '/assets/css/reports/report-141.css';

$report141Filler = new \App\Services\Report141FillerService();

$PRODUTOS = $produtos ?? [];

ob_start();
?>

<?php if (count($PRODUTOS) > 0): ?>
    <?php

    $bgCandidates = [
        '/relatorios/relatorio-14-1-bg.png',
        '/relatorios/relatorio-14-1-bg.jpg',
        '/relatorios/relatorio-14-1-bg.jpeg',
        '/relatorios/relatorio-14-1.png',
        '/relatorios/relatorio-14-1.jpg',
        '/relatorios/ralatorio14-1.png',
        '/relatorios/ralatorio14-1.jpg',
    ];
    $bgUrl = '';
    // $projectRoot already defined at the top of this file
    foreach ($bgCandidates as $rel) {
        $abs = $projectRoot . '/' . ltrim($rel, '/');
        if (file_exists($abs)) {
            $bgUrl = $rel;
            break;
        }
    }
    ?>

    <!-- valores-comuns removido conforme solicitado -->
    <?php if (!empty($styleContent)): ?>
        <style>
            <?php echo $styleContent; ?>
        </style>
    <?php endif; ?>

    <!-- Container de páginas -->
    <div class="paginas-container">
        <?php foreach ($PRODUTOS as $index => $row): ?>
            <div class="pagina-card">
                <div class="pagina-header">
                    <span class="pagina-numero">
                        <i class="bi bi-file-earmark-text"></i> Página <?php echo $index + 1; ?> de <?php echo count($PRODUTOS); ?>
                    </span>
                    <div class="pagina-actions">
                        <!-- VISUALIZAR removido conforme solicitado -->
                    </div>
                </div>

                <div class="a4-viewport">
                    <div class="a4-scaled">
                        <?php
                        if (!empty($a4Block)) {
                            $context141 = [
                                'comum_planilha' => $comum_planilha ?? '',
                                'administracao_planilha' => $administracao_planilha ?? '',
                                'cidade_planilha' => $cidade_planilha ?? '',
                                'cnpj_planilha' => $cnpj_planilha ?? '',
                                'numero_relatorio_auto' => $numero_relatorio_auto ?? '',
                                'casa_oracao_auto' => $casa_oracao_auto ?? '',
                                'bgUrl' => $bgUrl ?? '',
                            ];
                            echo $report141Filler->preencher($a4Block, $row, $context141);
                        } else {
                            echo '<div class="r141-root"><div class="a4"><p style="padding:10mm;color:#900">Template 14-1 NÃO encontrado.</p></div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Nenhum PRODUTO encontrado para impressão do relatório 14.1.
    </div>
<?php endif;

echo '<script src="/assets/js/reports/report-141.js"></script>';

?>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
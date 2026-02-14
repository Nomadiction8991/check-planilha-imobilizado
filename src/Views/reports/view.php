<?php

declare(strict_types=1);

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
$backUrl = '/spreadsheets/view?id=' . urlencode($id_planilha);
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

function preencherCampo($html, $id, $valor)
{
    if (empty($valor)) return $html;

    $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');

    $pattern = '/(<textarea[^>]*id=["\']' . preg_quote($id, '/') . '["\'][^>]*>).*?(<\/textarea>)/s';
    $html = preg_replace($pattern, '$1' . $valor . '$2', $html);

    $pattern = '/(<input[^>]*id=["\']' . preg_quote($id, '/') . '["\'][^>]*value=["\'])[^"\']*(["\'])/';
    $html = preg_replace($pattern, '$1' . $valor . '$2', $html);

    return $html;
}

function preencherCheckbox($html, $id, $checked)
{
    if (!$checked) return $html;

    $pattern = '/(<input[^>]*id=["\']' . preg_quote($id, '/') . '["\'][^>]*)(\/?>)/';
    $replacement = '$1 checked$2';
    return preg_replace($pattern, $replacement, $html);
}

function preencherFormulario141($html, $produto, $planilha)
{
    $html = preencherCampo($html, 'input1', date('d/m/Y'));
    $html = preencherCampo($html, 'input2', $planilha['administracao'] ?? '');
    $html = preencherCampo($html, 'input3', $planilha['cidade'] ?? '');
    $html = preencherCampo($html, 'input4', $planilha['setor'] ?? '');
    $html = preencherCampo($html, 'input5', $planilha['cnpj'] ?? '');
    $html = preencherCampo($html, 'input7', $planilha['comum'] ?? '');

    $html = preencherCampo($html, 'input8', $produto['descricao_completa'] ?? '');
    $html = preencherCampo($html, 'input9', $produto['nota_numero'] ?? '');
    $html = preencherCampo($html, 'input10', $produto['nota_data'] ?? '');
    $html = preencherCampo($html, 'input11', $produto['nota_valor'] ?? '');
    $html = preencherCampo($html, 'input12', $produto['nota_fornecedor'] ?? '');

    if (!empty($produto['condicao_14_1'])) {
        $html = preencherCheckbox($html, 'input' . (13 + (int)$produto['condicao_14_1']), true);
    }

    if (!empty($produto['doador_nome'])) {
        $html = preencherCampo($html, 'input17', $produto['doador_nome']);
        $html = preencherCampo($html, 'input19', $produto['doador_endereco'] ?? '');
        $html = preencherCampo($html, 'input21', $produto['doador_cpf'] ?? '');
        $html = preencherCampo($html, 'input23', $produto['doador_rg'] ?? '');

        if ($produto['doador_casado']) {
            $html = preencherCampo($html, 'input18', $produto['doador_nome_conjuge'] ?? '');
            $html = preencherCampo($html, 'input20', $produto['doador_endereco'] ?? '');
            $html = preencherCampo($html, 'input22', $produto['doador_cpf_conjuge'] ?? '');
            $html = preencherCampo($html, 'input24', $produto['doador_rg_conjuge'] ?? '');
        }
    }

    $html = preencherCampo($html, 'input27', $produto['administrador_nome'] ?? '');
    $html = preencherCampo($html, 'input29', $produto['doador_nome'] ?? '');

    return $html;
}

function preencherFormulario142($html, $produto, $planilha)
{
    $html = preencherCampo($html, 'data_emissao', date('d/m/Y'));
    $html = preencherCampo($html, 'administracao', $planilha['administracao'] ?? '');
    $html = preencherCampo($html, 'cidade', $planilha['cidade'] ?? '');
    $html = preencherCampo($html, 'cnpj', $planilha['cnpj'] ?? '');
    $html = preencherCampo($html, 'descricao_bem', $produto['descricao_completa'] ?? '');
    return $html;
}

function preencherFormulario143($html, $produto, $planilha)
{
    return preencherFormulario142($html, $produto, $planilha);
}

function preencherFormulario144($html, $produto, $planilha)
{
    return preencherFormulario142($html, $produto, $planilha);
}

function preencherFormulario145($html, $produto, $planilha)
{
    return preencherFormulario142($html, $produto, $planilha);
}

function preencherFormulario146($html, $produto, $planilha)
{
    return preencherFormulario142($html, $produto, $planilha);
}

function preencherFormulario147($html, $produto, $planilha)
{
    return preencherFormulario142($html, $produto, $planilha);
}

function preencherFormulario148($html, $produto, $planilha)
{
    return preencherFormulario142($html, $produto, $planilha);
}

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
                        $htmlPreenchido = $a4Block;

                        $funcao = 'preencherFormulario' . str_replace('.', '', $formulario);
                        if (function_exists($funcao)) {
                            $htmlPreenchido = $funcao($htmlPreenchido, $produto, $planilha);
                        }

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
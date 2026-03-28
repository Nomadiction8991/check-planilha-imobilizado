<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

// Variáveis recebidas do RelatorioController
$id_planilha = $id_planilha ?? null;
$formulario = $formulario ?? '14.1';
$comum_id = $comum_id ?? $id_planilha;
$planilha = $planilha ?? [];
$produtos = $produtos ?? [];

if (!$id_planilha) {
    echo '<div style="background:#fafafa;border:1px solid #000;color:#171717;border-radius:2px;padding:12px 14px">Nenhuma planilha selecionada.</div>';
    return;
}

$dados = [];
$templatePath = $projectRoot . "/src/Views/reports/" . basename($formulario) . ".html";
if (!file_exists($templatePath)) {
    echo '<div style="background:#fafafa;border:1px solid #000;color:#171717;border-radius:2px;padding:12px 14px">Template do formulário ' . htmlspecialchars($formulario, ENT_QUOTES, 'UTF-8') . ' não encontrado</div>';
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
$backUrl = '/products/view?comum_id=' . urlencode((string) ($comum_id ?? $id_planilha));
$headerActions = '
    <div class="relative inline-block">
        <button class="p-2 hover:bg-gray-100 rounded transition" type="button" id="menuRelatorio" title="Menu">
            <i class="bi bi-list"></i>
        </button>
        <ul class="absolute right-0 mt-1 bg-white border border-gray-200 rounded shadow-lg hidden" id="menuRelatorioDropdown">
            <li>
                <button id="btnPrint" class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center gap-2 text-sm">
                    <i class="bi bi-printer"></i>Imprimir
                </button>
            </li>
            <li><hr class="my-1 border-gray-200"></li>
            <li>
                <form method="POST" action="/logout">
                    <?= \App\Core\CsrfService::hiddenField() ?>
                    <button type="submit" class="block w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center gap-2 text-sm" style="background:none;border:none">
                        <i class="bi bi-box-arrow-right"></i>Sair
                    </button>
                </form>
            </li>
        </ul>
    </div>
';

// CSS customizado carregado via layout (arquivo externo não existe mais)
$tailwindReportsCss = '/assets/css/reports/tailwind-reports.css';

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

<link rel="stylesheet" href="<?php echo $tailwindReportsCss; ?>">

<?php if (!empty($styleContent)): ?>
    <style>
        <?php echo $styleContent; ?>
    </style>
<?php endif; ?>

<div class="report-a4-screen">
    <?php if (count($produtos) > 0): ?>
        <?php foreach ($produtos as $index => $produto): ?>
            <div class="report-a4-card">
                <div class="report-a4-card-header">
                    <span class="report-a4-card-title">
                        <i class="bi bi-file-earmark-text text-black"></i>
                        Página <?php echo $index + 1; ?> de <?php echo count($produtos); ?>
                    </span>
                    <span class="report-a4-card-note">Pré-visualização em proporção A4</span>
                </div>

                <div class="report-a4-preview">
                    <div class="report-a4-stage">
                        <div class="report-a4-sheet">
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
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="m-4 md:m-6 p-4 border border-black text-neutral-900" style="border-radius:2px;background:#fafafa">
            <div class="flex items-start gap-3">
                <i class="bi bi-info-circle text-lg mt-0.5"></i>
                <span>Nenhum produto encontrado para o relatório <?php echo htmlspecialchars($formulario); ?>.</span>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="/assets/js/reports/view.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>

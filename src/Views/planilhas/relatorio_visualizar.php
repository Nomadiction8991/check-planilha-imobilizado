<?php
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once __DIR__ . '/../../controllers/read/RelatorioViewController.php';

$id_planilha = $_GET['id'] ?? null;
$formulario = $_GET['form'] ?? '14.1';

if (!$id_planilha) {
    header('Location: ../../index.php');
    exit;
}

try {
    $controller = new RelatorioViewController($pdo, $id_planilha, $formulario);
    $dados = $controller->obterDados();
    extract($dados);
} catch (Exception $e) {
    die('Erro ao carregar dados: ' . htmlspecialchars($e->getMessage()));
}

$templatePath = __DIR__ . "/../../../relatorios/{$formulario}.html";
if (!file_exists($templatePath)) {
    die("Template do formulário {$formulario} não encontrado");
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
$backUrl = './planilha_visualizar.php?id=' . urlencode($id_planilha);
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

$customCssPath = __DIR__ . '/style/relatorio141.css';
$customCss = file_exists($customCssPath) ? file_get_contents($customCssPath) : '';

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

<style>
    <?php echo $customCss; ?>
</style>

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

<script>
    (function() {
        function mmToPx(mm) {
            const el = document.createElement('div');
            el.style.width = mm + 'mm';
            el.style.position = 'absolute';
            el.style.left = '-9999px';
            document.body.appendChild(el);
            const px = el.getBoundingClientRect().width;
            document.body.removeChild(el);
            return px;
        }

        function fitAll() {
            const a4w = mmToPx(210);
            const a4h = mmToPx(297);

            document.querySelectorAll('.a4-viewport').forEach(vp => {
                const scaled = vp.querySelector('.a4-scaled');
                if (!scaled) return;

                const rect = vp.getBoundingClientRect();
                const style = getComputedStyle(vp);
                const paddingLeft = parseFloat(style.paddingLeft) || 0;
                const paddingRight = parseFloat(style.paddingRight) || 0;
                const available = rect.width - paddingLeft - paddingRight - 8;

                let scale = available / a4w;
                scale = Math.max(0.25, Math.min(1, scale));

                scaled.style.width = a4w + 'px';
                scaled.style.height = a4h + 'px';
                scaled.style.transformOrigin = 'top left';
                scaled.style.transform = 'scale(' + scale + ')';

                const paddingTop = parseFloat(style.paddingTop) || 0;
                const targetH = Math.round(a4h * scale + paddingTop + 4);
                vp.style.height = targetH + 'px';
                vp.style.overflow = 'hidden';
            });
        }

        const debounce = (fn, wait) => {
            let t;
            return function() {
                clearTimeout(t);
                t = setTimeout(fn, wait);
            };
        };

        window.addEventListener('resize', debounce(fitAll, 120));
        window.addEventListener('load', fitAll);
        document.addEventListener('DOMContentLoaded', fitAll);

        document.getElementById('btnPrint')?.addEventListener('click', () => {
            window.print();
        });
    })();
</script>

<?php
$contentHtml = ob_get_clean();
include __DIR__ . '/../layouts/app_wrapper.php';
?>
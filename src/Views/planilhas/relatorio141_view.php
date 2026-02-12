<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

require_once __DIR__ . '/../../../app/controllers/read/Relatorio141DataController.php';


$templatePath = __DIR__ . '/../../../relatorios/14-1.html';
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
$backUrl = './planilha_visualizar.php?id=' . urlencode($id_planilha) . '&comum_id=' . urlencode($id_planilha);
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
                <a class="dropdown-item" href="../../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';


$customCss = '';
$customCssPath = __DIR__ . '/style/relatorio141.css';
if (file_exists($customCssPath)) {
    $customCss .= file_get_contents($customCssPath);
}
$customCss .= "\n.r141-root textarea, .r141-root input{pointer-events:none; -webkit-user-select:none; user-select:none; cursor: default;}";
$customCss .= "\n.r141-root textarea{background:transparent; border:none; outline:none;}";
$customCss .= "\n.r141-root input[disabled]{opacity:1; filter:none;}";



if (!function_exists('r141_safe_strlen')) {
    function r141_safe_strlen(string $text): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($text, 'UTF-8');
        }
        return strlen($text);
    }
}

if (!function_exists('r141_safe_substr')) {
    function r141_safe_substr(string $text, int $start, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($text, $start, $length, 'UTF-8');
        }
        return substr($text, $start, $length);
    }
}

if (!function_exists('r141_dom_available')) {
    function r141_dom_available(): bool
    {
        return class_exists('DOMDocument') && class_exists('DOMXPath');
    }
}

if (!function_exists('r141_safe_html')) {
    function r141_safe_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('r141_fillFieldByIdRegex')) {
    function r141_fillFieldByIdRegex(string $html, string $id, string $text): string
    {
        $safe = r141_safe_html($text);
        $idPattern = preg_quote($id, '/');

        $textareaPattern = "/(<textarea[^>]*\\bid\\s*=\\s*(\"|'){$idPattern}\\2[^>]*>)(.*?)(<\\/textarea>)/is";
        if (preg_match($textareaPattern, $html)) {
            return preg_replace_callback($textareaPattern, function ($m) use ($safe) {
                return $m[1] . $safe . $m[4];
            }, $html, 1);
        }

        $inputPattern = "/(<input[^>]*\\bid\\s*=\\s*(\"|'){$idPattern}\\2[^>]*)(>)/i";
        if (preg_match($inputPattern, $html)) {
            return preg_replace_callback($inputPattern, function ($m) use ($safe) {
                $tag = $m[1];
                if (preg_match('/\\bvalue\\s*=\\s*(\"|\\\')(.*?)\\1/i', $tag)) {
                    $tag = preg_replace('/\\bvalue\\s*=\\s*(\"|\\\')(.*?)\\1/i', 'value="' . $safe . '"', $tag);
                } else {
                    $tag .= ' value="' . $safe . '"';
                }
                return $tag . $m[3];
            }, $html, 1);
        }

        return $html;
    }
}

if (!function_exists('r141_setCheckboxByIdRegex')) {
    function r141_setCheckboxByIdRegex(string $html, string $id, bool $checked): string
    {
        $idPattern = preg_quote($id, '/');
        $inputPattern = "/(<input[^>]*\\bid\\s*=\\s*(\"|'){$idPattern}\\2[^>]*)(>)/i";
        if (!preg_match($inputPattern, $html)) {
            return $html;
        }

        return preg_replace_callback($inputPattern, function ($m) use ($checked) {
            $tag = $m[1];
            $tag = preg_replace('/\\schecked(\\s*=\\s*(\"|\\\')checked\\2)?/i', '', $tag);
            if ($checked) {
                $tag .= ' checked="checked"';
            }
            return $tag . $m[3];
        }, $html, 1);
    }
}

if (!function_exists('r141_insertSignatureImageRegex')) {
    function r141_insertSignatureImageRegex(string $html, string $textareaId, string $base64Image): string
    {
        $safe = r141_safe_html($base64Image);
        $idPattern = preg_quote($textareaId, '/');
        $img = '<img src="' . $safe . '" alt="Assinatura" style="max-width: 100%; height: auto; display: block; max-height: 9mm; margin: 0 auto; object-fit: contain;">';
        $pattern = "/<textarea[^>]*\\bid\\s*=\\s*(\"|'){$idPattern}\\1[^>]*>.*?<\\/textarea>/is";
        return preg_replace($pattern, $img, $html, 1) ?? $html;
    }
}


if (!function_exists('r141_fillFieldById')) {
    function r141_fillFieldById(string $html, string $id, string $text): string
    {
        
        
        
        

        $text = trim((string)$text);
        $maxLen = 10000;
        if (r141_safe_strlen($text) > $maxLen) {
            $text = r141_safe_substr($text, 0, $maxLen);
        }

        if (!r141_dom_available()) {
            return r141_fillFieldByIdRegex($html, $id, $text);
        }

        $prev = function_exists('libxml_use_internal_errors') ? libxml_use_internal_errors(true) : null;
        $doc = new \DOMDocument('1.0', 'UTF-8');
        
        $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $html . '</body></html>';
        
        $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($doc);

        
        $textarea = $xpath->query('//textarea[@id="' . $id . '"]')->item(0);
        if ($textarea) {
            
            while ($textarea->firstChild) {
                $textarea->removeChild($textarea->firstChild);
            }
            $textarea->appendChild($doc->createTextNode($text));
            if (function_exists('libxml_clear_errors')) {
                libxml_clear_errors();
            }
            if ($prev !== null) {
                libxml_use_internal_errors($prev);
            }
            $body = $doc->getElementsByTagName('body')->item(0);
            return $body ? r141_inner_html($body) : $html;
        }

        
        $input = $xpath->query('//input[@id="' . $id . '"]')->item(0);
        if ($input) {
            $input->setAttribute('value', $text);
            if (function_exists('libxml_clear_errors')) {
                libxml_clear_errors();
            }
            if ($prev !== null) {
                libxml_use_internal_errors($prev);
            }
            $body = $doc->getElementsByTagName('body')->item(0);
            return $body ? r141_inner_html($body) : $html;
        }

        
        if (function_exists('libxml_clear_errors')) {
            libxml_clear_errors();
        }
        if ($prev !== null) {
            libxml_use_internal_errors($prev);
        }
        return $html;
    }
}

if (!function_exists('r141_setCheckboxById')) {
    function r141_setCheckboxById(string $html, string $id, bool $checked): string
    {
        if (!r141_dom_available()) {
            return r141_setCheckboxByIdRegex($html, $id, $checked);
        }

        $prev = function_exists('libxml_use_internal_errors') ? libxml_use_internal_errors(true) : null;
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $html . '</body></html>';
        $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($doc);
        $input = $xpath->query('//input[@id="' . $id . '"]')->item(0);
        if ($input) {
            if ($checked) {
                $input->setAttribute('checked', 'checked');
            } else {
                $input->removeAttribute('checked');
            }
        }
        if (function_exists('libxml_clear_errors')) {
            libxml_clear_errors();
        }
        if ($prev !== null) {
            libxml_use_internal_errors($prev);
        }
        $body = $doc->getElementsByTagName('body')->item(0);
        return $body ? r141_inner_html($body) : $html;
    }
}


if (!function_exists('r141_inner_html')) {
    function r141_inner_html(\DOMNode $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }
        return $html;
    }
}


if (!function_exists('r141_insertSignatureImage')) {
    function r141_insertSignatureImage(string $html, string $textareaId, string $base64Image): string
    {
        if (empty($base64Image)) return $html;

        if (!r141_dom_available()) {
            return r141_insertSignatureImageRegex($html, $textareaId, $base64Image);
        }

        $prev = function_exists('libxml_use_internal_errors') ? libxml_use_internal_errors(true) : null;
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $html . '</body></html>';
        $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($doc);

        
        $textarea = $xpath->query('//textarea[@id="' . $textareaId . '"]')->item(0);
        if ($textarea) {
            
            $img = $doc->createElement('img');
            $img->setAttribute('src', $base64Image);
            $img->setAttribute('alt', 'Assinatura');
            
            
            $img->setAttribute('style', 'max-width: 100%; height: auto; display: block; max-height: 9mm; margin: 0 auto; object-fit: contain;');

            
            $textarea->parentNode->replaceChild($img, $textarea);

            if (function_exists('libxml_clear_errors')) {
                libxml_clear_errors();
            }
            if ($prev !== null) {
                libxml_use_internal_errors($prev);
            }
            $body = $doc->getElementsByTagName('body')->item(0);
            return $body ? r141_inner_html($body) : $html;
        }

        if (function_exists('libxml_clear_errors')) {
            libxml_clear_errors();
        }
        if ($prev !== null) {
            libxml_use_internal_errors($prev);
        }
        return $html;
    }
}


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
    $projectRoot = __DIR__ . '/../../../';
    foreach ($bgCandidates as $rel) {
        $abs = $projectRoot . ltrim($rel, '/');
        if (file_exists($abs)) {
            $bgUrl = $rel;
            break;
        }
    }
    ?>

    <!-- valores-comuns removido conforme solicitado -->
    <?php if (!empty($styleContent)): ?>
        <style><?php echo $styleContent; ?></style>
    <?php endif; ?>

    <!-- Container de páginas -->
    <div class="paginas-container">
        <?php foreach ($PRODUTOS as $index => $row): ?>
            <div class="pagina-card">
                <div class="pagina-header">
                    <span class="pagina-numero">
                        <i class="bi bi-file-earmark-text"></i> Pgina <?php echo $index + 1; ?> de <?php echo count($PRODUTOS); ?>
                    </span>
                    <div class="pagina-actions">
                        <!-- VISUALIZAR removido conforme solicitado -->
                    </div>
                </div>

                <div class="a4-viewport">
                    <div class="a4-scaled">
                        <?php
                        
                        $htmlPreenchido = $a4Block;
                        if (!empty($htmlPreenchido)) {
                            
                            $dataEmissao = date('d/m/Y');
                            $descricaoBem = $row['descricao_completa'];

                            
                            $administracao_auto = '';
                            if (!empty($comum_planilha)) {
                                $partesComum = array_map('trim', explode('-', $comum_planilha));
                                if (count($partesComum) >= 1) {
                                    $administracao_auto = $partesComum[0];
                                }
                            }
                            $setor_auto = isset($row['dependencia_descricao']) ? trim((string)$row['dependencia_descricao']) : '';
                            
                            $local_data_auto = trim(($comum_planilha ?? ''));

                            
                            
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input1', $dataEmissao);
                            
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input2', $administracao_planilha ?? '');
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input3', $cidade_planilha ?? '');
                            
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input5', $cnpj_planilha ?? '');
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input6', $numero_relatorio_auto ?? '');
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input7', $casa_oracao_auto ?? '');
                            if (!empty($descricaoBem)) {
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input8', $descricaoBem);
                            }
                            
                            $local_data_with_placeholder = trim(($local_data_auto ?? '') . ' ' . '___/___/_____');
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input16', $local_data_with_placeholder);

                            
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input27', (string)($row['administrador_nome'] ?? ''));

                            
                            $sigAdmin = (string)($row['administrador_assinatura'] ?? '');
                            if (!empty($sigAdmin)) {
                                
                                if (stripos($sigAdmin, 'data:image') !== 0) {
                                    $sigAdmin = 'data:image/png;base64,' . $sigAdmin;
                                }
                                $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input28', $sigAdmin);
                            }

                            
                            
                            $end_doador = trim(implode(' ', array_filter([
                                $row['doador_endereco_logradouro'] ?? '',
                                $row['doador_endereco_numero'] ?? ''
                            ])));
                            $end_doador_comp = trim(implode(' - ', array_filter([
                                $row['doador_endereco_complemento'] ?? '',
                                $row['doador_endereco_bairro'] ?? ''
                            ])));
                            $end_doador_local = trim(implode(' - ', array_filter([
                                trim(($row['doador_endereco_cidade'] ?? '')),
                                trim(($row['doador_endereco_estado'] ?? ''))
                            ])));
                            $end_doador_cep = trim($row['doador_endereco_cep'] ?? '');
                            
                            $partesEnd = [];
                            if ($end_doador) $partesEnd[] = $end_doador; 
                            if ($end_doador_comp) $partesEnd[] = $end_doador_comp; 
                            if ($end_doador_local) $partesEnd[] = $end_doador_local; 
                            $endereco_doador_final = implode(', ', $partesEnd);
                            if ($end_doador_cep) {
                                $endereco_doador_final = rtrim($endereco_doador_final, ', ');
                                $endereco_doador_final .= ($endereco_doador_final ? ' - ' : '') . $end_doador_cep;
                            } else {
                                
                                $endereco_doador_final = rtrim($endereco_doador_final, ' -');
                            }

                            
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input17', (string)($row['doador_nome'] ?? ''));
                            $cpfDoador = (string)($row['doador_cpf'] ?? '');
                            $rgDoadorOriginal = (string)($row['doador_rg'] ?? '');
                            $rgDoador = $rgDoadorOriginal;
                            if (empty($rgDoador) || (!empty($row['doador_rg_igual_cpf']) && $row['doador_rg_igual_cpf'])) {
                                
                                $rgDoador = $cpfDoador;
                            }
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input21', $cpfDoador);
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input23', $rgDoador);
                            
                            if (!empty($endereco_doador_final)) {
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input19', $endereco_doador_final);
                            }
                            
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input29', (string)($row['doador_nome'] ?? ''));

                            
                            $sigDoador = (string)($row['doador_assinatura'] ?? '');
                            if (!empty($sigDoador)) {
                                if (stripos($sigDoador, 'data:image') !== 0) {
                                    $sigDoador = 'data:image/png;base64,' . $sigDoador;
                                }
                                
                                $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input25', $sigDoador);
                                
                                $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input30', $sigDoador);
                            }

                            
                            if (!empty($row['doador_casado']) && $row['doador_casado'] == 1) {
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input18', (string)($row['doador_nome_conjuge'] ?? ''));
                                $cpfConj = (string)($row['doador_cpf_conjuge'] ?? '');
                                $rgConjOriginal = (string)($row['doador_rg_conjuge'] ?? '');
                                $rgConj = $rgConjOriginal;
                                if (empty($rgConj) || (!empty($row['doador_rg_conjuge_igual_cpf']) && $row['doador_rg_conjuge_igual_cpf'])) {
                                    $rgConj = $cpfConj;
                                }
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input22', $cpfConj);
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input24', $rgConj);
                                
                                $end_conj = trim(implode(' ', array_filter([
                                    $row['doador_endereco_logradouro'] ?? '',
                                    $row['doador_endereco_numero'] ?? ''
                                ])));
                                $end_conj_comp = trim(implode(' - ', array_filter([
                                    $row['doador_endereco_complemento'] ?? '',
                                    $row['doador_endereco_bairro'] ?? ''
                                ])));
                                $end_conj_local = trim(implode(' - ', array_filter([
                                    trim(($row['doador_endereco_cidade'] ?? '')),
                                    trim(($row['doador_endereco_estado'] ?? ''))
                                ])));
                                $end_conj_cep = trim($row['doador_endereco_cep'] ?? '');
                                $partesConj = [];
                                if ($end_conj) $partesConj[] = $end_conj;
                                if ($end_conj_comp) $partesConj[] = $end_conj_comp;
                                if ($end_conj_local) $partesConj[] = $end_conj_local;
                                $endereco_conjuge_final = implode(', ', $partesConj);
                                if ($end_conj_cep) {
                                    $endereco_conjuge_final = rtrim($endereco_conjuge_final, ', ');
                                    $endereco_conjuge_final .= ($endereco_conjuge_final ? ' - ' : '') . $end_conj_cep;
                                } else {
                                    
                                    $endereco_conjuge_final = rtrim($endereco_conjuge_final, ' -');
                                }
                                if (!empty($endereco_conjuge_final)) {
                                    $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input20', $endereco_conjuge_final);
                                }

                                
                                $sigConjuge = (string)($row['doador_assinatura_conjuge'] ?? '');
                                if (!empty($sigConjuge)) {
                                    if (stripos($sigConjuge, 'data:image') !== 0) {
                                        $sigConjuge = 'data:image/png;base64,' . $sigConjuge;
                                    }
                                    $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input26', $sigConjuge);
                                }
                            }

                            
                            if (isset($row['condicao_14_1']) && ($row['condicao_14_1'] == 1 || $row['condicao_14_1'] == 3)) {
                                
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input9', (string)($row['nota_numero'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input10', (string)($row['nota_data'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input11', (string)($row['nota_valor'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input12', (string)($row['nota_fornecedor'] ?? ''));
                            }

                            
                            $htmlIsolado = $htmlPreenchido;
                            if (!empty($bgUrl)) {
                                $htmlIsolado = preg_replace('/(<div\s+class="a4"[^>]*>)/', '$1' . '<img class="page-bg" src="' . htmlspecialchars($bgUrl, ENT_QUOTES) . '" alt="">', $htmlIsolado, 1);
                            }
                            
                            $condicao = isset($row['condicao_14_1']) ? (int)$row['condicao_14_1'] : 0;
                            $htmlIsolado = r141_setCheckboxById($htmlIsolado, 'input13', $condicao === 1);
                            $htmlIsolado = r141_setCheckboxById($htmlIsolado, 'input14', $condicao === 2);
                            $htmlIsolado = r141_setCheckboxById($htmlIsolado, 'input15', $condicao === 3);

                            echo $htmlIsolado;
                        } else {
                            echo '<div class="r141-root"><div class="a4"><p style="padding:10mm;color:#900">Template 14-1 NO encontrado.</p></div></div>';
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

$script = <<<JS
<script>
(function(){
    // Calcula px a partir de mm usando elemento temporrio
    function mmToPx(mm){ const el=document.createElement('div'); el.style.position='absolute'; el.style.left='-9999px'; el.style.width=mm+'mm'; document.body.appendChild(el); const px=el.getBoundingClientRect().width; document.body.removeChild(el); return px; }

    function fitAll(){
        const a4w = mmToPx(210);
        const a4h = mmToPx(297);
        document.querySelectorAll('.a4-viewport').forEach(vp=>{
            const scaled = vp.querySelector('.a4-scaled');
            if(!scaled) return;
            const rect = vp.getBoundingClientRect();
            const style = getComputedStyle(vp);
            const paddingLeft = parseFloat(style.paddingLeft) || 0;
            const paddingRight = parseFloat(style.paddingRight) || 0;
            // largura til dentro do viewport (inclui a rea visvel menos paddings)
            const available = rect.width - paddingLeft - paddingRight - 8; // 8px de margem de segurana
            let scale = available / a4w;
            if(!isFinite(scale) || scale <= 0) scale = 0.5;
            // limitar entre 0.25 e 1
            scale = Math.max(0.25, Math.min(1, scale));

            // definir dimenses reais do wrapper scaled para que o transform seja aplicado sobre valores previsveis
            scaled.style.width = a4w + 'px';
            scaled.style.height = a4h + 'px';
            scaled.style.transformOrigin = 'top left';
            scaled.style.transform = 'scale(' + scale + ')';

            // Ajustar a altura do container para o A4 escalado (inclui padding-top)
            const paddingTop = parseFloat(style.paddingTop) || 0;
            const targetH = Math.round(a4h * scale + paddingTop + 4); // +4px folga
            vp.style.height = targetH + 'px';
            // assegurar overflow hidden para NO mostrar fundo alm do A4
            vp.style.overflow = 'hidden';
        });
    }

    const debounce = (fn,wait)=>{ let t; return function(){ clearTimeout(t); t=setTimeout(fn,wait); }; };
    window.addEventListener('resize', debounce(fitAll, 120));
    window.addEventListener('load', fitAll);
    document.addEventListener('DOMContentLoaded', fitAll);

    // Paginao removida - todas as páginas sero exibidas em scroll

    // Funo global de impressão simplificada: apenas chama o print do navegador
    window.validarEImprimir = function(){
        window.print();
    };

})();
</script>
JS;



echo "<script>document.addEventListener('click', function(e){ var btn = e.target && e.target.closest && e.target.closest('#btnPrint'); if(btn){ e.preventDefault(); try{ console && console.log && console.log('print button clicked'); if(typeof window.validarEImprimir==='function'){ window.validarEImprimir(); } else { window.print(); } }catch(err){ console && console.error && console.error('print handler error', err); window.print(); } } });</script>\n";

echo $script;

?>

<?php
$contentHtml = ob_get_clean();
include __DIR__ . '/../layouts/app_wrapper.php';
?>

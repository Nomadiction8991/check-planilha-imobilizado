<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Serviço responsável por preencher o template HTML do relatório 14.1
 * com dados de produto, doador, administrador e assinaturas.
 *
 * Extraído de src/Views/reports/report-141.php para separar lógica de negócio da view.
 */
class Report141FillerService
{
    public function preencher(string $html, array $row, array $context): string
    {
        if (empty($html)) {
            return $html;
        }

        $dataEmissao = date('d/m/Y');
        $descricaoBem = $row['descricao_completa'] ?? '';

        $administracao_auto = '';
        if (!empty($context['comum_planilha'])) {
            $partesComum = array_map('trim', explode('-', $context['comum_planilha']));
            if (count($partesComum) >= 1) {
                $administracao_auto = $partesComum[0];
            }
        }

        $local_data_auto = trim($context['comum_planilha'] ?? '');

        // Campos base do formulário
        $html = $this->fillFieldById($html, 'input1', $dataEmissao);
        $html = $this->fillFieldById($html, 'input2', $context['administracao_planilha'] ?? '');
        $html = $this->fillFieldById($html, 'input3', $context['cidade_planilha'] ?? '');
        $html = $this->fillFieldById($html, 'input5', $context['cnpj_planilha'] ?? '');
        $html = $this->fillFieldById($html, 'input6', $context['numero_relatorio_auto'] ?? '');
        $html = $this->fillFieldById($html, 'input7', $context['casa_oracao_auto'] ?? '');

        if (!empty($descricaoBem)) {
            $html = $this->fillFieldById($html, 'input8', $descricaoBem);
        }

        $local_data_with_placeholder = trim(($local_data_auto) . ' ' . '___/___/_____');
        $html = $this->fillFieldById($html, 'input16', $local_data_with_placeholder);

        // Administrador
        $html = $this->fillFieldById($html, 'input27', (string)($row['administrador_nome'] ?? ''));
        $sigAdmin = (string)($row['administrador_assinatura'] ?? '');
        if (!empty($sigAdmin)) {
            if (stripos($sigAdmin, 'data:image') !== 0) {
                $sigAdmin = 'data:image/png;base64,' . $sigAdmin;
            }
            $html = $this->insertSignatureImage($html, 'input28', $sigAdmin);
        }

        // Endereço do doador
        $endereco_doador_final = $this->montarEndereco($row, 'doador_endereco_');
        $html = $this->fillFieldById($html, 'input17', (string)($row['doador_nome'] ?? ''));

        $cpfDoador = (string)($row['doador_cpf'] ?? '');
        $rgDoador = (string)($row['doador_rg'] ?? '');
        if (empty($rgDoador) || (!empty($row['doador_rg_igual_cpf']) && $row['doador_rg_igual_cpf'])) {
            $rgDoador = $cpfDoador;
        }
        $html = $this->fillFieldById($html, 'input21', $cpfDoador);
        $html = $this->fillFieldById($html, 'input23', $rgDoador);

        if (!empty($endereco_doador_final)) {
            $html = $this->fillFieldById($html, 'input19', $endereco_doador_final);
        }

        $html = $this->fillFieldById($html, 'input29', (string)($row['doador_nome'] ?? ''));

        // Assinatura do doador
        $sigDoador = (string)($row['doador_assinatura'] ?? '');
        if (!empty($sigDoador)) {
            if (stripos($sigDoador, 'data:image') !== 0) {
                $sigDoador = 'data:image/png;base64,' . $sigDoador;
            }
            $html = $this->insertSignatureImage($html, 'input25', $sigDoador);
            $html = $this->insertSignatureImage($html, 'input30', $sigDoador);
        }

        // Cônjuge
        if (!empty($row['doador_casado']) && $row['doador_casado'] == 1) {
            $html = $this->preencherConjuge($html, $row);
        }

        // Nota fiscal (condições 1 ou 3)
        if (isset($row['condicao_14_1']) && ($row['condicao_14_1'] == 1 || $row['condicao_14_1'] == 3)) {
            $html = $this->fillFieldById($html, 'input9', (string)($row['nota_numero'] ?? ''));
            $html = $this->fillFieldById($html, 'input10', (string)($row['nota_data'] ?? ''));
            $html = $this->fillFieldById($html, 'input11', (string)($row['nota_valor'] ?? ''));
            $html = $this->fillFieldById($html, 'input12', (string)($row['nota_fornecedor'] ?? ''));
        }

        // Background e checkboxes de condição
        if (!empty($context['bgUrl'])) {
            $html = preg_replace(
                '/(<div\s+class="a4"[^>]*>)/',
                '$1<img class="page-bg" src="' . htmlspecialchars($context['bgUrl'], ENT_QUOTES) . '" alt="">',
                $html,
                1
            );
        }

        $condicao = isset($row['condicao_14_1']) ? (int)$row['condicao_14_1'] : 0;
        $html = $this->setCheckboxById($html, 'input13', $condicao === 1);
        $html = $this->setCheckboxById($html, 'input14', $condicao === 2);
        $html = $this->setCheckboxById($html, 'input15', $condicao === 3);

        return $html;
    }

    private function preencherConjuge(string $html, array $row): string
    {
        $html = $this->fillFieldById($html, 'input18', (string)($row['doador_nome_conjuge'] ?? ''));

        $cpfConj = (string)($row['doador_cpf_conjuge'] ?? '');
        $rgConj = (string)($row['doador_rg_conjuge'] ?? '');
        if (empty($rgConj) || (!empty($row['doador_rg_conjuge_igual_cpf']) && $row['doador_rg_conjuge_igual_cpf'])) {
            $rgConj = $cpfConj;
        }
        $html = $this->fillFieldById($html, 'input22', $cpfConj);
        $html = $this->fillFieldById($html, 'input24', $rgConj);

        $endereco_conjuge = $this->montarEndereco($row, 'doador_endereco_');
        if (!empty($endereco_conjuge)) {
            $html = $this->fillFieldById($html, 'input20', $endereco_conjuge);
        }

        $sigConjuge = (string)($row['doador_assinatura_conjuge'] ?? '');
        if (!empty($sigConjuge)) {
            if (stripos($sigConjuge, 'data:image') !== 0) {
                $sigConjuge = 'data:image/png;base64,' . $sigConjuge;
            }
            $html = $this->insertSignatureImage($html, 'input26', $sigConjuge);
        }

        return $html;
    }

    private function montarEndereco(array $row, string $prefix): string
    {
        $logradouro = trim(implode(' ', array_filter([
            $row[$prefix . 'logradouro'] ?? '',
            $row[$prefix . 'numero'] ?? ''
        ])));
        $complemento = trim(implode(' - ', array_filter([
            $row[$prefix . 'complemento'] ?? '',
            $row[$prefix . 'bairro'] ?? ''
        ])));
        $local = trim(implode(' - ', array_filter([
            trim((string)($row[$prefix . 'cidade'] ?? '')),
            trim((string)($row[$prefix . 'estado'] ?? ''))
        ])));
        $cep = trim((string)($row[$prefix . 'cep'] ?? ''));

        $partes = [];
        if ($logradouro) $partes[] = $logradouro;
        if ($complemento) $partes[] = $complemento;
        if ($local) $partes[] = $local;
        $endereco = implode(', ', $partes);

        if ($cep) {
            $endereco = rtrim($endereco, ', ');
            $endereco .= ($endereco ? ' - ' : '') . $cep;
        } else {
            $endereco = rtrim($endereco, ' -');
        }

        return $endereco;
    }

    // --- Funções utilitárias de manipulação HTML ---

    private function safeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    private function safeStrlen(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    private function safeSubstr(string $text, int $start, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($text, $start, $length, 'UTF-8') : substr($text, $start, $length);
    }

    private function domAvailable(): bool
    {
        return class_exists('DOMDocument') && class_exists('DOMXPath');
    }

    private function innerHtml(\DOMNode $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }
        return $html;
    }

    public function fillFieldById(string $html, string $id, string $text): string
    {
        $text = trim($text);
        $maxLen = 10000;
        if ($this->safeStrlen($text) > $maxLen) {
            $text = $this->safeSubstr($text, 0, $maxLen);
        }

        if (!$this->domAvailable()) {
            return $this->fillFieldByIdRegex($html, $id, $text);
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
            $this->cleanupLibxml($prev);
            $body = $doc->getElementsByTagName('body')->item(0);
            return $body ? $this->innerHtml($body) : $html;
        }

        $input = $xpath->query('//input[@id="' . $id . '"]')->item(0);
        if ($input) {
            $input->setAttribute('value', $text);
            $this->cleanupLibxml($prev);
            $body = $doc->getElementsByTagName('body')->item(0);
            return $body ? $this->innerHtml($body) : $html;
        }

        $this->cleanupLibxml($prev);
        return $html;
    }

    public function setCheckboxById(string $html, string $id, bool $checked): string
    {
        if (!$this->domAvailable()) {
            return $this->setCheckboxByIdRegex($html, $id, $checked);
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

        $this->cleanupLibxml($prev);
        $body = $doc->getElementsByTagName('body')->item(0);
        return $body ? $this->innerHtml($body) : $html;
    }

    public function insertSignatureImage(string $html, string $textareaId, string $base64Image): string
    {
        if (empty($base64Image)) return $html;

        if (!$this->domAvailable()) {
            return $this->insertSignatureImageRegex($html, $textareaId, $base64Image);
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

            $this->cleanupLibxml($prev);
            $body = $doc->getElementsByTagName('body')->item(0);
            return $body ? $this->innerHtml($body) : $html;
        }

        $this->cleanupLibxml($prev);
        return $html;
    }

    // --- Fallbacks via Regex ---

    private function fillFieldByIdRegex(string $html, string $id, string $text): string
    {
        $safe = $this->safeHtml($text);
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

    private function setCheckboxByIdRegex(string $html, string $id, bool $checked): string
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

    private function insertSignatureImageRegex(string $html, string $textareaId, string $base64Image): string
    {
        $safe = $this->safeHtml($base64Image);
        $idPattern = preg_quote($textareaId, '/');
        $img = '<img src="' . $safe . '" alt="Assinatura" style="max-width: 100%; height: auto; display: block; max-height: 9mm; margin: 0 auto; object-fit: contain;">';
        $pattern = "/<textarea[^>]*\\bid\\s*=\\s*(\"|'){$idPattern}\\1[^>]*>.*?<\\/textarea>/is";
        return preg_replace($pattern, $img, $html, 1) ?? $html;
    }

    private function cleanupLibxml(?bool $prev): void
    {
        if (function_exists('libxml_clear_errors')) {
            libxml_clear_errors();
        }
        if ($prev !== null) {
            libxml_use_internal_errors($prev);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

class ReportFillerService
{
    public function preencherCampo(string $html, string $id, ?string $valor): string
    {
        if (empty($valor)) {
            return $html;
        }

        $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');

        $pattern = '/(<textarea[^>]*id=["\']' . preg_quote($id, '/') . '["\'][^>]*>).*?(<\/textarea>)/s';
        $html = preg_replace($pattern, '$1' . $valor . '$2', $html);

        $pattern = '/(<input[^>]*id=["\']' . preg_quote($id, '/') . '["\'][^>]*value=["\'])[^"\']*(["\'])/';
        $html = preg_replace($pattern, '$1' . $valor . '$2', $html);

        return $html;
    }

    public function preencherCheckbox(string $html, string $id, bool $checked): string
    {
        if (!$checked) {
            return $html;
        }

        $pattern = '/(<input[^>]*id=["\']' . preg_quote($id, '/') . '["\'][^>]*)(\/?>)/';
        $replacement = '$1 checked$2';
        return preg_replace($pattern, $replacement, $html);
    }

    public function preencher(string $formulario, string $html, array $produto, array $planilha): string
    {
        $metodo = 'preencherFormulario' . str_replace('.', '', $formulario);

        if (method_exists($this, $metodo)) {
            return $this->$metodo($html, $produto, $planilha);
        }

        return $html;
    }

    private function preencherFormulario141(string $html, array $produto, array $planilha): string
    {
        $html = $this->preencherCampo($html, 'input1', date('d/m/Y'));
        $html = $this->preencherCampo($html, 'input2', $planilha['administracao'] ?? '');
        $html = $this->preencherCampo($html, 'input3', $planilha['cidade'] ?? '');
        $html = $this->preencherCampo($html, 'input4', $planilha['setor'] ?? '');
        $html = $this->preencherCampo($html, 'input5', $planilha['cnpj'] ?? '');
        $html = $this->preencherCampo($html, 'input7', $planilha['comum'] ?? '');

        $html = $this->preencherCampo($html, 'input8', $produto['descricao_completa'] ?? '');
        $html = $this->preencherCampo($html, 'input9', $produto['nota_numero'] ?? '');
        $html = $this->preencherCampo($html, 'input10', $produto['nota_data'] ?? '');
        $html = $this->preencherCampo($html, 'input11', $produto['nota_valor'] ?? '');
        $html = $this->preencherCampo($html, 'input12', $produto['nota_fornecedor'] ?? '');

        if (!empty($produto['condicao_14_1'])) {
            $html = $this->preencherCheckbox($html, 'input' . (13 + (int)$produto['condicao_14_1']), true);
        }

        if (!empty($produto['doador_nome'])) {
            $html = $this->preencherCampo($html, 'input17', $produto['doador_nome']);
            $html = $this->preencherCampo($html, 'input19', $produto['doador_endereco'] ?? '');
            $html = $this->preencherCampo($html, 'input21', $produto['doador_cpf'] ?? '');
            $html = $this->preencherCampo($html, 'input23', $produto['doador_rg'] ?? '');

            if (!empty($produto['doador_casado'])) {
                $html = $this->preencherCampo($html, 'input18', $produto['doador_nome_conjuge'] ?? '');
                $html = $this->preencherCampo($html, 'input20', $produto['doador_endereco'] ?? '');
                $html = $this->preencherCampo($html, 'input22', $produto['doador_cpf_conjuge'] ?? '');
                $html = $this->preencherCampo($html, 'input24', $produto['doador_rg_conjuge'] ?? '');
            }
        }

        $html = $this->preencherCampo($html, 'input27', $produto['administrador_nome'] ?? '');
        $html = $this->preencherCampo($html, 'input29', $produto['doador_nome'] ?? '');

        return $html;
    }

    private function preencherFormulario142(string $html, array $produto, array $planilha): string
    {
        $html = $this->preencherCampo($html, 'data_emissao', date('d/m/Y'));
        $html = $this->preencherCampo($html, 'administracao', $planilha['administracao'] ?? '');
        $html = $this->preencherCampo($html, 'cidade', $planilha['cidade'] ?? '');
        $html = $this->preencherCampo($html, 'cnpj', $planilha['cnpj'] ?? '');
        $html = $this->preencherCampo($html, 'descricao_bem', $produto['descricao_completa'] ?? '');
        return $html;
    }

    private function preencherFormulario143(string $html, array $produto, array $planilha): string
    {
        return $this->preencherFormulario142($html, $produto, $planilha);
    }

    private function preencherFormulario144(string $html, array $produto, array $planilha): string
    {
        return $this->preencherFormulario142($html, $produto, $planilha);
    }

    private function preencherFormulario145(string $html, array $produto, array $planilha): string
    {
        return $this->preencherFormulario142($html, $produto, $planilha);
    }

    private function preencherFormulario146(string $html, array $produto, array $planilha): string
    {
        return $this->preencherFormulario142($html, $produto, $planilha);
    }

    private function preencherFormulario147(string $html, array $produto, array $planilha): string
    {
        return $this->preencherFormulario142($html, $produto, $planilha);
    }

    private function preencherFormulario148(string $html, array $produto, array $planilha): string
    {
        return $this->preencherFormulario142($html, $produto, $planilha);
    }
}

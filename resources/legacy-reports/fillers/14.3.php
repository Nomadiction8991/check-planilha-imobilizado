<?php

declare(strict_types=1);

// Formulário 14.3 (saída de bens). Só preenche quando recebe os itens de uma
// transferência; a folha em branco do relatório continua sem dados.
return static function (string $html, array $produto, array $planilha): string {
    if (!isset($produto['itens'])) {
        return $html;
    }

    $html = appReportPreencherCampoPorName($html, 'data_emissao', $produto['data'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'administrador_1', $planilha['administracao'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'cidade_1', $planilha['cidade'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'setor_1', $planilha['setor'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'cnpj_da_administracao_1', $planilha['administracao_cnpj'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'n_do_relatorio_1', $planilha['codigo'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'casa_de_oracao_1', $planilha['descricao'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'transferencia_enviada', 'X');
    $html = appReportPreencherCampoPorName($html, 'descricao_do_motivo_1', $produto['motivo'] ?? '');

    foreach (array_values($produto['itens']) as $indice => $item) {
        $numero = $indice + 1;

        if ($numero > 11) {
            break;
        }

        $html = appReportPreencherCampoPorName($html, 'codigo_do_bem_' . $numero, $item['codigo'] ?? '');
        $html = appReportPreencherCampoPorName($html, 'quantidade_' . $numero, $item['quantidade'] ?? '');
        $html = appReportPreencherCampoPorName($html, 'descricao_' . $numero, $item['descricao'] ?? '');
        $html = appReportPreencherCampoPorName($html, 'valor_original_' . $numero, $item['valor'] ?? '');
    }

    return $html;
};

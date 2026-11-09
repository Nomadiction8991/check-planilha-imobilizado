<?php

declare(strict_types=1);

// Formulário 14.2 (entrada de bens). Só preenche quando recebe os itens de uma
// transferência; a folha em branco do relatório continua sem dados.
return static function (string $html, array $produto, array $planilha): string {
    if (!isset($produto['itens'])) {
        return $html;
    }

    $html = appReportPreencherCampoPorName($html, 'data_emissao', $produto['data'] ?? '');

    // Localidade que recebe o bem (parte de cima da folha).
    $html = appReportPreencherCampoPorName($html, 'administracao_1', $planilha['administracao'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'cidade_1', $planilha['cidade'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'setor_1', $planilha['setor'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'cnpj_administracao_1', $planilha['administracao_cnpj'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'n_do_relatorio_1', $planilha['numero_entrada'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'casa_de_horacao', $planilha['descricao'] ?? '');

    // Localidade de origem (parte de baixo da folha).
    $html = appReportPreencherCampoPorName($html, 'administracao_2', $planilha['origem_administracao'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'cidade_2&amp;setor_2', $planilha['origem_cidade_setor'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'n_do_relatorio', $planilha['codigo'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'casa_de_oracao', $planilha['origem_descricao'] ?? '');

    $html = appReportPreencherCampoPorName($html, 'transferencia_recebida', 'X');
    $html = appReportPreencherCampoPorName($html, 'descricao_do_motivo_1', $produto['motivo'] ?? '');

    foreach (array_values($produto['itens']) as $indice => $item) {
        $numero = $indice + 1;

        if ($numero > 12) {
            break;
        }

        $html = appReportPreencherCampoPorName($html, 'tipo_de_bem_' . $numero, $item['tipo'] ?? '');
        $html = appReportPreencherCampoPorName($html, 'quantidade_' . $numero, $item['quantidade'] ?? '');
        $html = appReportPreencherCampoPorName($html, 'descricao_' . $numero, $item['descricao'] ?? '');
        $html = appReportPreencherCampoPorName($html, 'valor_de_aquisicao_' . $numero, $item['valor'] ?? '');
        $html = appReportPreencherCampoPorName($html, 'local_do_bem_' . $numero, $item['local'] ?? '');
    }

    return $html;
};

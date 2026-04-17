<?php

declare(strict_types=1);

return static function (string $html, array $produto, array $planilha): string {
    $dataHoje = date('d/m/Y');
    $descricaoIgreja = $planilha['descricao'] ?? $planilha['comum'] ?? '';
    $condicao = (int)($produto['condicao_14_1'] ?? 0);
    $administracao = trim((string)($planilha['cidade_administracao'] ?? $planilha['administracao'] ?? ''));
    $estadoAdministracao = trim((string)($planilha['estado_administracao'] ?? ''));
    $cidade = trim((string)($planilha['cidade'] ?? ''));
    $estado = trim((string)($planilha['estado'] ?? ''));
    $localDataPartes = array_filter([
        trim((string)$descricaoIgreja),
        trim((string)($planilha['cidade'] ?? '')),
        $estado,
    ], static fn ($valor): bool => $valor !== '');
    $localData = trim(implode(' - ', $localDataPartes));
    if ($localData !== '') {
        $localData .= ' - ___/___/_____';
    }

    if ($administracao !== '' && $estadoAdministracao !== '') {
        $administracao .= ' - ' . $estadoAdministracao;
    }

    if ($cidade !== '' && $estado !== '') {
        $cidade .= ' - ' . $estado;
    }

    $descricaoProduto = trim((string)($produto['descricao_completa'] ?? ''));
    if ($descricaoProduto === '') {
        $descricaoProduto = trim(implode(' ', array_filter([
            $produto['bem'] ?? '',
            $produto['complemento'] ?? '',
        ])));
    }

    $html = appReportPreencherCampoPorName($html, 'data_emissao', $dataHoje);
    $html = appReportPreencherCampoPorName($html, 'administracao', $administracao);
    $html = appReportPreencherCampoPorName($html, 'cidade', $cidade);
    $html = appReportPreencherCampoPorName($html, 'setor', $planilha['setor'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'cnpj-da-administracao', $planilha['cnpj'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'casa_de_oracao', $descricaoIgreja);
    $html = appReportPreencherCampoPorName($html, 'descricao_bem', $descricaoProduto);
    $html = appReportPreencherCampoPorName($html, 'local_e_data', $localData);

    if ($condicao === 1 || $condicao === 3) {
        $html = appReportPreencherCampoPorName($html, 'n_nota_fical', $produto['nota_numero'] ?? '');
        $html = appReportPreencherCampoPorName($html, 'data_de_emissao', appReportFormatarDataRelatorio($produto['nota_data'] ?? ''));
        $html = appReportPreencherCampoPorName($html, 'valor', $produto['nota_valor'] ?? '');
        $html = appReportPreencherCampoPorName($html, 'fornecedor', $produto['nota_fornecedor'] ?? '');
    }

    $html = appReportPreencherCampoPorName($html, 'c1', $condicao === 1 ? 'X' : '');
    $html = appReportPreencherCampoPorName($html, 'c2', $condicao === 2 ? 'X' : '');
    $html = appReportPreencherCampoPorName($html, 'c3', $condicao === 3 ? 'X' : '');
    $html = appReportPreencherCampoPorName($html, 'nome_administrador&acessor_1', $produto['administrador_nome'] ?? '');

    return $html;
};

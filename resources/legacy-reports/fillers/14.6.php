<?php

declare(strict_types=1);

return static function (string $html, array $produto, array $planilha): string {
    $dataHoje = date('d/m/Y');
    $descricaoIgreja = trim((string)($planilha['descricao'] ?? $planilha['comum'] ?? ''));
    $administracao = trim((string)($planilha['administracao_descricao'] ?? $planilha['administracao'] ?? ''));
    $administracaoCnpj = trim((string)($planilha['administracao_cnpj'] ?? ''));
    $usuarioNome = trim((string)($planilha['usuario_nome_relatorio'] ?? $planilha['usuario_nome'] ?? ''));
    $cidade = trim((string)($planilha['cidade'] ?? ''));
    $estado = trim((string)($planilha['estado'] ?? ''));

    $itens = $produto['itens'] ?? [];
    if (!is_array($itens)) {
        $itens = [];
    }

    $responsavel = '';
    if ($usuarioNome !== '') {
        $responsavel = $usuarioNome;
    } elseif ($itens !== []) {
        $responsavel = trim((string)($itens[0]['administrador_nome'] ?? ''));
    }

    $html = appReportPreencherCampoPorName($html, 'data_emissao', $dataHoje);
    $html = appReportPreencherCampoPorName($html, 'administracao', $administracao);
    $html = appReportPreencherCampoPorName($html, 'cidade', $cidade);
    $html = appReportPreencherCampoPorName($html, 'setor', $planilha['setor'] ?? '');
    $html = appReportPreencherCampoPorName($html, 'cnpj_da-administracao', $administracaoCnpj);
    $html = appReportPreencherCampoPorName($html, 'casa_de_oracao', $descricaoIgreja);
    $html = appReportPreencherCampoPorName($html, 'nome_do-responsavel', $responsavel);

    foreach ($itens as $indice => $item) {
        if ($indice >= 13) {
            break;
        }

        $linha = $indice + 1;
        $linhaDe = ($indice * 2) + 1;
        $linhaPara = $linhaDe + 1;

        $descricaoOriginal = trim(implode(' ', array_filter([
            trim((string)($item['bem'] ?? '')),
            trim((string)($item['complemento'] ?? '')),
        ], static fn ($valor): bool => $valor !== '')));

        $descricaoEditada = trim(implode(' ', array_filter([
            trim((string)($item['editado_bem'] ?? '')),
            trim((string)($item['editado_complemento'] ?? '')),
        ], static fn ($valor): bool => $valor !== '')));

        if ($descricaoEditada === '') {
            $descricaoEditada = $descricaoOriginal;
        }

        $tipoOriginal = trim((string)($item['tipo_codigo'] ?? ''));
        $tipoEditado = trim((string)($item['editado_tipo_codigo'] ?? ''));

        if ($tipoEditado === '') {
            $tipoEditado = $tipoOriginal;
        }

        $localizacaoOriginal = trim((string)($item['dependencia_descricao'] ?? ''));
        $localizacaoEditada = trim((string)($item['editado_dependencia_descricao'] ?? ''));

        if ($localizacaoEditada === '') {
            $localizacaoEditada = $localizacaoOriginal;
        }

        $html = appReportPreencherCampoPorName($html, 'codigo_do_bem_' . $linha, $item['codigo'] ?? '');
        $html = appReportPreencherCampoPorName($html, 'descricao_de_' . $linhaDe, $descricaoOriginal);
        $html = appReportPreencherCampoPorName($html, 'descricao_para_' . $linhaPara, $descricaoEditada);
        $html = appReportPreencherCampoPorName($html, 'tipo_bem_de_' . $linhaDe, $tipoOriginal);
        $html = appReportPreencherCampoPorName($html, 'tipo_bem_para_' . $linhaPara, $tipoEditado);
        $html = appReportPreencherCampoPorName($html, 'localizacao_de_' . $linhaDe, $localizacaoOriginal);
        $html = appReportPreencherCampoPorName($html, 'localizacao_para_' . $linhaPara, $localizacaoEditada);
    }

    return $html;
};

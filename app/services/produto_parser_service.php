<?php

/**
 * @deprecated Use ProdutoParserService em src/Services/ProdutoParserService.php
 * @see ProdutoParserService
 */

require_once __DIR__ . '/../../src/Services/ProdutoParserService.php';

// Criar instância global para compatibilidade
$__pp_service = new ProdutoParserService();

/**
 * @deprecated Use ProdutoParserService::normalizar()
 */
function pp_normaliza($str)
{
    global $__pp_service;
    return $__pp_service->normalizar($str);
}

/**
 * @deprecated Use ProdutoParserService::normalizarChar()
 */
function pp_normaliza_char($char)
{
    global $__pp_service;
    return $__pp_service->normalizarChar($char);
}

/**
 * @deprecated Use ProdutoParserService::gerarVariacoes()
 */
function pp_gerar_variacoes($str)
{
    global $__pp_service;
    return $__pp_service->gerarVariacoes($str);
}

/**
 * @deprecated Use ProdutoParserService::matchFuzzy()
 */
function pp_match_fuzzy($str1, $str2)
{
    global $__pp_service;
    return $__pp_service->matchFuzzy($str1, $str2);
}

/**
 * @deprecated Use ProdutoParserService::colunaParaIndice()
 */
function pp_colunaParaIndice($coluna)
{
    global $__pp_service;
    return $__pp_service->colunaParaIndice($coluna);
}

/**
 * @deprecated Use ProdutoParserService::extrairCodigoPrefixo()
 */
function pp_extrair_codigo_prefixo($texto)
{
    global $__pp_service;
    return $__pp_service->extrairCodigoPrefixo($texto);
}

/**
 * @deprecated Use ProdutoParserService::construirAliasesTipos()
 */
function pp_construir_aliases_tipos(array $tipos_bens)
{
    global $__pp_service;
    return $__pp_service->construirAliasesTipos($tipos_bens);
}

/**
 * @deprecated Use ProdutoParserService::detectarTipo()
 */
function pp_detectar_tipo($texto, $codigo_detectado, array $tipos_aliases)
{
    global $__pp_service;
    return $__pp_service->detectarTipo($texto, $codigo_detectado, $tipos_aliases);
}

/**
 * @deprecated Use ProdutoParserService::extrairBenComplemento()
 */
function pp_extrair_ben_complemento($texto, array $tipo_aliases = null, $aliases_originais = null, $tipo_descricao = null)
{
    global $__pp_service;
    return $__pp_service->extrairBenComplemento($texto, $tipo_aliases, $aliases_originais, $tipo_descricao);
}

/**
 * @deprecated Use ProdutoParserService::removerBenDoComplemento()
 */
function pp_remover_ben_do_complemento($ben, $complemento)
{
    global $__pp_service;
    return $__pp_service->removerBenDoComplemento($ben, $complemento);
}

/**
 * @deprecated Use ProdutoParserService::aplicarSinonimos()
 */
function pp_aplicar_sinonimos($ben, $complemento, $tipo_desc, array $config)
{
    global $__pp_service;
    return $__pp_service->aplicarSinonimos($ben, $complemento, $tipo_desc);
}

/**
 * @deprecated Use ProdutoParserService::forcarBenEmAliases()
 */
function pp_forcar_ben_em_aliases($ben, $tipo_desc, $alias_usado = null)
{
    global $__pp_service;
    return $__pp_service->forcarBenEmAliases($ben, $tipo_desc, $alias_usado);
}

/**
 * @deprecated Use ProdutoParserService::montarDescricao()
 */
function pp_montar_descricao($qtd, $tipo_codigo, $tipo_desc, $ben, $comp, $dep, array $config)
{
    global $__pp_service;
    return $__pp_service->montarDescricao($qtd, $tipo_codigo, $tipo_desc, $ben, $comp, $dep);
}

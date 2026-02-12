<?php

/**
 * FACADE DE COMPATIBILIDADE PARA COMUM_HELPER.PHP
 * 
 * Este arquivo mantém as funções procedurais originais funcionando,
 * mas agora elas usam os Repositories por baixo dos panos.
 * 
 * IMPORTANTE: Este é um arquivo de transição. No futuro, todas as chamadas
 * diretas a estas funções devem ser substituídas por uso direto dos Repositories.
 */

use App\Repositories\ComumRepository;

/**
 * Busca comuns com paginação
 * 
 * @deprecated Use ComumRepository::buscarPaginado() diretamente
 */
function buscar_comuns_paginated($conexao, $busca = '', $limite = 10, $offset = 0)
{
    static $repo = null;
    if ($repo === null) {
        $repo = new ComumRepository($conexao);
    }
    
    return $repo->buscarPaginado($busca, $limite, $offset);
}

/**
 * Conta comuns com filtro
 * 
 * @deprecated Use ComumRepository::contarComFiltro() diretamente
 */
function contar_comuns($conexao, $busca = '')
{
    static $repo = null;
    if ($repo === null) {
        $repo = new ComumRepository($conexao);
    }
    
    return $repo->contarComFiltro($busca);
}

/**
 * Normaliza CNPJ
 * 
 * @deprecated Use ComumRepository::normalizarCnpj() diretamente
 */
function normalizar_cnpj_valor($cnpj_raw)
{
    // Implementação direta (não precisa de repository)
    return preg_replace('/\D+/', '', trim((string)$cnpj_raw));
}

/**
 * Gera CNPJ único
 * 
 * @deprecated Use ComumRepository::gerarCnpjUnico() diretamente
 */
function gerar_cnpj_unico($conexao, $cnpj_base, $codigo, $ignorar_id = null)
{
    static $repo = null;
    if ($repo === null) {
        $repo = new ComumRepository($conexao);
    }
    
    return $repo->gerarCnpjUnico($cnpj_base, $codigo, $ignorar_id);
}

/**
 * Garante que existe um comum com código específico
 * 
 * @deprecated Use ComumRepository::garantirPorCodigo() diretamente
 */
function garantir_comum_por_codigo($conexao, $codigo, $dados = [])
{
    static $repo = null;
    if ($repo === null) {
        $repo = new ComumRepository($conexao);
    }
    
    return $repo->garantirPorCodigo($codigo, $dados);
}

/**
 * Extrai código do texto do comum
 * 
 * @deprecated Use ComumRepository::extrairCodigo() diretamente
 */
function extrair_codigo_comum($comum_text)
{
    // Implementação direta (não precisa de conexão)
    $comum_text = trim($comum_text);

    if (preg_match('/BR\s*(\d{2})\D?(\d{4})/i', $comum_text, $matches)) {
        return (int)($matches[1] . $matches[2]);
    }
    if (preg_match('/(\d{2})\D?(\d{4})/', $comum_text, $matches)) {
        return (int)($matches[1] . $matches[2]);
    }

    return 0;
}

/**
 * Extrai descrição do texto do comum
 * 
 * @deprecated Use ComumRepository::extrairDescricao() diretamente
 */
function extrair_descricao_comum($comum_text)
{
    // Implementação direta (não precisa de conexão)
    $comum_text = trim($comum_text);

    if (
        preg_match('/BR\s*\d{2}\D?\d{4}\s*[-\/]?\s*(.+)$/i', $comum_text, $matches) ||
        preg_match('/\d{2}\D?\d{4}\s*[-\/]?\s*(.+)$/', $comum_text, $matches)
    ) {
        $descricao = trim($matches[1]);

        if (strpos($descricao, '-') !== false) {
            $partes = array_map('trim', explode('-', $descricao));
            $descricao = end($partes);
        }

        return $descricao;
    }

    return '';
}

/**
 * Processa/cria comum a partir do texto completo
 * 
 * @deprecated Use ComumRepository::processarComum() diretamente
 */
function processar_comum($conexao, $comum_text, $dados = [])
{
    static $repo = null;
    if ($repo === null) {
        $repo = new ComumRepository($conexao);
    }
    
    return $repo->processarComum($comum_text, $dados);
}

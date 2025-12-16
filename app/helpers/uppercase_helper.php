<?php
/**
 * Helper para converter strings para UPPERCASE
 * Usa a biblioteca voku/portable-utf8 para manipulação avançada de UTF-8
 */

use voku\helper\UTF8;

/**
 * Converte um campo para UPPERCASE com suporte completo a UTF-8 e acentos
 *
 * @param string $value O valor a ser convertido
 * @return string O valor em UPPERCASE
 */
function to_uppercase($value) {
    if (empty($value) || !is_string($value)) {
        return $value;
    }

    // Corrige strings mal codificadas e converte para maiúsculas
    $value = UTF8::fix_utf8($value);
    return UTF8::strtoupper($value);
}

/**
 * Alias para to_uppercase (compatibilidade)
 */
function uppercase($value) {
    return to_uppercase($value);
}

/**
 * Converte múltiplos campos de um array para UPPERCASE
 *
 * @param array $data Array com dados (passado por referência)
 * @param array $fields_to_convert Lista de campos que devem ser convertidos
 * @return array Array com campos convertidos
 */
function uppercase_fields(&$data, $fields_to_convert = []) {
    foreach ($fields_to_convert as $field) {
        if (isset($data[$field]) && is_string($data[$field])) {
            $data[$field] = to_uppercase($data[$field]);
        }
    }
    return $data;
}

/**
 * Normaliza um texto (remove acentos opcionalmente e converte para uppercase)
 *
 * @param string $text Texto a normalizar
 * @param bool $remove_accents Se deve remover acentos
 * @return string Texto normalizado
 */
function normalize_text($text, $remove_accents = false) {
    if (empty($text)) {
        return $text;
    }

    if ($remove_accents) {
        // Remove acentos e converte para uppercase
        $text = UTF8::remove_accents($text);
    }

    return to_uppercase($text);
}

/**
 * Converte para lowercase mantendo suporte UTF-8
 *
 * @param string $value O valor a ser convertido
 * @return string O valor em lowercase
 */
function to_lowercase($value) {
    if (empty($value) || !is_string($value)) {
        return $value;
    }

    return UTF8::strtolower($value);
}

/**
 * Remove acentos de uma string
 *
 * @param string $text Texto com possíveis acentos
 * @return string Texto sem acentos
 */
function remove_accents($text) {
    if (empty($text)) {
        return $text;
    }

    return UTF8::remove_accents($text);
}

/**
 * Campos que devem ser salvos em UPPERCASE no banco de dados
 * Por modelo/tabela
 */
function get_uppercase_fields($table = null) {
    $fields_by_table = [
        'usuarios' => [
            'nome',
            'email',
            'tipo',
            'assinatura',
            'nome_conjuge',
            'assinatura_conjuge',
            'endereco_logradouro',
            'endereco_numero',
            'endereco_complemento',
            'endereco_bairro',
            'endereco_cidade',
            'endereco_estado'
        ],
        'comuns' => [
            'descricao',
            'administracao',
            'cidade'
        ],
        'dependencias' => [
            'descricao'
        ],
        'produtos' => [
            'descricao',
            'tipo',
            'marca',
            'modelo',
            'numero_serie',
            'cor',
            'especificacoes'
        ]
    ];

    if ($table && isset($fields_by_table[$table])) {
        return $fields_by_table[$table];
    }

    // Retornar todos se não especificar tabela
    $all = [];
    foreach ($fields_by_table as $fields) {
        $all = array_merge($all, $fields);
    }
    return $all;
}

// Nota: intencionalmente sem tag de fechamento PHP para evitar saída acidental

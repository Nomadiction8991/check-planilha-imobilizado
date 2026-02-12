<?php

/**
 * @deprecated Use StringHelper em src/Helpers/StringHelper.php
 * @see StringHelper
 */

require_once __DIR__ . '/../../src/Helpers/StringHelper.php';

/**
 * Converte um campo para UPPERCASE com suporte completo a UTF-8 e acentos
 *
 * @deprecated Use StringHelper::toUppercase()
 * @param string $value O valor a ser convertido
 * @return string O valor em UPPERCASE
 */
function to_uppercase($value)
{
    if (empty($value) || !is_string($value)) {
        return $value;
    }
    return StringHelper::toUppercase($value);
}

/**
 * Alias para to_uppercase (compatibilidade)
 *
 * @deprecated Use StringHelper::toUppercase()
 */
function uppercase($value)
{
    return to_uppercase($value);
}

/**
 * Converte múltiplos campos de um array para UPPERCASE
 *
 * @deprecated Use StringHelper::uppercaseFields()
 * @param array $data Array com dados (passado por referência)
 * @param array $fields_to_convert Lista de campos que devem ser convertidos
 * @return array Array com campos convertidos
 */
function uppercase_fields(&$data, $fields_to_convert = [])
{
    return StringHelper::uppercaseFields($data, $fields_to_convert);
}

/**
 * Normaliza um texto (remove acentos opcionalmente e converte para uppercase)
 *
 * @deprecated Use StringHelper::normalize()
 * @param string $text Texto a normalizar
 * @param bool $remove_accents Se deve remover acentos
 * @return string Texto normalizado
 */
function normalize_text($text, $remove_accents = false)
{
    if (empty($text)) {
        return $text;
    }
    return StringHelper::normalize($text, $remove_accents);
}

/**
 * Converte para lowercase mantendo suporte UTF-8
 *
 * @deprecated Use StringHelper::toLowercase()
 * @param string $value O valor a ser convertido
 * @return string O valor em lowercase
 */
function to_lowercase($value)
{
    if (empty($value) || !is_string($value)) {
        return $value;
    }
    return StringHelper::toLowercase($value);
}

/**
 * Remove acentos de uma string
 *
 * @deprecated Use StringHelper::removeAccents()
 * @param string $text Texto com possíveis acentos
 * @return string Texto sem acentos
 */
function remove_accents($text)
{
    return StringHelper::removeAccents($text);
}

/**
 * Campos que devem ser salvos em UPPERCASE no banco de dados
 * Por modelo/tabela
 *
 * @deprecated Use StringHelper::getUppercaseFields()
 */
function get_uppercase_fields($table = null)
{
    return StringHelper::getUppercaseFields($table);
}

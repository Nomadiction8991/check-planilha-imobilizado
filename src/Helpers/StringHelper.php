<?php

declare(strict_types=1);

namespace App\Helpers;

class StringHelper
{
    public static function toUppercase(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        return mb_strtoupper($value, 'UTF-8');
    }

    public static function toLowercase(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        return mb_strtolower($value, 'UTF-8');
    }

    public static function removeAccents(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $result = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($result === false) {
            return preg_replace('/[^\x20-\x7E]/u', '', $text) ?? $text;
        }

        return preg_replace('/[^\x20-\x7E]/u', '', $result) ?? $result;
    }

    public static function normalize(string $text, bool $removeAccents = false): string
    {
        if ($text === '') {
            return $text;
        }

        if ($removeAccents) {
            $text = self::removeAccents($text);
        }

        return self::toUppercase($text);
    }

    public static function normalizeWhitespace(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $text = trim($text);
        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }

    public static function uppercaseFields(array &$data, array $fields): array
    {
        foreach ($fields as $field) {
            if (in_array($field, ['senha', 'password'], true)) {
                continue;
            }

            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = self::toUppercase($data[$field]);
            }
        }

        return $data;
    }

    public static function getUppercaseFields(?string $table = null): array
    {
        $fieldsByTable = [
            'usuarios' => [
                'nome', 'email', 'tipo', 'nome_conjuge',
                'endereco_logradouro', 'endereco_numero', 'endereco_complemento',
                'endereco_bairro', 'endereco_cidade', 'endereco_estado',
            ],
            'comuns' => ['descricao', 'administracao', 'cidade'],
            'dependencias' => ['descricao'],
            'produtos' => [
                'descricao', 'tipo', 'marca', 'modelo',
                'numero_serie', 'cor', 'especificacoes',
            ],
        ];

        if ($table !== null && isset($fieldsByTable[$table])) {
            return $fieldsByTable[$table];
        }

        $all = [];
        foreach ($fieldsByTable as $fields) {
            $all = array_merge($all, $fields);
        }

        return array_values(array_unique($all));
    }

    public static function toTitleCase(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
    }

    public static function isAlpha(string $text): bool
    {
        return preg_match('/^[\p{L}]+$/u', $text) === 1;
    }

    public static function isAlphanumeric(string $text): bool
    {
        return preg_match('/^[\p{L}\p{N}]+$/u', $text) === 1;
    }
}

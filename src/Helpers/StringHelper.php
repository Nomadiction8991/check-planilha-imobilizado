<?php


class StringHelper
{
    
    private static function ensureUtf8Library(): void
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        
        if (!class_exists('voku\\helper\\UTF8')) {
            $code = <<<'PHP'
namespace voku\helper;
class UTF8 {
    public static function fix_utf8($s) { 
        return $s; 
    }
    
    public static function strtoupper($s) { 
        return mb_strtoupper($s, 'UTF-8'); 
    }
    
    public static function strtolower($s) { 
        return mb_strtolower($s, 'UTF-8'); 
    }
    
    public static function to_ascii($s) {
        $out = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($out === false) {
            return preg_replace('/[^\x20-\x7E]/u', '', $s);
        }
        return preg_replace('/[^\x20-\x7E]/u', '', $out);
    }
}
PHP;
            eval($code);
        }

        $loaded = true;
    }

    
    public static function toUppercase(string $value): string
    {
        if (empty($value)) {
            return $value;
        }

        self::ensureUtf8Library();

        
        $value = \voku\helper\UTF8::fix_utf8($value);
        return \voku\helper\UTF8::strtoupper($value);
    }

    
    public static function toLowercase(string $value): string
    {
        if (empty($value)) {
            return $value;
        }

        self::ensureUtf8Library();

        $value = \voku\helper\UTF8::fix_utf8($value);
        return \voku\helper\UTF8::strtolower($value);
    }

    
    public static function removeAccents(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        self::ensureUtf8Library();

        return \voku\helper\UTF8::to_ascii($text);
    }

    
    public static function normalize(string $text, bool $removeAccents = false): string
    {
        if (empty($text)) {
            return $text;
        }

        if ($removeAccents) {
            $text = self::removeAccents($text);
        }

        return self::toUppercase($text);
    }

    
    public static function normalizeWhitespace(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        
        $text = trim($text);
        return preg_replace('/\s+/', ' ', $text);
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
                'nome',
                'email',
                'tipo',
                'nome_conjuge',
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

        if ($table !== null && isset($fieldsByTable[$table])) {
            return $fieldsByTable[$table];
        }

        
        $all = [];
        foreach ($fieldsByTable as $fields) {
            $all = array_merge($all, $fields);
        }

        return array_unique($all);
    }

    
    public static function toTitleCase(string $text): string
    {
        if (empty($text)) {
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

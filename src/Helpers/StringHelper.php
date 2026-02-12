<?php

/**
 * String Helper - Utilidades para manipulação e normalização de strings
 * 
 * Fornece métodos para conversão de case, normalização de texto,
 * remoção de acentos e manipulação de strings UTF-8.
 * 
 * Usa a biblioteca voku/portable-utf8 para manipulação avançada de UTF-8.
 * 
 * @package App\Helpers
 * @version 1.0.0
 */
class StringHelper
{
    /**
     * Carrega biblioteca voku/portable-utf8 com fallback
     * 
     * @return void
     */
    private static function ensureUtf8Library(): void
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        // Tenta carregar autoload do Composer
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        // Cria fallback se biblioteca não estiver disponível
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

    /**
     * Converte string para UPPERCASE com suporte completo a UTF-8
     * 
     * Corrige automaticamente strings mal codificadas antes de converter.
     * 
     * @param string $value String a ser convertida
     * @return string String em UPPERCASE
     */
    public static function toUppercase(string $value): string
    {
        if (empty($value)) {
            return $value;
        }

        self::ensureUtf8Library();

        // Corrige encoding e converte para maiúsculas
        $value = \voku\helper\UTF8::fix_utf8($value);
        return \voku\helper\UTF8::strtoupper($value);
    }

    /**
     * Converte string para lowercase com suporte completo a UTF-8
     * 
     * @param string $value String a ser convertida
     * @return string String em lowercase
     */
    public static function toLowercase(string $value): string
    {
        if (empty($value)) {
            return $value;
        }

        self::ensureUtf8Library();

        $value = \voku\helper\UTF8::fix_utf8($value);
        return \voku\helper\UTF8::strtolower($value);
    }

    /**
     * Remove acentos de uma string
     * 
     * Converte caracteres acentuados para seus equivalentes ASCII.
     * Exemplo: "São Paulo" → "Sao Paulo"
     * 
     * @param string $text Texto com possíveis acentos
     * @return string Texto sem acentos
     */
    public static function removeAccents(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        self::ensureUtf8Library();

        return \voku\helper\UTF8::to_ascii($text);
    }

    /**
     * Normaliza texto (remove acentos opcionalmente e converte para uppercase)
     * 
     * @param string $text Texto a normalizar
     * @param bool $removeAccents Se deve remover acentos
     * @return string Texto normalizado
     */
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

    /**
     * Normaliza espaços em branco
     * 
     * Remove espaços duplicados, tabs e quebras de linha,
     * deixando apenas um espaço entre palavras.
     * 
     * @param string $text Texto com espaços irregulares
     * @return string Texto com espaços normalizados
     */
    public static function normalizeWhitespace(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Remove espaços duplicados, tabs, quebras de linha
        $text = trim($text);
        return preg_replace('/\s+/', ' ', $text);
    }

    /**
     * Converte múltiplos campos de um array para UPPERCASE
     * 
     * Atualiza o array por referência convertendo apenas os campos especificados.
     * 
     * Segurança: Nunca converte campos de senha, mesmo se especificado.
     * 
     * @param array $data Array com dados (passado por referência)
     * @param array $fields Lista de campos que devem ser convertidos
     * @return array Array com campos convertidos (mesmo array por referência)
     */
    public static function uppercaseFields(array &$data, array $fields): array
    {
        foreach ($fields as $field) {
            // Segurança: nunca uppercase campos de senha
            if (in_array($field, ['senha', 'password'], true)) {
                continue;
            }

            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = self::toUppercase($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Retorna lista de campos que devem ser salvos em UPPERCASE por tabela
     * 
     * Define convenção de campos que são armazenados em uppercase no banco.
     * 
     * @param string|null $table Nome da tabela (null = todos os campos)
     * @return array Lista de campos uppercase
     */
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

        // Retorna todos os campos se não especificar tabela
        $all = [];
        foreach ($fieldsByTable as $fields) {
            $all = array_merge($all, $fields);
        }

        return array_unique($all);
    }

    /**
     * Capitaliza primeira letra de cada palavra (Title Case)
     * 
     * @param string $text Texto a capitalizar
     * @return string Texto em Title Case
     */
    public static function toTitleCase(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Trunca string no comprimento especificado
     * 
     * @param string $text Texto a truncar
     * @param int $length Comprimento máximo
     * @param string $suffix Sufixo a adicionar se truncado (padrão: '...')
     * @return string Texto truncado
     */
    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
    }

    /**
     * Verifica se string contém apenas letras (com suporte a UTF-8)
     * 
     * @param string $text Texto a verificar
     * @return bool True se contém apenas letras
     */
    public static function isAlpha(string $text): bool
    {
        return preg_match('/^[\p{L}]+$/u', $text) === 1;
    }

    /**
     * Verifica se string contém apenas letras e números
     * 
     * @param string $text Texto a verificar
     * @return bool True se contém apenas letras e números
     */
    public static function isAlphanumeric(string $text): bool
    {
        return preg_match('/^[\p{L}\p{N}]+$/u', $text) === 1;
    }
}

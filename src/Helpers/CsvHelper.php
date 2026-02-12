<?php

/**
 * CSV Helper - Utilidades para manipulação de arquivos CSV
 * 
 * Classe utilitária para normalização de encoding, remoção de BOM
 * e correção de caracteres corrompidos em arquivos CSV.
 * 
 * @package App\Helpers
 * @version 1.0.0
 */
class CsvHelper
{
    /**
     * Normaliza o encoding de um arquivo CSV para UTF-8 e remove BOM
     * 
     * Detecta automaticamente o encoding do arquivo e converte para UTF-8.
     * Remove BOM (Byte Order Mark) se presente no início do arquivo.
     * 
     * @param string $filePath Caminho absoluto para o arquivo CSV
     * @return void
     * @throws InvalidArgumentException Se o arquivo não existir
     */
    public static function normalizarEncodingCsv(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new InvalidArgumentException("Arquivo CSV não encontrado: {$filePath}");
        }

        $conteudo = file_get_contents($filePath);
        if ($conteudo === false) {
            throw new RuntimeException("Erro ao ler arquivo CSV: {$filePath}");
        }

        // Remove BOM UTF-8 (0xEF 0xBB 0xBF) se presente
        if (strncmp($conteudo, "\xEF\xBB\xBF", 3) === 0) {
            $conteudo = substr($conteudo, 3);
        }

        // Detecta encoding original
        $encodings = ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'Windows-1252'];
        $detectado = mb_detect_encoding($conteudo, $encodings, true) ?: 'UTF-8';

        // Converte para UTF-8 se necessário
        if (strcasecmp($detectado, 'UTF-8') !== 0) {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', $detectado);
        }

        // Salva arquivo normalizado
        $resultado = file_put_contents($filePath, $conteudo, LOCK_EX);
        if ($resultado === false) {
            throw new RuntimeException("Erro ao salvar arquivo CSV normalizado: {$filePath}");
        }
    }

    /**
     * Corrige encoding de texto com caracteres corrompidos
     * 
     * Tenta corrigir textos marcados com caracteres corrompidos
     * (�, Ã, Ã§, etc.) usando diferentes encodings.
     * 
     * Algoritmo:
     * 1. Detecta presença de caracteres corrompidos (�)
     * 2. Tenta recodificar usando Windows-1252 e ISO-8859-1
     * 3. Retorna a versão com menos caracteres corrompidos
     * 
     * @param string|null $valor Texto original (pode conter caracteres corrompidos)
     * @return string|null Texto corrigido (ou original se não foi possível corrigir)
     */
    public static function fixTextEncoding(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return $valor;
        }

        $melhor = $valor;
        $scoreMelhor = substr_count($valor, '�');

        // Se não há caracteres corrompidos, retorna original
        if ($scoreMelhor === 0) {
            return $valor;
        }

        $encodings = ['Windows-1252', 'ISO-8859-1'];

        foreach ($encodings as $encoding) {
            // UTF-8 → encoding intermediário
            $intermediario = @mb_convert_encoding($valor, $encoding, 'UTF-8');
            if ($intermediario === false) {
                continue;
            }

            // encoding intermediário → UTF-8 (re-decode)
            $corrigido = @mb_convert_encoding($intermediario, 'UTF-8', $encoding);
            if ($corrigido === false) {
                continue;
            }

            // Conta caracteres corrompidos
            $score = substr_count($corrigido, '�');

            // Se encontrou versão melhor (menos �), atualiza
            if ($score < $scoreMelhor) {
                $melhor = $corrigido;
                $scoreMelhor = $score;
            }
        }

        return $melhor;
    }

    /**
     * Detecta encoding de uma string
     * 
     * @param string $conteudo String para detectar encoding
     * @return string Encoding detectado (padrão: 'UTF-8')
     */
    public static function detectarEncoding(string $conteudo): string
    {
        $encodings = ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'Windows-1252'];
        return mb_detect_encoding($conteudo, $encodings, true) ?: 'UTF-8';
    }

    /**
     * Verifica se arquivo CSV tem BOM UTF-8
     * 
     * @param string $filePath Caminho do arquivo
     * @return bool True se tem BOM, False caso contrário
     */
    public static function temBomUtf8(string $filePath): bool
    {
        if (!is_file($filePath)) {
            return false;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return false;
        }

        $bytes = fread($handle, 3);
        fclose($handle);

        return $bytes === "\xEF\xBB\xBF";
    }

    /**
     * Valida se arquivo CSV está corretamente encodado em UTF-8
     * 
     * @param string $filePath Caminho do arquivo
     * @return bool True se válido, False se precisa normalização
     */
    public static function isValidUtf8(string $filePath): bool
    {
        if (!is_file($filePath)) {
            return false;
        }

        $conteudo = file_get_contents($filePath);
        if ($conteudo === false) {
            return false;
        }

        // Remove BOM para validação
        if (strncmp($conteudo, "\xEF\xBB\xBF", 3) === 0) {
            $conteudo = substr($conteudo, 3);
        }

        return mb_check_encoding($conteudo, 'UTF-8');
    }
}

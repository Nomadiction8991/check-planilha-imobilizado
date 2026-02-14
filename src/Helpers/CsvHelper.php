<?php

declare(strict_types=1);

namespace App\Helpers;

use InvalidArgumentException;
use RuntimeException;

class CsvHelper
{

    public static function normalizarEncodingCsv(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new InvalidArgumentException("Arquivo CSV não encontrado: {$filePath}");
        }

        $conteudo = file_get_contents($filePath);
        if ($conteudo === false) {
            throw new RuntimeException("Erro ao ler arquivo CSV: {$filePath}");
        }


        if (strncmp($conteudo, "\xEF\xBB\xBF", 3) === 0) {
            $conteudo = substr($conteudo, 3);
        }


        $encodings = ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'Windows-1252'];
        $detectado = mb_detect_encoding($conteudo, $encodings, true) ?: 'UTF-8';


        if (strcasecmp($detectado, 'UTF-8') !== 0) {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', $detectado);
        }


        $resultado = file_put_contents($filePath, $conteudo, LOCK_EX);
        if ($resultado === false) {
            throw new RuntimeException("Erro ao salvar arquivo CSV normalizado: {$filePath}");
        }
    }


    public static function fixTextEncoding(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return $valor;
        }

        $melhor = $valor;
        $scoreMelhor = substr_count($valor, '�');


        if ($scoreMelhor === 0) {
            return $valor;
        }

        $encodings = ['Windows-1252', 'ISO-8859-1'];

        foreach ($encodings as $encoding) {

            $intermediario = @mb_convert_encoding($valor, $encoding, 'UTF-8');
            if ($intermediario === false) {
                continue;
            }


            $corrigido = @mb_convert_encoding($intermediario, 'UTF-8', $encoding);
            if ($corrigido === false) {
                continue;
            }


            $score = substr_count($corrigido, '�');


            if ($score < $scoreMelhor) {
                $melhor = $corrigido;
                $scoreMelhor = $score;
            }
        }

        return $melhor;
    }


    public static function detectarEncoding(string $conteudo): string
    {
        $encodings = ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'Windows-1252'];
        return mb_detect_encoding($conteudo, $encodings, true) ?: 'UTF-8';
    }


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


    public static function isValidUtf8(string $filePath): bool
    {
        if (!is_file($filePath)) {
            return false;
        }

        $conteudo = file_get_contents($filePath);
        if ($conteudo === false) {
            return false;
        }


        if (strncmp($conteudo, "\xEF\xBB\xBF", 3) === 0) {
            $conteudo = substr($conteudo, 3);
        }

        return mb_check_encoding($conteudo, 'UTF-8');
    }
}

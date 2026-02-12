<?php

/**
 * @deprecated Use CsvHelper::normalizarEncodingCsv() em src/Helpers/CsvHelper.php
 * @see CsvHelper::normalizarEncodingCsv()
 */

require_once __DIR__ . '/../../src/Helpers/CsvHelper.php';

/**
 * Normaliza o contedo de um CSV para UTF-8 e remove BOM caso exista.
 *
 * @deprecated Use CsvHelper::normalizarEncodingCsv()
 * @param string $filePath Caminho absoluto para o arquivo CSV
 */
function ip_normalizar_csv_encoding(string $filePath): void
{
    CsvHelper::normalizarEncodingCsv($filePath);
}

/**
 * Tenta corrigir textos que estejam marcados com caracteres corrompidos (, ,  etc.).
 *
 * @deprecated Use CsvHelper::fixTextEncoding()
 * @param string|null $valor Texto original
 * @return string|null Texto depois da tentativa de correo
 */
function ip_fix_text_encoding(?string $valor): ?string
{
    return CsvHelper::fixTextEncoding($valor);
}

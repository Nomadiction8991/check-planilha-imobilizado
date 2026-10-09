<?php

declare(strict_types=1);

namespace App\Support;

use Exception;
use ZipArchive;

final class UniversalSpreadsheetReader
{
    /**
     * Lê qualquer arquivo de planilha (CSV, XLS ou XLSX) e retorna uma matriz de linhas.
     *
     * @return array<int, array<int, string>>
     */
    public static function read(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("Arquivo não encontrado ou ilegível: {$filePath}");
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new Exception("Não foi possível abrir o arquivo: {$filePath}");
        }
        $magic = fread($handle, 8);
        fclose($handle);

        if (str_starts_with($magic, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
            return self::readXls($filePath);
        }

        if (str_starts_with($magic, "PK\x03\x04")) {
            return self::readXlsx($filePath);
        }

        return self::readCsv($filePath);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function readCsv(string $filePath): array
    {
        $content = (string) file_get_contents($filePath);
        if ($content === '') {
            return [];
        }

        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = (string) mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $sample = substr($content, 0, 4096);
        $delimiter = substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';

        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            return [];
        }
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            $rows[] = array_map(static fn($c) => trim((string) $c, " \t\n\r\0\x0B"), $row);
        }
        fclose($stream);

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function readXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return self::readCsv($filePath);
        }

        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $xml = @simplexml_load_string($ssXml);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $t = '';
                        foreach ($si->r as $r) {
                            $t .= (string) $r->t;
                        }
                        $sharedStrings[] = $t;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat && str_starts_with($stat['name'], 'xl/worksheets/sheet') && str_ends_with($stat['name'], '.xml')) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false || !isset($xml->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rIndex = (int) $row['r'] - 1;
            $rowCells = [];
            foreach ($row->c as $cell) {
                $r = (string) $cell['r'];
                preg_match('/^([A-Z]+)/', $r, $m);
                $colLetters = $m[1] ?? 'A';
                $colIdx = self::lettersToColumnIndex($colLetters);

                $val = '';
                $type = (string) $cell['t'];
                if ($type === 's') {
                    $sIndex = (int) $cell->v;
                    $val = $sharedStrings[$sIndex] ?? '';
                } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                    $val = (string) $cell->is->t;
                } elseif (isset($cell->v)) {
                    $val = (string) $cell->v;
                }
                $rowCells[$colIdx] = trim($val, " \t\n\r\0\x0B");
            }

            if (!empty($rowCells)) {
                $maxCol = max(array_keys($rowCells));
                $denseRow = [];
                for ($c = 0; $c <= $maxCol; $c++) {
                    $denseRow[$c] = $rowCells[$c] ?? '';
                }
                $rows[$rIndex] = $denseRow;
            }
        }

        ksort($rows);
        return array_values($rows);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function readXls(string $filePath): array
    {
        $data = (string) file_get_contents($filePath);
        if (strlen($data) < 512) {
            return [];
        }

        $secSize = 1 << unpack('v', substr($data, 30, 2))[1];
        $msat = [];
        for ($i = 0; $i < 109; $i++) {
            $msat[] = unpack('V', substr($data, 76 + $i * 4, 4))[1];
        }

        $fat = [];
        foreach ($msat as $s) {
            if ($s < 0xFFFFFFFE) {
                $offset = $secSize + $s * sec_size;
                for ($j = 0; $j < ($secSize / 4); $j++) {
                    $fat[] = unpack('V', substr($data, $offset + $j * 4, 4))[1];
                }
            }
        }

        $firstDirSec = unpack('V', substr($data, 48, 4))[1];
        $dirData = '';
        $curr = $firstDirSec;
        while ($curr < 0xFFFFFFFE && isset($fat[$curr])) {
            $dirData .= substr($data, $secSize + $curr * $secSize, $secSize);
            $curr = $fat[$curr];
        }

        $wbStartSec = null;
        $wbSize = 0;
        $totalEntries = (int) (strlen($dirData) / 128);
        for ($i = 0; $i < $totalEntries; $i++) {
            $chunk = substr($dirData, $i * 128, 128);
            if (strlen($chunk) < 128) {
                break;
            }
            $nameLen = unpack('v', substr($chunk, 64, 2))[1];
            $nameRaw = substr($chunk, 0, $nameLen);
            $name = (string) @iconv('UTF-16LE', 'UTF-8//IGNORE', $nameRaw);
            $name = trim($name, "\x00");

            if ($name === 'Workbook' || $name === 'Book') {
                $wbStartSec = unpack('V', substr($chunk, 116, 4))[1];
                $wbSize = unpack('V', substr($chunk, 120, 4))[1];
                break;
            }
        }

        if ($wbStartSec === null) {
            return [];
        }

        $wbData = '';
        $curr = $wbStartSec;
        while ($curr < 0xFFFFFFFE && isset($fat[$curr])) {
            $wbData .= substr($data, $secSize + $curr * $secSize, $secSize);
            $curr = $fat[$curr];
        }
        $wbData = substr($wbData, 0, $wbSize);

        $cells = [];
        $maxRow = 0;
        $maxCol = 0;
        $pos = 0;
        $dataLen = strlen($wbData);

        while ($pos < $dataLen - 4) {
            $code = unpack('v', substr($wbData, $pos, 2))[1];
            $length = unpack('v', substr($wbData, $pos + 2, 2))[1];
            $rec = substr($wbData, $pos + 4, $length);

            if ($code === 0x0204 && strlen($rec) >= 8) { // LABEL
                $row = unpack('v', substr($rec, 0, 2))[1];
                $col = unpack('v', substr($rec, 2, 2))[1];
                $strLen = unpack('v', substr($rec, 6, 2))[1];
                $val = '';
                if (strlen($rec) > 8) {
                    $flags = ord($rec[8]);
                    if ($flags & 1) {
                        $rawStr = substr($rec, 9, $strLen * 2);
                        $val = (string) @iconv('UTF-16LE', 'UTF-8//IGNORE', $rawStr);
                    } else {
                        $rawStr = substr($rec, 9, $strLen);
                        $val = (string) @iconv('ISO-8859-1', 'UTF-8//IGNORE', $rawStr);
                    }
                }
                $cells[$row][$col] = trim($val, " \t\n\r\0\x0B");
                $maxRow = max($maxRow, $row);
                $maxCol = max($maxCol, $col);
            } elseif ($code === 0x0203 && strlen($rec) >= 14) { // NUMBER
                $row = unpack('v', substr($rec, 0, 2))[1];
                $col = unpack('v', substr($rec, 2, 2))[1];
                $numVal = unpack('d', substr($rec, 6, 8))[1];

                // Detectar se é data serial do Excel (ex: 41274 = 31/12/2012)
                if ($numVal >= 10000 && $numVal <= 70000 && floor($numVal) == $numVal && $col == 19) {
                    $ts = (int) (($numVal - 25569) * 86400);
                    $val = gmdate('d/m/Y', $ts);
                } else {
                    $val = (string) $numVal;
                }

                $cells[$row][$col] = $val;
                $maxRow = max($maxRow, $row);
                $maxCol = max($maxCol, $col);
            }

            $pos += 4 + $length;
        }

        $rows = [];
        for ($r = 0; $r <= $maxRow; $r++) {
            $denseRow = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $denseRow[$c] = $cells[$r][$c] ?? '';
            }
            $rows[$r] = $denseRow;
        }

        return $rows;
    }

    private static function lettersToColumnIndex(string $letters): int
    {
        $letters = strtoupper(trim($letters));
        $len = strlen($letters);
        $col = 0;
        for ($i = 0; $i < $len; $i++) {
            $col = $col * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return max(0, $col - 1);
    }
}

<?php
// Usage: php analyze_produtos_imprimir.php [path_to_sql]
$path = $argv[1] ?? dirname(__FILE__) . '/../anvycomb_checkplanilha.sql';
if (!file_exists($path)) {
    fwrite(STDERR, "Arquivo não encontrado: $path\n");
    exit(2);
}
$contents = file_get_contents($path);

$pattern = '/INSERT\s+INTO\s+[`\"]?produtos[`\"]?\s*\(([^)]+)\)\s*VALUES\s*(.*?);/is';
if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
    fwrite(STDERR, "Nenhum INSERT INTO produtos encontrado no arquivo.\n");
    exit(1);
}

$summary = [];
$examples = [];

foreach ($matches as $m) {
    $cols_raw = $m[1];
    $vals_raw = $m[2];

    // Parse column names
    $cols = array_map(function ($c) {
        $c = trim($c);
        $c = trim($c, "`\" ");
        return $c;
    }, preg_split('/,\s*/', $cols_raw));

    $idx_imprimir = array_search('imprimir_etiqueta', $cols);
    if ($idx_imprimir === false) {
        // try older name
        $idx_imprimir = array_search('imprimir', $cols);
    }
    $idx_comum = array_search('comum_id', $cols);
    if ($idx_comum === false) $idx_comum = array_search('planilha_id', $cols);
    $idx_id = array_search('id', $cols);
    $idx_codigo = array_search('codigo', $cols);

    if ($idx_imprimir === false) {
        // No imprimir column in this INSERT, skip
        continue;
    }

    // Extract tuples: split on ),( but handle starting/ending parens
    // Normalize: remove starting and ending parentheses if they wrap the entire values string
    $vals = trim($vals_raw);
    // Remove leading and trailing ( ... ) if entire VALUES is a single (...) list
    // We'll split on '),(' by a simple approach
    $tuples = preg_split('/\)\s*,\s*\(/', $vals);

    foreach ($tuples as $t) {
        $t = trim($t);
        $t = preg_replace('/^\(/', '', $t);
        $t = preg_replace('/\)$/', '', $t);
        // Split fields, respecting single quotes and escaped single quotes ('')
        $fields = [];
        $len = strlen($t);
        $buf = '';
        $in_quote = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = $t[$i];
            if ($ch === "'") {
                // check for doubled single-quote escape
                if ($in_quote && $i + 1 < $len && $t[$i + 1] === "'") {
                    $buf .= "''"; // keep escaped
                    $i++; // skip next
                    continue;
                }
                $in_quote = !$in_quote;
                $buf .= $ch;
                continue;
            }
            if ($ch === ',' && !$in_quote) {
                $fields[] = trim($buf);
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        if (strlen($buf) > 0) $fields[] = trim($buf);

        // Get imprimir value
        if (!isset($fields[$idx_imprimir])) continue;
        $imprimir_val = $fields[$idx_imprimir];
        // normalize numeric or quoted
        $imprimir_val = trim($imprimir_val, " \t\n\r\0\x0B'");
        if ($imprimir_val === '1') {
            $comum_val = isset($fields[$idx_comum]) ? trim($fields[$idx_comum], " '\\") : null;
            $comum_val = $comum_val === '' ? null : $comum_val;
            $id_val = isset($fields[$idx_id]) ? trim($fields[$idx_id], " '\\") : null;
            $codigo_val = isset($fields[$idx_codigo]) ? trim($fields[$idx_codigo], " '\\") : null;

            $key = $comum_val ?? '(NULL)';
            if (!isset($summary[$key])) $summary[$key] = 0;
            $summary[$key]++;

            if (!isset($examples[$key])) $examples[$key] = [];
            if (count($examples[$key]) < 5) {
                $examples[$key][] = ['id' => $id_val, 'codigo' => $codigo_val];
            }
        }
    }
}

// Output summary
ksort($summary, SORT_NATURAL);
if (empty($summary)) {
    echo "Nenhum produto com imprimir_etiqueta=1 encontrado no dump.\n";
    exit(0);
}

echo "Resumo de produtos com imprimir_etiqueta=1 por comum_id:\n";
foreach ($summary as $comum => $count) {
    echo sprintf("  %s => %d\n", $comum, $count);
}

echo "\nExemplos (até 5) por comum_id:\n";
foreach ($examples as $comum => $list) {
    echo "  $comum:\n";
    foreach ($list as $ex) {
        echo sprintf("    id=%s, codigo=%s\n", $ex['id'] ?? 'NULL', $ex['codigo'] ?? 'NULL');
    }
}

exit(0);

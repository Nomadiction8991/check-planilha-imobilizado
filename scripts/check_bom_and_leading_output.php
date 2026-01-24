<?php
// script para detectar BOM e saída antes de <?php em arquivos .php
$cwd = realpath(__DIR__ . '/..');
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cwd));
$found = false;
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    $name = $file->getFilename();
    if (!preg_match('/\.php$/i', $name)) continue;
    $path = $file->getPathname();
    $s = file_get_contents($path);
    if ($s === false) continue;
    if (strpos($s, chr(0xEF) . chr(0xBB) . chr(0xBF)) !== false) {
        echo "BOM: $path\n";
        $found = true;
    }
    $pos = strpos($s, '<?php');
    if ($pos === false) {
        // file without opening tag maybe pure HTML, skip
        continue;
    }
    if ($pos > 0) {
        $prefix = substr($s, 0, $pos);
        if (strlen(trim($prefix)) > 0) {
            echo "LEADING OUTPUT: $path (non-empty prefix before <?php)\n";
            // show a little of the prefix
            $snippet = str_replace("\n", "\\n", substr($prefix, 0, 200));
            echo "  => prefix: " . $snippet . "\n";
            $found = true;
        }
    }
}
if (!$found) {
    echo "No BOM or leading output detected in PHP files.\n";
}

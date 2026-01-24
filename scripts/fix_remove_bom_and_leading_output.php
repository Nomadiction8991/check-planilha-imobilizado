<?php
// Remove BOM and any characters before first '<?php' in PHP files, backing up originals with .bak
$cwd = realpath(__DIR__ . '/..');
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cwd));
$modified = [];
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    $name = $file->getFilename();
    if (!preg_match('/\.php$/i', $name)) continue;
    $path = $file->getPathname();
    $s = file_get_contents($path);
    if ($s === false) continue;

    $orig = $s;

    // remove BOM
    if (strpos($s, chr(0xEF) . chr(0xBB) . chr(0xBF)) !== false) {
        $s = preg_replace('/^\x{FEFF}/u', '', $s);
    }

    // if there is a <?php later and there's non-space before it, remove prefix
    $pos = strpos($s, '<?php');
    if ($pos !== false && $pos > 0) {
        $prefix = substr($s, 0, $pos);
        if (strlen(trim($prefix)) > 0 || strpos($prefix, "\n") === 0 || strlen($prefix) > 0) {
            // remove everything before <?php, but preserve if prefix is only whitespace? we'll remove whitespace/newlines too
            $s = substr($s, $pos);
        }
    }

    if ($s !== $orig) {
        // backup
        copy($path, $path . '.bak');
        file_put_contents($path, $s);
        $modified[] = $path;
    }
}
if (empty($modified)) {
    echo "No files modified.\n";
} else {
    echo "Modified files:\n" . implode("\n", $modified) . "\n";
}

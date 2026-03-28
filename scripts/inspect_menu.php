<?php
session_start();
// emulate logged user for header rendering
$_SESSION['usuario_id'] = $_SESSION['usuario_id'] ?? 1;
require __DIR__ . '/../vendor/autoload.php';
ob_start();
\App\Core\ViewRenderer::render('menu');
$out = ob_get_clean();

// try to extract header fragment (including select)
if (preg_match('/<select id="comum-selector"[\s\S]*?<\/select>/i', $out, $m)) {
    echo $m[0];
} elseif (preg_match('/<head>(.*?)<\/head>/is', $out, $m2)) {
    echo $m2[1];
} else {
    echo substr($out, 0, 2000);
}

<?php
// Convert all error messages in UsuarioCreateController to UPPERCASE

$file = 'app/controllers/create/UsuarioCreateController.php';
$content = file_get_contents($file);

// Use preg_replace to uppercase all Exception messages
$content = preg_replace_callback(
    "/throw new Exception\\('([^']+)'\\);/",
    function($matches) {
        $msg = $matches[1];
        $uppercase = mb_strtoupper($msg, 'UTF-8');
        return "throw new Exception('" . $uppercase . "');";
    },
    $content
);

file_put_contents($file, $content);
echo "✅ UsuarioCreateController error messages converted to UPPERCASE\n";
?>

<?php
// Fix usuario_editar.php encoding and UPPERCASE
$file = __DIR__ . '/../app/views/usuarios/usuario_editar.php';
$content = file_get_contents($file);

// Basic replacements to uppercase critical text
$content = str_replace('Autenticação', 'AUTENTICAÇÃO', $content);
$content = str_replace('Visualizar / Editar lógica:', 'VISUALIZAR / EDITAR LÓGICA:', $content);
$content = str_replace('Dados Básicos', 'DADOS BÁSICOS', $content);
$content = str_replace('Cônjuge', 'CÔNJUGE', $content);
$content = str_replace('cônjuge', 'CÔNJUGE', $content);
$content = str_replace('jQuery e InputMask', 'JQUERY E INPUTMASK', $content);
$content = str_replace('SignaturePad', 'SIGNATUREPAD', $content);

file_put_contents($file, $content);
echo "✅ Arquivo corrigido!";
?>

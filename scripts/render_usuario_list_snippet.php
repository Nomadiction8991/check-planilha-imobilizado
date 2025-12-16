<?php
define('SKIP_AUTH', true);
// render snippet for local testing
$_GET['busca'] = '';
require_once __DIR__ . '/../app/controllers/read/UsuarioListController.php';
ob_start();
include __DIR__ . '/../app/views/usuarios/usuarios_listar.php';
$html = ob_get_clean();
// print first 2000 chars of HTML for inspection
echo substr($html, 0, 2000);

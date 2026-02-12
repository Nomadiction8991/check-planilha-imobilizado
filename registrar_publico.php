<?php
define('SKIP_AUTH', true);
require_once __DIR__ . '/config/bootstrap.php';

// Redireciona para o create-usuario.php com parâmetro indicando registro público
header('Location: src/Views/usuarios/usuario_criar.php?public=1');
exit;


<?php

define('SKIP_AUTH', true); // Para rotas públicas como login
require __DIR__ . '/../config/bootstrap.php';

require __DIR__ . '/../vendor/autoload.php';

use App\Routes\MapaRotas;

$rotas = MapaRotas::obter();
$metodo = $_SERVER['REQUEST_METHOD'];
$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$caminho = rtrim($caminho, '/') ?: '/';
$chaveRota = $metodo . ' ' . $caminho;

if (!isset($rotas[$chaveRota])) {
    http_response_code(404);
    echo "Página não encontrada " . htmlspecialchars($caminho);
    exit();
}

[$classeControlador, $acao] = $rotas[$chaveRota];

// Injetar conexão PDO para controllers que precisam
// AuthController não precisa de conexão no construtor
if ($classeControlador === 'App\Controllers\AuthController') {
    $controlador = new $classeControlador();
} else {
    // Para outros controllers, injetar a conexão global
    global $conexao;
    $controlador = new $classeControlador($conexao);
}

$resposta = $controlador->$acao();

if (is_string($resposta)) {
    echo $resposta;
}

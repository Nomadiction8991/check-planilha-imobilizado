<?php

define('SKIP_AUTH', true); // Para rotas públicas como login
require __DIR__ . '/../app/bootstrap.php';

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
$controlador = new $classeControlador();
$resposta = $controlador->$acao();

if (is_string($resposta)) {
    echo $resposta;
}

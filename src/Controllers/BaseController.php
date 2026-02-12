<?php

namespace App\Controllers;

use App\Core\ViewRenderer;

abstract class BaseController
{
    protected function renderizar(string $arquivo, array $dados = []): void
    {
        echo ViewRenderer::renderView($arquivo, $dados);
    }

    protected function redirecionar(string $url, int $status = 302): void
    {
        header("Location: {$url}", true, $status);
        exit;
    }

    protected function json(array $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados);
        exit;
    }

    protected function jsonErro(string $mensagem, int $status = 400): void
    {
        $this->json([
            'erro' => true,
            'mensagem' => $mensagem
        ], $status);
    }

    protected function setMensagem(string $mensagem, string $tipo = 'success'): void
    {
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['tipo_mensagem'] = $tipo;
    }

    protected function input(string $chave, $padrao = null)
    {
        return $_REQUEST[$chave] ?? $padrao;
    }

    protected function query(string $chave, $padrao = null)
    {
        return $_GET[$chave] ?? $padrao;
    }

    protected function post(string $chave, $padrao = null)
    {
        return $_POST[$chave] ?? $padrao;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

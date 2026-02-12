<?php

namespace App\Controllers;

use App\Core\Renderizador;

/**
 * Controller Base
 * Fornece funcionalidades comuns para todos os controllers
 */
abstract class BaseController
{
    /**
     * Renderiza uma view com dados
     */
    protected function renderizar(string $arquivo, array $dados = []): void
    {
        echo Renderizador::renderizar($arquivo, $dados);
    }

    /**
     * Redireciona para outra URL
     */
    protected function redirecionar(string $url, int $status = 302): void
    {
        header("Location: {$url}", true, $status);
        exit;
    }

    /**
     * Retorna JSON
     */
    protected function json(array $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados);
        exit;
    }

    /**
     * Retorna erro JSON
     */
    protected function jsonErro(string $mensagem, int $status = 400): void
    {
        $this->json([
            'erro' => true,
            'mensagem' => $mensagem
        ], $status);
    }

    /**
     * Define mensagem flash na sessão
     */
    protected function setMensagem(string $mensagem, string $tipo = 'success'): void
    {
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['tipo_mensagem'] = $tipo;
    }

    /**
     * Obtém valor da requisição ($_GET ou $_POST)
     */
    protected function input(string $chave, $padrao = null)
    {
        return $_REQUEST[$chave] ?? $padrao;
    }

    /**
     * Obtém valor do GET
     */
    protected function query(string $chave, $padrao = null)
    {
        return $_GET[$chave] ?? $padrao;
    }

    /**
     * Obtém valor do POST
     */
    protected function post(string $chave, $padrao = null)
    {
        return $_POST[$chave] ?? $padrao;
    }

    /**
     * Verifica se é requisição POST
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Verifica se é requisição GET
     */
    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Verifica se é requisição AJAX
     */
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

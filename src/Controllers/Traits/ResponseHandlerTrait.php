<?php

declare(strict_types=1);

namespace App\Controllers\Traits;

use App\Core\ViewRenderer;

/**
 * Trait para manipulação de respostas HTTP
 * Responsabilidade: renderizar views, redirecionar, retornar JSON
 */
trait ResponseHandlerTrait
{
    protected function renderizar(string $arquivo, array $dados = []): void
    {
        echo ViewRenderer::renderView($arquivo, $dados);
    }

    /**
     * Redireciona para URL construída com parâmetros seguros.
     * SEGURANÇA: aceita caminho base + array de parâmetros para evitar XSS
     */
    protected function redirecionar(string $url, int $status = 302): void
    {
        // Validar URL: deve começar com / ou ser protocolo-relativa
        if (!preg_match('#^(/|https?:)#', $url)) {
            $url = '/';
        }

        header("Location: {$url}", true, $status);
        exit;
    }

    /**
     * Constrói URL segura com query parameters escapados para URL
     * SEGURANÇA: previne XSS em query string
     */
    protected function construirUrl(string $caminho, array $parametros = []): string
    {
        // Validar caminho base
        if (!preg_match('#^/#', $caminho)) {
            $caminho = '/' . $caminho;
        }

        if (empty($parametros)) {
            return $caminho;
        }

        // Escapar e construir query string de forma segura
        $query = http_build_query(array_map(function($value) {
            // Se for string, escapar para URL seguramente
            return is_string($value) ? $value : (string) $value;
        }, $parametros));

        return $caminho . ($query ? '?' . $query : '');
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
            'success' => false,
            'message' => $mensagem,
            'erro' => true,
            'mensagem' => $mensagem
        ], $status);
    }

    protected function setMensagem(string $mensagem, string $tipo = 'success'): void
    {
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['tipo_mensagem'] = $tipo;
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\Traits;

/**
 * Trait para manipulação de requisições HTTP
 * Responsabilidade: extrair dados de $_GET, $_POST, $_REQUEST
 */
trait RequestHandlerTrait
{
    protected function input(string $chave, mixed $padrao = null): mixed
    {
        if ($this->isPost()) {
            if (array_key_exists($chave, $_POST)) {
                return $_POST[$chave];
            }

            return $_GET[$chave] ?? $padrao;
        }

        if ($this->isGet()) {
            if (array_key_exists($chave, $_GET)) {
                return $_GET[$chave];
            }

            return $_POST[$chave] ?? $padrao;
        }

        return $_POST[$chave] ?? $_GET[$chave] ?? $padrao;
    }

    protected function query(string $chave, mixed $padrao = null): mixed
    {
        return $_GET[$chave] ?? $padrao;
    }

    protected function post(string $chave, mixed $padrao = null): mixed
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

    /**
     * Obtém parâmetro de ID com fallbacks para nomes alternativos
     */
    protected function getIntParam(string $primaryName, string ...$alternativeNames): int
    {
        $value = $this->input($primaryName);

        if ($value !== null && $value !== '') {
            return (int) $value;
        }

        foreach ($alternativeNames as $altName) {
            $value = $this->input($altName);
            if ($value !== null && $value !== '') {
                return (int) $value;
            }
        }

        return 0;
    }

    /**
     * Obtém parâmetro de string com fallbacks para nomes alternativos
     */
    protected function getStringParam(string $primaryName, string $default = '', string ...$alternativeNames): string
    {
        $value = $this->input($primaryName);

        if ($value !== null && $value !== '') {
            return (string) $value;
        }

        foreach ($alternativeNames as $altName) {
            $value = $this->input($altName);
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return $default;
    }
}

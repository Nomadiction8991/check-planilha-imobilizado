<?php

declare(strict_types=1);

namespace App\Controllers\Traits;

use App\Core\CsrfService;

/**
 * Trait para segurança HTTP
 * Responsabilidade: validação de CSRF, autenticação, autorização
 */
trait SecurityTrait
{
    /**
     * Valida token CSRF de requisição AJAX
     * Token pode vir no header X-CSRF-Token ou no POST
     */
    protected function validateCsrfToken(): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->post('_csrf_token', '');
        return CsrfService::validate($token);
    }

    /**
     * Valida CSRF ou retorna erro JSON
     * Útil para endpoints AJAX que precisam de proteção CSRF
     */
    protected function requireValidCsrfToken(): void
    {
        if (!$this->validateCsrfToken()) {
            http_response_code(403);
            $this->json([
                'erro' => true,
                'mensagem' => 'Token de segurança inválido ou ausente.'
            ], 403);
        }
    }

    /**
     * Obtém token CSRF para usar em requisições AJAX
     */
    protected function getCsrfToken(): string
    {
        return CsrfService::getToken();
    }
}

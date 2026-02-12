<?php

namespace App\Contracts;

/**
 * AuthServiceInterface - Contrato para Serviços de Autenticação
 * 
 * SOLID Principles:
 * - Dependency Inversion: Controllers dependem desta interface, não de implementação concreta
 * - Single Responsibility: Apenas métodos relacionados a autenticação
 * 
 * @package App\Contracts
 */
interface AuthServiceInterface
{
    /**
     * Autentica usuário com email e senha
     * 
     * @param string $email
     * @param string $senha
     * @return array Dados do usuário autenticado
     * @throws \Exception Se autenticação falhar
     */
    public function authenticate(string $email, string $senha): array;

    /**
     * Verifica se usuário está autenticado
     * 
     * @return bool
     */
    public function isAuthenticated(): bool;

    /**
     * Recupera ID do usuário autenticado
     * 
     * @return int|null
     */
    public function getUserId(): ?int;

    /**
     * Realiza logout do usuário
     * 
     * @return void
     */
    public function logout(): void;
}

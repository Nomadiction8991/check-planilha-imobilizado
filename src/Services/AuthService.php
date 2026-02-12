<?php

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\Repositories\UsuarioRepository;
use App\Core\SessionManager;
use Exception;

/**
 * AuthService - Serviço de Autenticação
 * 
 * SOLID Principles:
 * - Single Responsibility: Gerencia APENAS autenticação
 * - Dependency Inversion: Depende de UsuarioRepository (abstração), não de PDO direto
 * - Open/Closed: Extensível via métodos, não modifica lógica core
 * 
 * @package App\Services
 */
class AuthService implements AuthServiceInterface
{
    private UsuarioRepository $usuarioRepository;

    /**
     * Construtor com Dependency Injection
     * 
     * @param UsuarioRepository $usuarioRepository
     */
    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    /**
     * Autentica usuário com email e senha
     * 
     * @param string $email
     * @param string $senha
     * @return array Dados do usuário autenticado
     * @throws Exception Se autenticação falhar
     */
    public function authenticate(string $email, string $senha): array
    {
        // Buscar usuário por email (normalizado em uppercase)
        $usuario = $this->usuarioRepository->buscarPorEmail($email);

        if (!$usuario) {
            throw new Exception('E-mail ou senha inválidos.');
        }

        // Verifica se usuário está ativo
        if ((int)($usuario['ativo'] ?? 0) !== 1) {
            throw new Exception('Usuário inativo. Entre em contato com o administrador.');
        }

        // Verifica senha
        if (!password_verify($senha, $usuario['senha'])) {
            throw new Exception('E-mail ou senha inválidos.');
        }

        // Login bem-sucedido - armazena dados na sessão
        SessionManager::setUser(
            (int)$usuario['id'],
            $usuario['nome'],
            $usuario['email']
        );

        // Regenera ID da sessão (segurança contra session fixation)
        SessionManager::regenerate();

        return $usuario;
    }

    /**
     * Verifica se usuário está autenticado
     * 
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return SessionManager::isAuthenticated();
    }

    /**
     * Recupera ID do usuário autenticado
     * 
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return SessionManager::getUserId();
    }

    /**
     * Realiza logout do usuário
     * 
     * @return void
     */
    public function logout(): void
    {
        SessionManager::clearUser();
    }
}

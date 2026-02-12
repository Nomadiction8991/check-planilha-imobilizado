<?php

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\Repositories\UsuarioRepository;
use App\Core\SessionManager;
use Exception;

class AuthService implements AuthServiceInterface
{
    private UsuarioRepository $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    public function authenticate(string $email, string $senha): array
    {
        $usuario = $this->usuarioRepository->buscarPorEmail($email);

        if (!$usuario) {
            throw new Exception('E-mail ou senha inválidos.');
        }

        if ((int)($usuario['ativo'] ?? 0) !== 1) {
            throw new Exception('Usuário inativo. Entre em contato com o administrador.');
        }

        if (!password_verify($senha, $usuario['senha'])) {
            throw new Exception('E-mail ou senha inválidos.');
        }

        SessionManager::setUser(
            (int)$usuario['id'],
            $usuario['nome'],
            $usuario['email']
        );

        SessionManager::regenerate();

        return $usuario;
    }

    public function isAuthenticated(): bool
    {
        return SessionManager::isAuthenticated();
    }

    public function getUserId(): ?int
    {
        return SessionManager::getUserId();
    }

    public function logout(): void
    {
        SessionManager::clearUser();
    }
}

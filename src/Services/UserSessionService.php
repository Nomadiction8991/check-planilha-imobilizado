<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\UsuarioRepository;
use App\Repositories\ComumRepository;

class UserSessionService
{
    private UsuarioRepository $usuarioRepository;
    private ComumRepository $comumRepository;

    public function __construct(UsuarioRepository $usuarioRepository, ComumRepository $comumRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
        $this->comumRepository = $comumRepository;
    }

    /**
     * Garante que o usuário logado tenha uma comum_id definida na sessão.
     * Se não houver, busca do banco e persiste.
     */
    public function ensureComumId(): ?int
    {
        $comumId = SessionManager::getComumId();
        if ($comumId !== null && $comumId > 0) {
            return $comumId;
        }

        if (!SessionManager::isAuthenticated()) {
            return null;
        }

        try {
            $userId = SessionManager::getUserId();
            if ($userId === null) {
                return null;
            }

            $usuario = $this->usuarioRepository->buscarPorId($userId);
            $comumId = (int) ($usuario['comum_id'] ?? 0);

            if ($comumId <= 0) {
                $comuns = $this->comumRepository->buscarTodos();
                if (!empty($comuns)) {
                    $comumId = (int) $comuns[0]['id'];
                    $this->usuarioRepository->atualizar($userId, ['comum_id' => $comumId]);
                } else {
                    return null;
                }
            }

            SessionManager::setComumId($comumId);
            return $comumId;
        } catch (\Exception $e) {
            error_log('Erro ao garantir comum_id: ' . $e->getMessage());
            return null;
        }
    }
}

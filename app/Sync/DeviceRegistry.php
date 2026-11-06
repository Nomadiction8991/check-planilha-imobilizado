<?php

declare(strict_types=1);

namespace App\Sync;

/**
 * Cadastro dos notebooks que sincronizam: cria o código de acesso, confere e revoga.
 *
 * O código é mostrado uma única vez. No banco fica só o hash SHA-256 dele.
 */
final class DeviceRegistry
{
    /**
     * @return array{aparelho: Device, token: string}
     */
    public function registrar(string $nome, ?int $usuarioId): array
    {
        $token = $this->novoToken();

        $aparelho = Device::query()->create([
            'nome' => $nome,
            'token_hash' => self::hashToken($token),
            'usuario_id' => $usuarioId,
        ]);

        return ['aparelho' => $aparelho, 'token' => $token];
    }

    /**
     * Devolve o aparelho do código informado, ou null se o código não existe ou foi revogado.
     */
    public function autenticar(?string $token): ?Device
    {
        if ($token === null || $token === '') {
            return null;
        }

        $aparelho = Device::query()->where('token_hash', self::hashToken($token))->first();

        if ($aparelho === null || $aparelho->estaRevogado()) {
            return null;
        }

        $aparelho->forceFill(['ultimo_contato_em' => now()])->save();

        return $aparelho;
    }

    public function revogar(Device $aparelho): void
    {
        $aparelho->forceFill(['revogado_em' => now()])->save();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function novoToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}

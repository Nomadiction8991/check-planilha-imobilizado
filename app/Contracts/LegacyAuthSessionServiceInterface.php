<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

interface LegacyAuthSessionServiceInterface
{
    /**
     * @return array{id: int, nome: string, email: string, comum_id: int|null, administracao_id: int|null, administracoes_permitidas: array<int, int>, is_admin: bool, legacy_permissions: array<string, bool>}
     */
    public function attempt(string $email, string $password): array;

    public function logout(): void;

    public function isAuthenticated(): bool;

    /**
     * @return array{id: int, nome: string, email: string, comum_id: int|null, administracao_id: int|null, administracoes_permitidas: array<int, int>, is_admin: bool}|null
     */
    public function currentUser(): ?array;

    public function currentChurchId(): ?int;

    public function switchChurch(int $churchId): void;

    /**
     * @return array{id: int, codigo: string, descricao: string}|null
     */
    public function currentChurch(): ?array;

    /**
     * @return Collection<int, object{id:int,codigo:string,descricao:string}>
     */
    public function availableChurches(): Collection;
}

<?php

declare(strict_types=1);

namespace App\Contracts;


interface AuthServiceInterface
{
    
    public function authenticate(string $email, string $senha): array;

    
    public function isAuthenticated(): bool;

    
    public function getUserId(): ?int;

    
    public function logout(): void;
}

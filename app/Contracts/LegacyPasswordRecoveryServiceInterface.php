<?php

declare(strict_types=1);

namespace App\Contracts;

interface LegacyPasswordRecoveryServiceInterface
{
    public function recover(string $cpf, string $phone, string $email): void;
}

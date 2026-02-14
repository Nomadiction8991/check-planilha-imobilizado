<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class AuthenticationException extends RuntimeException
{
    public function __construct(string $message = 'Credenciais inválidas.', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}

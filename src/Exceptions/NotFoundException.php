<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class NotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Registro não encontrado.', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}

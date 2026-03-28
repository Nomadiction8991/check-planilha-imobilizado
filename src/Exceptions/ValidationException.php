<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /** @var array<string, string> */
    private array $errors;

    /**
     * @param string $message
     * @param array<string, string> $errors
     */
    public function __construct(string $message = 'Dados inválidos.', array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    /** @return array<string, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class LegacyBrandingData
{
    public function __construct(public string $nome, public ?string $conteudo = null, public ?string $mime = null)
    {
    }
}

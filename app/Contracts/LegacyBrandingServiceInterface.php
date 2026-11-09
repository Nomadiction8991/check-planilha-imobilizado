<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\LegacyBrandingData;

interface LegacyBrandingServiceInterface
{
    public function nome(): string;

    /** @return array{conteudo: string, mime: string} */
    public function logo(): array;

    public function versao(): string;

    public function salvar(LegacyBrandingData $data): void;
}

<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\LegacyMailConfigurationData;

interface LegacyMailConfigurationServiceInterface
{
    public function current(): LegacyMailConfigurationData;

    public function save(LegacyMailConfigurationData $data): void;

    public function configureRuntimeMailer(): void;
}

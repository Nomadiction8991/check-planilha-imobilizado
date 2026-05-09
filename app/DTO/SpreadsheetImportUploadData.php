<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class SpreadsheetImportUploadData
{
    public function __construct(
        public int $responsibleUserId,
        public ?int $churchId,
        public int $administrationId,
    ) {
    }
}

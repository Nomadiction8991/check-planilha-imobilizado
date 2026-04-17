<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class UserMutationData
{
    public function __construct(
        public ?int $administrationId,
        /**
         * @var array<int, int>
         */
        public array $administrationIds,
        public string $name,
        public string $email,
        public bool $active,
        public string $cpf,
        public string $rg,
        public bool $rgEqualsCpf,
        public string $phone,
        public bool $married,
        public string $spouseName,
        public string $spouseCpf,
        public string $spouseRg,
        public bool $spouseRgEqualsCpf,
        public string $spousePhone,
        public string $addressZip,
        public string $addressStreet,
        public string $addressNumber,
        public string $addressComplement,
        public string $addressDistrict,
        public string $addressCity,
        public string $addressState,
        /**
         * @var array<int, string>
         */
        public array $permissions,
        public bool $permissionsProvided,
        public ?string $password,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class LegacyNavigationOrderData
{
    /**
     * @param array<int, string> $items
     */
    public function __construct(
        public array $items,
    ) {
    }

    /**
     * @param array{items: array<int, string>} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            items: $data['items'],
        );
    }

    /**
     * @return array<int, string>
     */
    public function toArray(): array
    {
        return $this->items;
    }
}

<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class AdministrationFilters
{
    public function __construct(
        public string $search,
        public int $page,
        public int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: trim((string) $request->query('busca', '')),
            page: max(1, (int) $request->query('pagina', 1)),
            perPage: 20,
        );
    }

    /**
     * @return array<string, scalar>
     */
    public function toQuery(): array
    {
        if ($this->search === '') {
            return [];
        }

        return ['busca' => $this->search];
    }
}

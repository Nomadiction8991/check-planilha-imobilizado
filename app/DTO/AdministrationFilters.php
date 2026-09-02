<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class AdministrationFilters
{
    public function __construct(
        public string $search,
        public ?string $state,
        public int $page,
        public int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $rawState = trim((string) $request->query('estado', ''));
        $state = $rawState !== '' ? mb_strtoupper(mb_substr($rawState, 0, 2, 'UTF-8'), 'UTF-8') : null;

        return new self(
            search: trim((string) $request->query('busca', '')),
            state: $state,
            page: max(1, (int) $request->query('pagina', 1)),
            perPage: 20,
        );
    }

    /**
     * @return array<string, scalar>
     */
    public function toQuery(): array
    {
        $query = [];

        if ($this->search !== '') {
            $query['busca'] = $this->search;
        }

        if ($this->state !== null && $this->state !== '') {
            $query['estado'] = $this->state;
        }

        return $query;
    }
}

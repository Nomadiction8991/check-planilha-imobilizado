<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class AssetTypeFilters
{
    public function __construct(
        public ?int $administrationId,
        public string $search,
        public ?string $state,
        public int $page,
        public int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $administrationId = (int) $request->query('administracao_id', 0);
        $rawState = trim((string) $request->query('estado', ''));
        $state = $rawState !== '' ? mb_strtoupper(mb_substr($rawState, 0, 2, 'UTF-8'), 'UTF-8') : null;

        return new self(
            administrationId: $administrationId > 0 ? $administrationId : null,
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

        if ($this->administrationId !== null) {
            $query['administracao_id'] = $this->administrationId;
        }

        if ($this->search !== '') {
            $query['busca'] = $this->search;
        }

        if ($this->state !== null && $this->state !== '') {
            $query['estado'] = $this->state;
        }

        return $query;
    }
}

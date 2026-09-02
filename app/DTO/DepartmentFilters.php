<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class DepartmentFilters
{
    public function __construct(
        public ?int $administrationId,
        public ?int $comumId,
        public string $search,
        public int $page,
        public int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $administrationId = (int) $request->query('administracao_id', 0);
        $comumId = (int) $request->query('comum_id', 0);

        return new self(
            administrationId: $administrationId > 0 ? $administrationId : null,
            comumId: $comumId > 0 ? $comumId : null,
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
        $query = [];

        if ($this->administrationId !== null) {
            $query['administracao_id'] = $this->administrationId;
        }

        if ($this->comumId !== null) {
            $query['comum_id'] = $this->comumId;
        }

        if ($this->search !== '') {
            $query['busca'] = $this->search;
        }

        return $query;
    }
}

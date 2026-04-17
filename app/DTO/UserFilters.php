<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class UserFilters
{
    public function __construct(
        public ?int $administrationId,
        public string $search,
        public string $status,
        public int $page,
        public int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $administrationId = (int) $request->query('administracao_id', 0);
        $status = trim((string) $request->query('status', ''));

        return new self(
            administrationId: $administrationId > 0 ? $administrationId : null,
            search: trim((string) $request->query('busca', '')),
            status: in_array($status, ['0', '1'], true) ? $status : '',
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

        if ($this->status !== '') {
            $query['status'] = $this->status;
        }

        return $query;
    }
}

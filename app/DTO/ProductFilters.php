<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class ProductFilters
{
    public function __construct(
        public ?int $comumId,
        public string $search,
        public ?int $dependencyId,
        public ?int $assetTypeId,
        public string $status,
        public bool $onlyNew,
        public int $page,
        public int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $comumId = (int) $request->query('comum_id', 0);
        $dependencyId = (int) $request->query('dependencia_id', 0);
        $assetTypeId = (int) $request->query('tipo_bem_id', 0);
        $search = trim((string) $request->query('busca', ''));

        if ($search === '') {
            $search = trim((string) $request->query('nome', ''));
        }

        if ($search === '') {
            $search = trim((string) $request->query('codigo', ''));
        }

        return new self(
            comumId: $comumId > 0 ? $comumId : null,
            search: $search,
            dependencyId: $dependencyId > 0 ? $dependencyId : null,
            assetTypeId: $assetTypeId > 0 ? $assetTypeId : null,
            status: trim((string) $request->query('status', '')),
            onlyNew: $request->boolean('somente_novos') || $request->query('status') === 'novos',
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

        if ($this->comumId !== null) {
            $query['comum_id'] = $this->comumId;
        }

        if ($this->search !== '') {
            $query['busca'] = $this->search;
        }

        if ($this->dependencyId !== null) {
            $query['dependencia_id'] = $this->dependencyId;
        }

        if ($this->assetTypeId !== null) {
            $query['tipo_bem_id'] = $this->assetTypeId;
        }

        if ($this->status !== '') {
            $query['status'] = $this->status;
        }

        if ($this->onlyNew) {
            $query['somente_novos'] = 1;
        }

        return $query;
    }
}

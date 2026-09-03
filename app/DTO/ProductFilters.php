<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class ProductFilters
{
    public function __construct(
        public ?int $administrationId,
        public ?int $comumId,
        public string $search,
        public ?int $dependencyId,
        public ?int $assetTypeId,
        public ?string $state,
        public string $status,
        public bool $onlyNew,
        public int $page,
        public int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $administrationId = (int) $request->query('administracao_id', 0);
        $comumId = (int) $request->query('comum_id', 0);
        $dependencyId = (int) $request->query('dependencia_id', 0);
        $assetTypeId = (int) $request->query('tipo_bem_id', 0);
        $search = trim((string) $request->query('busca', ''));
        $rawState = trim((string) $request->query('estado', ''));
        $state = $rawState !== '' ? mb_strtoupper(mb_substr($rawState, 0, 2, 'UTF-8'), 'UTF-8') : null;

        if ($search === '') {
            $search = trim((string) $request->query('nome', ''));
        }

        if ($search === '') {
            $search = trim((string) $request->query('codigo', ''));
        }

        return new self(
            administrationId: $administrationId > 0 ? $administrationId : null,
            comumId: $comumId > 0 ? $comumId : null,
            search: $search,
            dependencyId: $dependencyId > 0 ? $dependencyId : null,
            assetTypeId: $assetTypeId > 0 ? $assetTypeId : null,
            state: $state,
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

        if ($this->administrationId !== null) {
            $query['administracao_id'] = $this->administrationId;
        }

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

        if ($this->state !== null && $this->state !== '') {
            $query['estado'] = $this->state;
        }

        if ($this->status !== '') {
            $query['status'] = $this->status;
        }

        if ($this->onlyNew && $this->status !== 'novos') {
            $query['somente_novos'] = 1;
        }

        return $query;
    }

    public function activeCriteriaCount(): int
    {
        $count = 0;

        foreach ([
            $this->administrationId,
            $this->comumId,
            $this->search,
            $this->dependencyId,
            $this->assetTypeId,
            $this->state,
        ] as $value) {
            if ($value !== null && $value !== '') {
                $count++;
            }
        }

        if ($this->status !== '') {
            $count++;
        }

        if ($this->onlyNew && $this->status !== 'novos') {
            $count++;
        }

        return $count;
    }
}

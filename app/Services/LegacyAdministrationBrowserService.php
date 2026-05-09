<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAdministrationBrowserServiceInterface;
use App\DTO\AdministrationFilters;
use App\Models\Legacy\Administracao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LegacyAdministrationBrowserService implements LegacyAdministrationBrowserServiceInterface
{
    public function paginate(AdministrationFilters $filters): LengthAwarePaginator
    {
        $cnpjSearch = preg_replace('/\D+/', '', $filters->search) ?? '';

        return Administracao::query()
            ->when(
                $filters->search !== '',
                static function ($query) use ($filters, $cnpjSearch): void {
                    $query->where(function ($nested) use ($filters, $cnpjSearch): void {
                        if (ctype_digit($filters->search)) {
                            $nested->whereKey((int) $filters->search);
                        }

                        $nested->orWhere('descricao', 'like', '%' . $filters->search . '%');
                        if ($cnpjSearch !== '') {
                            $nested->orWhere('cnpj', 'like', '%' . $cnpjSearch . '%');
                        }
                    });
                }
            )
            ->orderBy('descricao')
            ->paginate(
                perPage: $filters->perPage,
                pageName: 'pagina',
                page: $filters->page,
            );
    }

    public function countAll(): int
    {
        return Administracao::query()->count();
    }
}

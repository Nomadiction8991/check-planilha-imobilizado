<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use Illuminate\Support\Facades\Session;
use RuntimeException;

trait ResolvesLegacyProductScope
{
    protected function assertChurchWithinProductScope(int $churchId): Comum
    {
        $church = Comum::query()->find($churchId);

        if ($church === null) {
            throw new RuntimeException('A igreja selecionada não está mais disponível.');
        }

        if ($this->productScopeIsGlobal()) {
            return $church;
        }

        $administrationId = (int) ($church->administracao_id ?? 0);
        if ($administrationId <= 0 || !in_array($administrationId, $this->productScopeAdministrationIds(), true)) {
            throw new RuntimeException('A igreja selecionada está fora do seu escopo permitido.');
        }

        return $church;
    }

    protected function assertProductWithinProductScope(\App\Models\Legacy\Produto $product): void
    {
        $this->assertChurchWithinProductScope((int) $product->comum_id);
    }

    protected function productScopeIsGlobal(): bool
    {
        return (bool) Session::get('is_admin', false);
    }

    /**
     * @return list<int>
     */
    protected function productScopeAdministrationIds(): array
    {
        $ids = array_values(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            (array) Session::get('administracoes_permitidas', []),
        ), static fn (int $value): bool => $value > 0));

        $currentId = (int) Session::get('administracao_id', 0);
        if ($currentId > 0) {
            $ids[] = $currentId;
        }

        return array_values(array_unique($ids));
    }
}

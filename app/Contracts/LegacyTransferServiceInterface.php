<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Legacy\Produto;
use App\Models\Legacy\Transferencia;
use Illuminate\Support\Collection;

interface LegacyTransferServiceInterface
{
    /**
     * Transfere os bens para outra igreja. Tudo acontece numa única operação:
     * se uma parte falhar, nada muda.
     *
     * @param list<int> $produtoIds
     */
    public function transferir(
        int $comumOrigemId,
        int $comumDestinoId,
        int $dependenciaDestinoId,
        array $produtoIds,
        int $usuarioId,
    ): Transferencia;

    /**
     * Grava o código novo de um bem transferido que estava pendente.
     */
    public function informarCodigo(int $produtoId, int $comumId, string $codigo): void;

    /**
     * Bens da igreja que chegaram por transferência e ainda não têm código.
     *
     * @return Collection<int, Produto>
     */
    public function pendentesDeCodigo(int $comumId): Collection;
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyTransferServiceInterface;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Produto;
use App\Models\Legacy\TipoBem;
use App\Models\Legacy\Transferencia;
use App\Models\Legacy\TransferenciaItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LegacyTransferService implements LegacyTransferServiceInterface
{
    use ResolvesLegacyProductScope;

    private const LIMITE_CODIGO = 50;

    public function transferir(
        int $comumOrigemId,
        int $comumDestinoId,
        int $dependenciaDestinoId,
        array $produtoIds,
        int $usuarioId,
    ): Transferencia {
        $ids = $this->normalizarIds($produtoIds);

        if ($ids === []) {
            throw new RuntimeException('Selecione ao menos um bem.');
        }

        if ($comumOrigemId === $comumDestinoId) {
            throw new RuntimeException('A igreja de destino precisa ser diferente da igreja de origem.');
        }

        $origem = $this->assertChurchWithinProductScope($comumOrigemId);
        $destino = $this->assertChurchWithinProductScope($comumDestinoId);

        $dependencia = Dependencia::query()
            ->whereKey($dependenciaDestinoId)
            ->where('comum_id', $destino->id)
            ->first();

        if ($dependencia === null) {
            throw new RuntimeException('O local selecionado não pertence à igreja de destino.');
        }

        return DB::transaction(function () use ($origem, $destino, $dependencia, $ids, $usuarioId): Transferencia {
            // Trava as duas igrejas, para que os números de saída e de entrada não se repitam.
            Comum::query()->whereKey([$origem->id, $destino->id])->lockForUpdate()->get();

            $produtos = Produto::query()
                ->whereIn('id_produto', $ids)
                ->where('comum_id', $origem->id)
                ->where('ativo', 1)
                ->orderBy('id_produto')
                ->lockForUpdate()
                ->get();

            if ($produtos->count() !== count($ids)) {
                throw new RuntimeException('Algum bem não está mais nesta igreja. Atualize a lista e tente de novo.');
            }

            $transferencia = Transferencia::query()->create([
                'comum_origem_id' => $origem->id,
                'comum_destino_id' => $destino->id,
                'dependencia_destino_id' => $dependencia->id,
                'numero_saida' => $this->proximoNumero('comum_origem_id', $origem->id, 'numero_saida'),
                'numero_entrada' => $this->proximoNumero('comum_destino_id', $destino->id, 'numero_entrada'),
                'usuario_id' => $usuarioId > 0 ? $usuarioId : null,
            ]);

            foreach ($produtos as $produto) {
                TransferenciaItem::query()->create(array_merge(
                    $this->copiaDoBem($produto),
                    ['transferencia_id' => $transferencia->id],
                ));

                // O bem perde o código e fica pendente até a igreja de destino informar um novo.
                $produto->fill([
                    'comum_id' => $destino->id,
                    'codigo' => null,
                    'dependencia_id' => $dependencia->id,
                    'editado_dependencia_id' => $dependencia->id,
                    'checado' => 0,
                    'imprimir_etiqueta' => 0,
                ])->save();
            }

            return $transferencia->load('itens');
        });
    }

    public function informarCodigo(int $produtoId, int $comumId, string $codigo): void
    {
        $this->assertChurchWithinProductScope($comumId);

        $codigoLimpo = trim($codigo);

        if ($codigoLimpo === '') {
            throw new RuntimeException('Informe o código do bem.');
        }

        if (mb_strlen($codigoLimpo) > self::LIMITE_CODIGO) {
            throw new RuntimeException('O código pode ter no máximo 50 caracteres.');
        }

        DB::transaction(function () use ($produtoId, $comumId, $codigoLimpo): void {
            $produto = Produto::query()
                ->whereKey($produtoId)
                ->where('comum_id', $comumId)
                ->lockForUpdate()
                ->first();

            if ($produto === null) {
                throw new RuntimeException('O bem não pertence a esta igreja.');
            }

            $itemPendente = TransferenciaItem::query()
                ->where('produto_id', $produtoId)
                ->whereNull('codigo_novo')
                ->latest('id')
                ->first();

            if ($itemPendente === null) {
                throw new RuntimeException('Este bem não está pendente de código.');
            }

            $emUso = Produto::query()
                ->where('codigo', $codigoLimpo)
                ->where('id_produto', '!=', $produtoId)
                ->exists();

            if ($emUso) {
                throw new RuntimeException('Este código já está em uso por outro bem.');
            }

            // Com código novo, o bem deixa de ser pendente e vai para a lista de etiquetas.
            $produto->fill([
                'codigo' => $codigoLimpo,
                'checado' => 1,
                'imprimir_etiqueta' => 1,
            ])->save();

            $itemPendente->update(['codigo_novo' => $codigoLimpo]);
        });
    }

    public function pendentesDeCodigo(int $comumId): Collection
    {
        $idsPendentes = TransferenciaItem::query()
            ->whereNull('codigo_novo')
            ->select('produto_id');

        return Produto::query()
            ->where('comum_id', $comumId)
            ->where('ativo', 1)
            ->whereNull('codigo')
            ->whereIn('id_produto', $idsPendentes)
            ->orderBy('id_produto')
            ->get();
    }

    private function proximoNumero(string $colunaIgreja, int $comumId, string $colunaNumero): int
    {
        $maior = (int) Transferencia::query()
            ->where($colunaIgreja, $comumId)
            ->max($colunaNumero);

        return $maior + 1;
    }

    /**
     * Cópia dos dados do bem no momento da transferência, para o histórico e os formulários.
     *
     * @return array<string, mixed>
     */
    private function copiaDoBem(Produto $produto): array
    {
        $editado = (int) $produto->editado === 1;
        $tipoId = (int) ($editado ? $produto->editado_tipo_bem_id : $produto->tipo_bem_id);
        $dependenciaId = (int) ($editado ? $produto->editado_dependencia_id : $produto->dependencia_id);
        $bem = trim((string) ($editado ? $produto->editado_bem : $produto->bem));
        $complemento = trim((string) ($editado ? $produto->editado_complemento : $produto->complemento));
        $codigo = trim((string) $produto->codigo);

        return [
            'produto_id' => (int) $produto->id_produto,
            'codigo_anterior' => $codigo !== '' ? $codigo : null,
            'codigo_novo' => null,
            'tipo_bem' => (string) (TipoBem::query()->whereKey($tipoId)->value('descricao') ?? ''),
            'descricao' => trim($bem . ' ' . $complemento),
            'quantidade' => 1,
            'valor' => $produto->nota_valor,
            'dependencia_origem' => (string) (Dependencia::query()->whereKey($dependenciaId)->value('descricao') ?? ''),
        ];
    }

    /**
     * @param array<int, mixed> $produtoIds
     * @return list<int>
     */
    private function normalizarIds(array $produtoIds): array
    {
        $ids = array_filter(
            array_map(static fn (mixed $valor): int => (int) $valor, $produtoIds),
            static fn (int $id): bool => $id > 0,
        );

        return array_values(array_unique($ids));
    }
}

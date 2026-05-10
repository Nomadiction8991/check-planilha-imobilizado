<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAssetTypeManagementServiceInterface;
use App\DTO\AssetTypeMutationData;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\TipoBem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class LegacyAssetTypeManagementService implements LegacyAssetTypeManagementServiceInterface
{
    public function create(AssetTypeMutationData $data): TipoBem
    {
        $this->assertAdministrationExists($data->administrationId);
        $this->assertAdministrationAllowed($data->administrationId);

        return DB::transaction(function () use ($data): TipoBem {
            $nextCode = $this->nextCode();

            $payload = [
                'codigo' => $nextCode,
                'descricao' => $data->description,
            ];

            if ($this->supportsAdministrationScope()) {
                $payload['administracao_id'] = $data->administrationId;
            }

            return TipoBem::query()->create($payload);
        });
    }

    public function update(TipoBem $assetType, AssetTypeMutationData $data): TipoBem
    {
        $this->assertAssetTypeWithinScope($assetType);
        $this->assertAdministrationExists($data->administrationId);
        $this->assertAdministrationAllowed($data->administrationId);

        $payload = [
            'descricao' => $data->description,
        ];

        if ($this->supportsAdministrationScope()) {
            $payload['administracao_id'] = $data->administrationId;
        }

        $assetType->fill($payload);
        $assetType->save();

        return $assetType->refresh();
    }

    public function delete(TipoBem $assetType): void
    {
        $this->assertAssetTypeWithinScope($assetType);

        if ($assetType->products()->exists()) {
            throw new RuntimeException('Este tipo de bem não pode ser excluído porque já está vinculado a produtos.');
        }

        $assetType->delete();
    }

    private function nextCode(): int
    {
        $query = TipoBem::query()
            ->select('codigo')
            ->orderByDesc('codigo');

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $query->lockForUpdate();
        }

        $lastCode = $query->value('codigo');

        return ((int) $lastCode) + 1;
    }

    private function assertAdministrationExists(?int $administrationId): void
    {
        if ($administrationId === null || $administrationId <= 0) {
            throw new RuntimeException('Selecione uma administração válida.');
        }

        if (!Administracao::query()->whereKey($administrationId)->exists()) {
            throw new RuntimeException('A administração selecionada não está mais disponível.');
        }
    }

    private function assertAdministrationAllowed(int $administrationId): void
    {
        if ($this->canManageOtherAdministrations()) {
            return;
        }

        if (!$this->supportsAdministrationScope()) {
            return;
        }

        $currentAdministrationId = $this->currentAdministrationId();
        if ($currentAdministrationId === null || $currentAdministrationId !== $administrationId) {
            throw new RuntimeException('Você só pode cadastrar ou editar tipos de bem da sua própria administração.');
        }
    }

    private function assertAssetTypeWithinScope(TipoBem $assetType): void
    {
        if ($this->canManageOtherAdministrations()) {
            return;
        }

        if (!$this->supportsAdministrationScope()) {
            return;
        }

        $currentAdministrationId = $this->currentAdministrationId();
        $assetAdministrationId = (int) ($assetType->administracao_id ?? 0);

        if ($currentAdministrationId === null || $assetAdministrationId > 0 && $assetAdministrationId !== $currentAdministrationId) {
            throw new RuntimeException('Você só pode gerenciar tipos de bem da sua própria administração.');
        }
    }

    private function canManageOtherAdministrations(): bool
    {
        return (bool) Session::get('is_admin', false);
    }

    private function currentAdministrationId(): ?int
    {
        $administrationId = (int) Session::get('administracao_id', 0);

        return $administrationId > 0 ? $administrationId : null;
    }

    private function supportsAdministrationScope(): bool
    {
        return Schema::hasColumn('tipos_bens', 'administracao_id');
    }
}

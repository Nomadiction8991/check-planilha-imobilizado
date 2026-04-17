<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAdministrationManagementServiceInterface;
use App\DTO\AdministrationMutationData;
use App\Models\Legacy\Administracao;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacyAdministrationManagementService implements LegacyAdministrationManagementServiceInterface
{
    public function create(AdministrationMutationData $data): Administracao
    {
        return Administracao::query()->create([
            'descricao' => $this->normalizeDescription($data->description),
        ]);
    }

    public function update(Administracao $administration, AdministrationMutationData $data): Administracao
    {
        $administration->fill([
            'descricao' => $this->normalizeDescription($data->description),
        ]);
        $administration->save();

        return $administration->refresh();
    }

    public function delete(Administracao $administration): void
    {
        if (DB::table('usuarios')->where('administracao_id', (int) $administration->getKey())->exists()) {
            throw new RuntimeException('Esta administração não pode ser excluída porque já está vinculada a usuários.');
        }

        if (DB::table('importacoes')->where('administracao_id', (int) $administration->getKey())->exists()) {
            throw new RuntimeException('Esta administração não pode ser excluída porque já está vinculada a importações.');
        }

        $administration->delete();
    }

    private function normalizeDescription(string $description): string
    {
        return trim($description);
    }
}

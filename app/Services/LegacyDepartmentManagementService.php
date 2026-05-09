<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyDepartmentManagementServiceInterface;
use App\DTO\DepartmentMutationData;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class LegacyDepartmentManagementService implements LegacyDepartmentManagementServiceInterface
{
    public function create(DepartmentMutationData $data): Dependencia
    {
        $normalizedDescription = $this->normalizeDescription($data->description);
        $this->assertChurchExists($data->churchId);
        $this->assertUniqueDescription(
            churchId: $data->churchId,
            normalizedDescription: $normalizedDescription,
        );

        return Dependencia::query()->create([
            'comum_id' => $data->churchId,
            'descricao' => $normalizedDescription,
        ]);
    }

    public function update(Dependencia $department, DepartmentMutationData $data): Dependencia
    {
        $normalizedDescription = $this->normalizeDescription($data->description);
        $this->assertChurchExists($data->churchId);
        $this->assertUniqueDescription(
            churchId: $data->churchId,
            normalizedDescription: $normalizedDescription,
            ignoreDepartmentId: (int) $department->getKey(),
        );

        $department->fill([
            'comum_id' => $data->churchId,
            'descricao' => $normalizedDescription,
        ]);
        $department->save();

        return $department->refresh();
    }

    public function delete(Dependencia $department): void
    {
        if ($department->products()->exists()) {
            throw new RuntimeException('Esta dependência não pode ser excluída porque já está vinculada a produtos.');
        }

        $department->delete();
    }

    private function assertChurchExists(int $churchId): void
    {
        if (!Comum::query()->whereKey($churchId)->exists()) {
            throw new RuntimeException('A igreja selecionada não está mais disponível.');
        }
    }

    private function assertUniqueDescription(
        int $churchId,
        string $normalizedDescription,
        ?int $ignoreDepartmentId = null,
    ): void {
        $exists = Dependencia::query()
            ->where('comum_id', $churchId)
            ->where('descricao', $normalizedDescription)
            ->when(
                $ignoreDepartmentId !== null,
                static fn (Builder $query) => $query->whereKeyNot($ignoreDepartmentId)
            )
            ->exists();

        if ($exists) {
            throw new RuntimeException('Já existe uma dependência com essa descrição para a igreja selecionada.');
        }
    }

    private function normalizeDescription(string $description): string
    {
        return mb_strtoupper(trim($description), 'UTF-8');
    }
}

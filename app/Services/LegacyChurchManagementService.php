<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyChurchManagementServiceInterface;
use App\DTO\ChurchMutationData;
use App\Models\Legacy\Comum;
use App\Support\LegacyCnpjValidator;
use RuntimeException;

class LegacyChurchManagementService implements LegacyChurchManagementServiceInterface
{
    public function update(Comum $church, ChurchMutationData $data): Comum
    {
        $normalizedCode = $this->normalizeCode((string) $church->codigo);

        if ($normalizedCode === '') {
            throw new RuntimeException('Código inválido para o cadastro selecionado.');
        }

        try {
            $validatedCnpj = LegacyCnpjValidator::validate($data->cnpj);
        } catch (\InvalidArgumentException $exception) {
            throw new RuntimeException('CNPJ inválido: ' . $exception->getMessage());
        }

        $church->fill([
            'descricao' => mb_strtoupper($data->description, 'UTF-8'),
            'cnpj' => $this->generateUniqueCnpj($validatedCnpj, $normalizedCode, (int) $church->id),
            'administracao_id' => $data->administrationId,
            'estado' => $data->state,
            'cidade' => mb_strtoupper($data->city, 'UTF-8'),
            'estado_administracao' => $data->administrationState,
            'cidade_administracao' => mb_strtoupper($data->administrationCity, 'UTF-8'),
            'setor' => $data->sector,
        ]);
        $church->save();

        return $church->refresh();
    }

    public function findChurch(int $churchId): ?Comum
    {
        return Comum::query()->find($churchId);
    }

    public function countProducts(int $churchId): int
    {
        $church = $this->findChurch($churchId);

        if ($church === null) {
            throw new RuntimeException('Igreja não encontrada.');
        }

        return $church->products()->count();
    }

    public function deleteProducts(Comum $church): int
    {
        return $church->products()->delete();
    }

    private function generateUniqueCnpj(string $cnpjBase, string $code, int $ignoreChurchId): string
    {
        $base = $cnpjBase === '' ? 'SEM-CNPJ-' . $code : $cnpjBase;
        $candidate = $base;
        $attempt = 0;

        while (true) {
            $existingId = Comum::query()
                ->where('cnpj', $candidate)
                ->value('id');

            if ($existingId === null || (int) $existingId === $ignoreChurchId) {
                return $candidate;
            }

            $attempt++;
            $candidate = $base . '-COD-' . $code;

            if ($attempt > 1) {
                $candidate .= '-' . $attempt;
            }
        }
    }

    private function normalizeCode(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        if (preg_match('/^(\d{2})\D?(\d{4})$/', $code, $matches)) {
            return $matches[1] . '-' . $matches[2];
        }

        return mb_strtoupper($code, 'UTF-8');
    }
}

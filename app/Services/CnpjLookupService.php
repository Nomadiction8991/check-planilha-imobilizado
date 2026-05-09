<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class CnpjLookupService
{
    /**
     * @return array{nome: string, cidade: string}|null
     */
    public function lookup(string $cnpj): ?array
    {
        try {
            /** @var Response $response */
            $response = Http::timeout(5)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'CheckPlanilha/1.0',
                ])
                ->get(sprintf('https://www.cnpj.dev/api/v1/cnpj/%s', $cnpj));

            if ($response->status() < 200 || $response->status() >= 300) {
                return null;
            }

            $payload = json_decode($response->body(), true);

            if (!is_array($payload)) {
                return null;
            }

            $name = trim((string) ($payload['razao_social'] ?? $payload['nome_fantasia'] ?? ''));

            if ($name === '') {
                return null;
            }

            return [
                'nome' => $name,
                'cidade' => trim((string) ($payload['municipio'] ?? '')),
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
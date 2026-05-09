<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class BrazilLocalityController extends Controller
{
    public function cities(Request $request, string $state): JsonResponse
    {
        $state = strtoupper(trim($state));

        if (!array_key_exists($state, (array) config('brazil.states', []))) {
            return response()->json([
                'success' => false,
                'message' => 'UF inválida.',
                'data' => [],
            ], 422);
        }

        foreach ($this->cityProviders($state) as $source => $url) {
            try {
                $response = Http::timeout(8)
                    ->acceptJson()
                    ->get($url);

                if (! $response->ok()) {
                    continue;
                }

                $cities = $this->extractCities((array) $response->json());

                if ($cities === []) {
                    continue;
                }

                return response()->json([
                    'success' => true,
                    'data' => $cities,
                    'source' => $source,
                ]);
            } catch (Throwable) {
                continue;
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Não foi possível consultar as cidades.',
            'data' => [],
        ], 502);
    }

    /**
     * @return array<string, string>
     */
    private function cityProviders(string $state): array
    {
        return [
            'IBGE' => "https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$state}/municipios",
            'BrasilAPI' => "https://brasilapi.com.br/api/ibge/municipios/v1/{$state}",
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $payload
     * @return array<int, string>
     */
    private function extractCities(array $payload): array
    {
        $cities = [];

        foreach ($payload as $item) {
            $name = $this->normalizeCityName((string) ($item['nome'] ?? ''));

            if ($name !== '') {
                $cities[] = $name;
            }
        }

        return $cities;
    }

    private function normalizeCityName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        if ($name === '') {
            return '';
        }

        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }
}

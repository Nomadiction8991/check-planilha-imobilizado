<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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

        $response = Http::timeout(8)
            ->acceptJson()
            ->get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$state}/municipios");

        if (!$response->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível consultar as cidades.',
                'data' => [],
            ], 502);
        }

        $payload = $response->json();
        $cities = [];

        foreach ((array) $payload as $item) {
            $name = trim((string) ($item['nome'] ?? ''));

            if ($name === '') {
                continue;
            }

            $cities[] = $name;
        }

        return response()->json([
            'success' => true,
            'data' => $cities,
            'source' => 'IBGE',
        ]);
    }
}

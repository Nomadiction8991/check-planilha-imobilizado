<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LookupCnpjRequest;
use App\Services\CnpjLookupService;
use Illuminate\Http\JsonResponse;

final class CnpjLookupController extends Controller
{
    public function __construct(
        private readonly CnpjLookupService $service,
    ) {
    }

    public function lookup(LookupCnpjRequest $request): JsonResponse
    {
        $result = $this->service->lookup($request->toDto()->cnpj);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'CNPJ não encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
            'source' => 'cnpj.dev',
        ]);
    }
}
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints da sincronização entre o notebook e o servidor.
 *
 * Ainda são um marcador: a lógica entra na etapa de envio e recebimento.
 */
final class SyncController extends Controller
{
    public function enviar(Request $request): JsonResponse
    {
        return response()->json(['erro' => 'Ainda não implementado.'], 501);
    }

    public function receber(Request $request): JsonResponse
    {
        return response()->json(['erro' => 'Ainda não implementado.'], 501);
    }
}

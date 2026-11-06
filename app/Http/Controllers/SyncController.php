<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Sync\Device;
use App\Sync\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints da sincronização entre o notebook e o servidor.
 *
 * O middleware "sincronizacao.aparelho" já garantiu que o notebook é válido.
 */
final class SyncController extends Controller
{
    public function __construct(
        private readonly SyncService $sincronizacao,
    ) {
    }

    public function enviar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'alteracoes' => ['required', 'array', 'min:1', 'max:' . SyncService::LIMITE_POR_ENVIO],
            'alteracoes.*.uuid' => ['required', 'string', 'size:36'],
            'alteracoes.*.tabela' => ['required', 'string', 'max:64'],
            'alteracoes.*.chave' => ['required', 'string', 'max:255'],
            'alteracoes.*.operacao' => ['required', 'in:insert,update,delete'],
            'alteracoes.*.antes' => ['nullable', 'array'],
            'alteracoes.*.depois' => ['nullable', 'array'],
            'alteracoes.*.criado_em' => ['nullable', 'string'],
        ]);

        /** @var Device $aparelho */
        $aparelho = $request->attributes->get('aparelho');

        return response()->json($this->sincronizacao->enviar($aparelho, $dados['alteracoes']));
    }

    public function receber(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'depois' => ['required', 'integer', 'min:0'],
        ]);

        /** @var Device $aparelho */
        $aparelho = $request->attributes->get('aparelho');

        return response()->json($this->sincronizacao->receber($aparelho, (int) $dados['depois']));
    }
}

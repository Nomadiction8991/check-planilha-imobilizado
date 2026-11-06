<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Sync\CopyExporter;
use App\Sync\Device;
use App\Sync\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Cópia completa para o primeiro download do notebook (NDJSON, em fluxo).
     */
    public function copia(): StreamedResponse
    {
        $exportador = app(CopyExporter::class);

        return response()->stream(function () use ($exportador): void {
            foreach ($exportador->linhas() as $linha) {
                echo $linha;
                flush();
            }
        }, 200, ['Content-Type' => 'application/x-ndjson; charset=UTF-8']);
    }

    /**
     * Faixa de números deste aparelho: o id do aparelho vezes o tamanho da faixa (ver config).
     */
    public function faixa(Request $request): JsonResponse
    {
        /** @var Device $aparelho */
        $aparelho = $request->attributes->get('aparelho');

        return response()->json([
            'faixa_inicial' => (int) $aparelho->id * (int) config('sincronizacao.faixa_por_aparelho'),
        ]);
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

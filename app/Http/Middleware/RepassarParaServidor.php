<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Modo\EstadoModo;
use App\Support\Modo\RepassePedido;
use App\Support\Modo\ServidorIndisponivelException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RepassarParaServidor
{
    public function handle(Request $request, Closure $next): Response
    {
        $url = config('modo_igreja.servidor_url');
        if (! config('modo_igreja.ativo') || empty($url) || $request->is(...config('modo_igreja.sem_repasse', []))) {
            return $next($request);
        }

        $estado = resolve(EstadoModo::class);
        if (! $estado->estaOnline()) {
            return $next($request);
        }

        try {
            return (new RepassePedido((string) $url, (string) (config('modo_igreja.servidor_publico') ?: $url)))->enviar($request);
        } catch (ServidorIndisponivelException) {
            $estado->registrarResultado(false);
            if ($request->isMethodSafe()) {
                return $next($request);
            }

            abort(503, 'Não consegui falar com o servidor agora. Confira se a ação foi salva antes de tentar de novo.');
        }
    }
}

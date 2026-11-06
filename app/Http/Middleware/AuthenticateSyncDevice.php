<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Sync\DeviceRegistry;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deixa passar só os notebooks com código de acesso válido e não revogado.
 */
final class AuthenticateSyncDevice
{
    public function __construct(
        private readonly DeviceRegistry $aparelhos,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $aparelho = $this->aparelhos->autenticar($request->bearerToken());

        if ($aparelho === null) {
            return new JsonResponse(['erro' => 'Aparelho não autorizado.'], 401);
        }

        $request->attributes->set('aparelho', $aparelho);

        return $next($request);
    }
}

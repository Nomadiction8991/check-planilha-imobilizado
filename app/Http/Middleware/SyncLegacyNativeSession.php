<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\LegacyNativeSessionBridge;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncLegacyNativeSession
{
    public function __construct(
        private readonly LegacyNativeSessionBridge $bridge,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->bridge->import($request);

        $response = $next($request);

        $this->bridge->export($request);

        return $response;
    }
}

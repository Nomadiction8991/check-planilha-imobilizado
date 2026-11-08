<?php

use App\Http\Middleware\RepassarParaServidor;
use App\Http\Middleware\RestringirARedeLocal;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(RepassarParaServidor::class);

        // Vem depois do repasse na lista, então roda antes: pedido de fora da rede local nem é repassado.
        $middleware->prepend(RestringirARedeLocal::class);
        $middleware->trustProxies(at: '*');

        // A sincronização grava exatamente o que o notebook gravou: texto vazio continua vazio e
        // espaços nas pontas não são removidos. Por isso só as rotas de sincronização ficam isentas
        // desses dois ajustes globais (senão um campo vazio chegava ao servidor como nulo).
        $ehRotaDeSincronizacao = fn (Request $request): bool => $request->is('api/sincronizacao/*');
        $middleware->convertEmptyStringsToNull(except: [$ehRotaDeSincronizacao]);
        $middleware->trimStrings(except: [$ehRotaDeSincronizacao]);

        // Substituição dentro do grupo 'web': replace() global não afeta o grupo,
        // e as isenções de rota do middleware híbrido precisam valer nas requests reais.
        $middleware->replaceInGroup(
            'web',
            PreventRequestForgery::class,
            \App\Http\Middleware\HybridPreventRequestForgery::class,
        );

        $middleware->alias([
            'legacy.bridge' => \App\Http\Middleware\SyncLegacyNativeSession::class,
            'legacy.auth' => \App\Http\Middleware\RequireLegacySession::class,
            'legacy.admin' => \App\Http\Middleware\RequireLegacyAdmin::class,
            'legacy.audit' => \App\Http\Middleware\RecordLegacyAudit::class,
            'legacy.permission' => \App\Http\Middleware\RequireLegacyPermission::class,
            'sincronizacao.aparelho' => \App\Http\Middleware\AuthenticateSyncDevice::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

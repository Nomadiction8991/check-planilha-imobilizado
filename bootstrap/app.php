<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

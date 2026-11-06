<?php

declare(strict_types=1);

use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

// Sincronização entre o notebook e o servidor. Cada rota exige um código de aparelho válido.
Route::prefix('sincronizacao')
    ->middleware('sincronizacao.aparelho')
    ->group(function (): void {
        Route::post('/enviar', [SyncController::class, 'enviar'])->name('api.sincronizacao.enviar');
        Route::get('/receber', [SyncController::class, 'receber'])->name('api.sincronizacao.receber');
        Route::get('/copia', [SyncController::class, 'copia'])->name('api.sincronizacao.copia');
    });

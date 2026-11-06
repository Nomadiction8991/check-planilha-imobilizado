<?php

declare(strict_types=1);

use App\Http\Controllers\SincronizacaoArquivosController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

// Sincronização entre o notebook e o servidor. Cada rota exige um código de aparelho válido.
Route::prefix('sincronizacao')
    ->middleware('sincronizacao.aparelho')
    ->group(function (): void {
        Route::post('/enviar', [SyncController::class, 'enviar'])->name('api.sincronizacao.enviar');
        Route::get('/receber', [SyncController::class, 'receber'])->name('api.sincronizacao.receber');
        Route::get('/copia', [SyncController::class, 'copia'])->name('api.sincronizacao.copia');
        Route::get('/faixa', [SyncController::class, 'faixa'])->name('api.sincronizacao.faixa');

        // Planilhas e auditoria. O nome da planilha aceita só letras, números, ponto, traço e sublinhado.
        Route::get('/planilhas', [SincronizacaoArquivosController::class, 'listarPlanilhas'])
            ->name('api.sincronizacao.planilhas.listar');
        Route::put('/planilhas/{nome}', [SincronizacaoArquivosController::class, 'gravarPlanilha'])
            ->where('nome', '[A-Za-z0-9][A-Za-z0-9_.\-]*')
            ->name('api.sincronizacao.planilhas.gravar');
        Route::get('/planilhas/{nome}', [SincronizacaoArquivosController::class, 'baixarPlanilha'])
            ->where('nome', '[A-Za-z0-9][A-Za-z0-9_.\-]*')
            ->name('api.sincronizacao.planilhas.baixar');
        Route::get('/auditoria', [SincronizacaoArquivosController::class, 'lerAuditoria'])
            ->name('api.sincronizacao.auditoria.ler');
        Route::post('/auditoria', [SincronizacaoArquivosController::class, 'gravarAuditoria'])
            ->name('api.sincronizacao.auditoria.gravar');
    });

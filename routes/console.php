<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Support\Modo\EstadoModo;
use App\Support\Modo\RedeAutomatica;
use App\Support\Modo\VerificadorDeServidor;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('modo-igreja:verificar-rede', function (EstadoModo $estado, VerificadorDeServidor $verificador): void {
    if (config('modo_igreja.ativo')) {
        $estado->registrarResultado($verificador->verificar());
    }
});

Artisan::command('modo-igreja:rede-propria', function (RedeAutomatica $rede): void {
    if (config('modo_igreja.ativo') && PHP_OS_FAMILY === 'Linux') {
        $this->info($rede->verificar());
    }
});

if (config('modo_igreja.ativo')) {
    Schedule::command('modo-igreja:verificar-rede')->everyMinute()->withoutOverlapping();
    if (PHP_OS_FAMILY === 'Linux') {
        Schedule::command('modo-igreja:rede-propria')->everyMinute()->withoutOverlapping();
    }
}

// Rotinas agendadas de manutenção preventiva do sistema
Schedule::command('analyses:cleanup-orphans')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('system:storage-cleanup --days=7')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('system:health-check')->hourly();

// Sincronização do notebook da igreja. No servidor (modo igreja desligado) ela não roda.
Schedule::command('sincronizacao:executar')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('modo_igreja.ativo')
        && filled(config('modo_igreja.servidor_url'))
        && filled(config('modo_igreja.aparelho_token')));

<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Rotinas agendadas de manutenção preventiva do sistema
Schedule::command('analyses:cleanup-orphans')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('system:storage-cleanup --days=7')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('system:health-check')->hourly();

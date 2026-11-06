<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AccessPointInterface;
use App\Contracts\CommandRunnerInterface;
use App\Support\Connection\HotspotCredentials;
use App\Support\Connection\NetworkManagerAccessPoint;
use App\Support\Connection\ProcessCommandRunner;
use App\Support\Connection\WindowsAccessPoint;
use Illuminate\Support\ServiceProvider;

/**
 * Escolhe o ponto de acesso certo para o sistema operacional (NetworkManager no Linux, API do Windows).
 */
final class ConnectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CommandRunnerInterface::class, ProcessCommandRunner::class);

        $this->app->bind(AccessPointInterface::class, function ($app): AccessPointInterface {
            $comandos = $app->make(CommandRunnerInterface::class);

            return PHP_OS_FAMILY === 'Windows'
                ? new WindowsAccessPoint($comandos, sys_get_temp_dir())
                : new NetworkManagerAccessPoint($comandos);
        });

        $this->app->bind(
            HotspotCredentials::class,
            fn (): HotspotCredentials => new HotspotCredentials(storage_path('app/modo-igreja/rede.senha')),
        );
    }
}

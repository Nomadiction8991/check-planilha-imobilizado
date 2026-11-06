<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Sync\DeviceRegistry;
use Illuminate\Console\Command;

/**
 * Cadastra um notebook para sincronizar e mostra o código de acesso uma única vez.
 */
class RegisterSyncDeviceCommand extends Command
{
    protected $signature = 'sincronizacao:aparelho {nome} {usuario}';

    protected $description = 'Cadastra um notebook para sincronizar e mostra o código de acesso uma vez';

    public function handle(DeviceRegistry $aparelhos): int
    {
        $registro = $aparelhos->registrar((string) $this->argument('nome'), (int) $this->argument('usuario'));

        $this->info("Aparelho cadastrado: {$registro['aparelho']->nome} (id {$registro['aparelho']->id}).");
        $this->line('Código de acesso (guarde-o, ele não aparece de novo): ' . $registro['token']);

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Sync\Device;
use App\Sync\DeviceRegistry;
use Illuminate\Console\Command;

/**
 * Revoga o código de acesso de um notebook. Ele deixa de sincronizar na próxima tentativa.
 */
class RevokeSyncDeviceCommand extends Command
{
    protected $signature = 'sincronizacao:revogar {aparelho : Número do aparelho na tabela sincronizacao_aparelhos}';

    protected $description = 'Revoga o código de acesso de um notebook (ele deixa de sincronizar)';

    public function handle(DeviceRegistry $aparelhos): int
    {
        $aparelho = Device::query()->find((int) $this->argument('aparelho'));

        if ($aparelho === null) {
            $this->error('Aparelho não encontrado.');

            return self::FAILURE;
        }

        if ($aparelho->estaRevogado()) {
            $this->info("O aparelho {$aparelho->nome} já estava revogado.");

            return self::SUCCESS;
        }

        $aparelhos->revogar($aparelho);
        $this->info("Aparelho revogado: {$aparelho->nome} (número {$aparelho->id}).");

        return self::SUCCESS;
    }
}

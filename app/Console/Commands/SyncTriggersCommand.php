<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Sync\TriggerGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recria os gatilhos que registram as alterações das tabelas de dados.
 * Pode rodar de novo quando o schema mudar: a recriação é idempotente.
 */
class SyncTriggersCommand extends Command
{
    protected $signature = 'sincronizacao:gatilhos';

    protected $description = 'Recria os gatilhos que registram as alterações das tabelas de dados (só MySQL)';

    public function handle(TriggerGenerator $gerador): int
    {
        $driver = DB::getDriverName();

        if ($driver !== 'mysql') {
            $this->error("Os gatilhos de sincronização só existem no MySQL. Banco atual: {$driver}.");

            return self::FAILURE;
        }

        /** @var list<string> $tabelas */
        $tabelas = config('sincronizacao.tabelas');
        $gerador->generateFor($tabelas);

        $this->info('Gatilhos recriados para ' . count($tabelas) . ' tabelas.');

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Fora do MySQL o comando não tem o que fazer. Ele precisa avisar com clareza,
 * em vez de falhar em silêncio ou criar gatilhos que não existem.
 *
 * Não usa RefreshDatabase: as migrations iniciais são SQL do MySQL e não rodam no SQLite.
 */
class SyncTriggersCommandSqliteTest extends TestCase
{
    public function test_recusa_rodar_fora_do_mysql_com_aviso_claro(): void
    {
        $this->assertSame(1, Artisan::call('sincronizacao:gatilhos'));
        $this->assertStringContainsString('MySQL', Artisan::output());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Integration\Sync;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * O comando que recria os gatilhos precisa poder rodar de novo sem duplicar nada.
 * Roda pela suíte de integração (MySQL), porque os gatilhos só existem no MySQL.
 */
class SyncTriggersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->fail('Estes testes precisam de MySQL. Rode com: phpunit -c phpunit.mysql.xml');
        }
    }

    public function test_recriar_gatilhos_e_idempotente(): void
    {
        $antes = $this->totalDeGatilhos();

        $this->assertGreaterThan(0, $antes);
        $this->assertSame(0, Artisan::call('sincronizacao:gatilhos'));
        $this->assertSame(0, Artisan::call('sincronizacao:gatilhos'));
        $this->assertSame($antes, $this->totalDeGatilhos());
    }

    private function totalDeGatilhos(): int
    {
        return (int) DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::raw('DATABASE()'))
            ->count();
    }
}

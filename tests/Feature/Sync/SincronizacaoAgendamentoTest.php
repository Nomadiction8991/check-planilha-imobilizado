<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * A sincronização automática só roda no notebook, com o modo igreja e o código configurados.
 */
class SincronizacaoAgendamentoTest extends TestCase
{
    public function test_sincronizacao_so_roda_com_modo_igreja_e_codigo_configurados(): void
    {
        config([
            'modo_igreja.ativo' => false,
            'modo_igreja.servidor_url' => 'https://servidor.teste',
            'modo_igreja.aparelho_token' => 'codigo-de-teste',
        ]);

        $this->assertFalse($this->eventoDeSincronizacao()->filtersPass($this->app));

        config(['modo_igreja.ativo' => true]);
        $this->assertTrue($this->eventoDeSincronizacao()->filtersPass($this->app));

        config(['modo_igreja.servidor_url' => null]);
        $this->assertFalse($this->eventoDeSincronizacao()->filtersPass($this->app));
    }

    private function eventoDeSincronizacao(): Event
    {
        foreach (app(Schedule::class)->events() as $evento) {
            if (str_contains((string) $evento->command, 'sincronizacao:executar')) {
                return $evento;
            }
        }

        $this->fail('O agendamento de sincronização não existe.');
    }
}

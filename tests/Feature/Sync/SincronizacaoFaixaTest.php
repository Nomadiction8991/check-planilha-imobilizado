<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Sync\DeviceRegistry;

/**
 * Cada notebook recebe uma faixa de números própria, para que itens criados em dois notebooks
 * nunca usem o mesmo número.
 */
class SincronizacaoFaixaTest extends SincronizacaoTestCase
{
    public function test_cada_aparelho_recebe_uma_faixa_propria_de_numeros(): void
    {
        $aparelhos = app(DeviceRegistry::class);
        $a = $aparelhos->registrar('Notebook A', 7);
        $b = $aparelhos->registrar('Notebook B', 7);

        $faixaA = $this->withHeader('Authorization', 'Bearer ' . $a['token'])
            ->getJson('/api/sincronizacao/faixa')
            ->assertOk()
            ->json('faixa_inicial');

        $faixaB = $this->withHeader('Authorization', 'Bearer ' . $b['token'])
            ->getJson('/api/sincronizacao/faixa')
            ->assertOk()
            ->json('faixa_inicial');

        $this->assertSame((int) $a['aparelho']->id * 100000000, $faixaA);
        $this->assertSame((int) $b['aparelho']->id * 100000000, $faixaB);
        $this->assertNotSame($faixaA, $faixaB);
    }

    public function test_faixa_sem_aparelho_valido_responde_401(): void
    {
        $this->getJson('/api/sincronizacao/faixa')->assertUnauthorized();
    }
}

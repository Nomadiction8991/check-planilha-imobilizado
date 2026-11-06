<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Sync\DeviceRegistry;

class SyncDeviceMiddlewareTest extends SincronizacaoTestCase
{
    public function test_rota_de_sincronizacao_sem_token_responde_401(): void
    {
        $this->postJson('/api/sincronizacao/enviar', ['alteracoes' => []])
            ->assertUnauthorized();
    }

    public function test_rota_de_sincronizacao_com_token_revogado_responde_401(): void
    {
        $aparelhos = app(DeviceRegistry::class);
        $registro = $aparelhos->registrar('Notebook da igreja', 7);
        $aparelhos->revogar($registro['aparelho']);

        $this->withHeader('Authorization', 'Bearer ' . $registro['token'])
            ->postJson('/api/sincronizacao/enviar', ['alteracoes' => []])
            ->assertUnauthorized();
    }
}

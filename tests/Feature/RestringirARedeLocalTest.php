<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * No modo igreja, o notebook só atende a rede local e o próprio computador.
 */
final class RestringirARedeLocalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['modo_igreja.ativo' => true]);
    }

    public function test_recusa_quem_esta_fora_da_rede_local(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/up')->assertForbidden();
    }

    public function test_atende_a_rede_local_e_o_proprio_computador(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.0.25'])->get('/up')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '10.42.0.40'])->get('/up')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get('/up')->assertOk();
    }

    public function test_recusa_pedido_que_passou_por_proxy_ou_tunel_mesmo_vindo_de_localhost(): void
    {
        // Um túnel na mesma máquina chega como localhost, mas traz o endereço real do visitante.
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeader('X-Forwarded-For', '8.8.8.8')
            ->get('/up')
            ->assertForbidden();
    }

    public function test_fora_do_modo_igreja_nao_restringe(): void
    {
        config(['modo_igreja.ativo' => false]);

        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/up')->assertOk();
    }
}

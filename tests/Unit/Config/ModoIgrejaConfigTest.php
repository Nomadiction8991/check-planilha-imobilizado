<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Tests\TestCase;

class ModoIgrejaConfigTest extends TestCase
{
    public function test_modo_igreja_vem_desligado_por_padrao(): void
    {
        $this->assertFalse(config('modo_igreja.ativo'));
    }

    public function test_porta_padrao_nao_exige_administrador(): void
    {
        $this->assertSame(8080, config('modo_igreja.porta'));
    }

    public function test_declara_chaves_do_servidor_e_do_aparelho(): void
    {
        $configuracao = config('modo_igreja');

        $this->assertIsArray($configuracao);
        $this->assertArrayHasKey('servidor_url', $configuracao);
        $this->assertArrayHasKey('aparelho_token', $configuracao);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Connection;

use App\Support\Connection\WifiQrPayload;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WifiQrPayloadTest extends TestCase
{
    public function test_monta_o_texto_padrao_de_rede_wpa(): void
    {
        $this->assertSame(
            'WIFI:T:WPA;S:CheckPlanilha;P:senhaSegura123;;',
            WifiQrPayload::para('CheckPlanilha', 'senhaSegura123'),
        );
    }

    public function test_escapa_caracteres_especiais_do_nome_e_da_senha(): void
    {
        $this->assertSame(
            'WIFI:T:WPA;S:Igreja\;Rede\,A;P:senha\:12345678;;',
            WifiQrPayload::para('Igreja;Rede,A', 'senha:12345678'),
        );
    }

    public function test_escapa_barra_invertida_e_aspas(): void
    {
        $this->assertSame(
            'WIFI:T:WPA;S:A\\\\B\"C;P:chave\\\\\"123456;;',
            WifiQrPayload::para('A\\B"C', 'chave\\"123456'),
        );
    }

    public function test_recusa_senha_menor_que_oito_caracteres(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WifiQrPayload::para('CheckPlanilha', 'curta');
    }
}

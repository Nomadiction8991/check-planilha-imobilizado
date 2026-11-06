<?php

declare(strict_types=1);

namespace Tests\Unit\Connection;

use App\Support\Connection\NetworkManagerAccessPoint;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\FakeCommandRunner;

/**
 * Ponto de acesso no Linux (NetworkManager), com o executor de comandos falso.
 */
class NetworkManagerAccessPointTest extends TestCase
{
    private const ADAPTADORES = 'nmcli -t -f DEVICE,TYPE device';

    public function test_liga_o_hotspot_com_nome_e_senha_como_argumentos_separados(): void
    {
        $executor = new FakeCommandRunner([
            self::ADAPTADORES => ['codigo' => 0, 'saida' => "enp2s0:ethernet\nwlp3s0:wifi\n"],
        ]);

        (new NetworkManagerAccessPoint($executor))->ligar('CheckPlanilha', 'senha-segura-123');

        $this->assertSame(
            ['nmcli', 'device', 'wifi', 'hotspot', 'ifname', 'wlp3s0', 'con-name', 'CheckPlanilha-Igreja',
                'ssid', 'CheckPlanilha', 'band', 'bg', 'password', 'senha-segura-123'],
            end($executor->chamadas)['comando'],
        );
    }

    public function test_senha_nao_aparece_na_mensagem_quando_o_nmcli_falha(): void
    {
        $executor = new FakeCommandRunner([
            self::ADAPTADORES => ['codigo' => 0, 'saida' => "wlp3s0:wifi\n"],
            'nmcli device wifi hotspot' => ['codigo' => 1, 'saida' => 'Erro ao usar a senha senha-segura-123'],
        ]);

        try {
            (new NetworkManagerAccessPoint($executor))->ligar('CheckPlanilha', 'senha-segura-123');
            $this->fail('Precisava falhar quando o nmcli recusa.');
        } catch (RuntimeException $erro) {
            $this->assertStringNotContainsString('senha-segura-123', $erro->getMessage());
            $this->assertStringContainsString('Não foi possível ligar', $erro->getMessage());
        }
    }

    public function test_sem_adaptador_wifi_vira_erro_claro(): void
    {
        $executor = new FakeCommandRunner([
            self::ADAPTADORES => ['codigo' => 0, 'saida' => "enp2s0:ethernet\n"],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nenhum adaptador Wi-Fi');

        (new NetworkManagerAccessPoint($executor))->ligar('CheckPlanilha', 'senha-segura-123');
    }

    public function test_sem_networkmanager_vira_erro_claro(): void
    {
        $executor = new FakeCommandRunner([
            self::ADAPTADORES => ['codigo' => 127, 'saida' => ''],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NetworkManager');

        (new NetworkManagerAccessPoint($executor))->ligar('CheckPlanilha', 'senha-segura-123');
    }

    public function test_esta_ligado_quando_a_conexao_do_hotspot_esta_ativa(): void
    {
        $executor = new FakeCommandRunner([
            'nmcli -t -f NAME connection show --active' => ['codigo' => 0, 'saida' => "Conexão cabeada\nCheckPlanilha-Igreja\n"],
        ]);

        $this->assertTrue((new NetworkManagerAccessPoint($executor))->estaLigado());
    }

    public function test_nao_esta_ligado_quando_so_ha_outras_conexoes_ativas(): void
    {
        $executor = new FakeCommandRunner([
            'nmcli -t -f NAME connection show --active' => ['codigo' => 0, 'saida' => "Conexão cabeada\n"],
        ]);

        $this->assertFalse((new NetworkManagerAccessPoint($executor))->estaLigado());
    }

    public function test_desligar_derruba_a_conexao_so_se_estiver_ligada(): void
    {
        $desligado = new FakeCommandRunner();
        (new NetworkManagerAccessPoint($desligado))->desligar();

        $this->assertNotContains(
            ['nmcli', 'connection', 'down', 'id', 'CheckPlanilha-Igreja'],
            $desligado->comandos(),
        );

        $ligado = new FakeCommandRunner([
            'nmcli -t -f NAME connection show --active' => ['codigo' => 0, 'saida' => "CheckPlanilha-Igreja\n"],
        ]);
        (new NetworkManagerAccessPoint($ligado))->desligar();

        $this->assertContains(
            ['nmcli', 'connection', 'down', 'id', 'CheckPlanilha-Igreja'],
            $ligado->comandos(),
        );
    }

    public function test_endereco_da_rede_sai_do_adaptador_sem_a_mascara(): void
    {
        $executor = new FakeCommandRunner([
            'nmcli -t -f NAME connection show --active' => ['codigo' => 0, 'saida' => "CheckPlanilha-Igreja\n"],
            self::ADAPTADORES => ['codigo' => 0, 'saida' => "wlp3s0:wifi\n"],
            'nmcli -g IP4.ADDRESS device show' => ['codigo' => 0, 'saida' => "10.42.0.1/24\n"],
        ]);

        $this->assertSame('10.42.0.1', (new NetworkManagerAccessPoint($executor))->enderecoDaRede());
    }
}

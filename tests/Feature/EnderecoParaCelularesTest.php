<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Connection\EnderecoParaCelulares;
use App\Support\Connection\HotspotCredentials;
use App\Support\Connection\OfflineModeService;
use Tests\Support\FakeAccessPoint;
use Tests\Support\FakeNetworkCommandRunner;
use Tests\TestCase;

final class EnderecoParaCelularesTest extends TestCase
{
    private FakeAccessPoint $ponto;
    private FakeNetworkCommandRunner $comandos;

    protected function setUp(): void
    {
        parent::setUp();
        config(['modo_igreja.porta' => 8080]);
        $this->ponto = new FakeAccessPoint();
        $this->comandos = new FakeNetworkCommandRunner();
    }

    private function servico(string $sistema = 'Linux'): EnderecoParaCelulares
    {
        return new EnderecoParaCelulares($this->comandos, new OfflineModeService(
            $this->ponto,
            new HotspotCredentials(sys_get_temp_dir() . '/cp-endereco-' . uniqid() . '/rede.senha'),
        ), $sistema);
    }

    public function test_rede_propria_ligada_usa_seu_endereco(): void
    {
        $this->ponto->ligado = true;
        $this->assertSame('http://10.42.0.1:8080', $this->servico()->endereco());
        $this->assertSame(0, $this->comandos->chamadas);
    }

    public function test_rede_propria_desligada_usa_ip_da_rede(): void
    {
        $this->comandos->saida = '1.1.1.1 via 10.0.0.1 dev wlan0 src 10.0.0.229 uid 33';
        $this->assertSame('http://10.0.0.229:8080', $this->servico()->endereco());
    }

    public function test_sem_src_nao_tem_endereco(): void
    {
        $this->comandos->saida = 'default via 10.0.0.1';
        $this->assertNull($this->servico()->endereco());
    }

    public function test_comando_com_erro_nao_tem_endereco(): void
    {
        $this->comandos->codigo = 1;
        $this->comandos->saida = 'src 10.0.0.229';
        $this->assertNull($this->servico()->endereco());
    }

    public function test_fora_do_linux_nao_executa_comando(): void
    {
        $this->assertNull($this->servico('Windows')->endereco());
        $this->assertSame(0, $this->comandos->chamadas);
    }

    public function test_consulta_a_rota_so_uma_vez_enquanto_o_cache_vale(): void
    {
        $this->comandos->saida = 'default via 10.0.0.1 dev wlan0 src 10.0.0.229 uid 33';
        $servico = $this->servico();

        $servico->endereco();
        $servico->endereco();

        $this->assertSame(1, $this->comandos->chamadas);
    }
}

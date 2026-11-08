<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Connection\HotspotCredentials;
use App\Support\Connection\OfflineModeService;
use Illuminate\Support\Facades\File;
use Tests\Support\FakeAccessPoint;
use Tests\TestCase;

/**
 * A situação do ponto de acesso fica guardada por pouco tempo, para as páginas não perguntarem
 * à rede a cada carregamento. Ligar e desligar precisam mostrar o novo estado logo em seguida.
 */
final class OfflineModeServiceTest extends TestCase
{
    private FakeAccessPoint $ponto;

    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ponto = new FakeAccessPoint();
        $this->pasta = sys_get_temp_dir() . '/cp-offline-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pasta);
        parent::tearDown();
    }

    private function servico(): OfflineModeService
    {
        return new OfflineModeService($this->ponto, new HotspotCredentials($this->pasta . '/rede.senha'));
    }

    public function test_consulta_o_ponto_de_acesso_so_uma_vez_enquanto_o_cache_vale(): void
    {
        $this->ponto->ligado = true;
        $servico = $this->servico();

        $servico->status();
        $servico->status();

        $this->assertSame(1, $this->ponto->consultas);
    }

    public function test_ligar_faz_a_proxima_consulta_mostrar_a_rede_ligada(): void
    {
        $servico = $this->servico();
        $this->assertFalse($servico->status()['ligado']);

        $servico->ligar();

        $this->assertTrue($servico->status()['ligado']);
    }

    public function test_desligar_faz_a_proxima_consulta_mostrar_a_rede_desligada(): void
    {
        $this->ponto->ligado = true;
        $servico = $this->servico();
        $this->assertTrue($servico->status()['ligado']);

        $servico->desligar();

        $this->assertFalse($servico->status()['ligado']);
    }
}

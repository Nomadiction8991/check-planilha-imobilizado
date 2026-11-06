<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\CommandRunnerInterface;
use App\Support\Connection\OfflineModeService;
use App\Support\Modo\EstadoModo;
use App\Support\Modo\RedeAutomatica;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RedeAutomaticaTest extends TestCase
{
    private string $arquivo;
    private ComandosRedeFalsos $comandos;
    private EstadoModo $estadoModo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arquivo = sys_get_temp_dir().'/rede-automatica-'.uniqid().'/rede.json';
        $this->estadoModo = new EstadoModo(dirname($this->arquivo).'/modo.json');
        $this->app->instance(EstadoModo::class, $this->estadoModo);
        $this->comandos = new ComandosRedeFalsos();
        $this->app->instance(CommandRunnerInterface::class, $this->comandos);
        config(['modo_igreja.ativo' => true]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(dirname($this->arquivo));
        parent::tearDown();
    }

    private function rede(string $sistema = 'Linux'): RedeAutomatica
    {
        return new RedeAutomatica($this->comandos, app(OfflineModeService::class), $this->arquivo, $sistema);
    }

    private function estado(int $falhas, bool $ligada): void
    {
        File::ensureDirectoryExists(dirname($this->arquivo));
        file_put_contents($this->arquivo, json_encode(['falhas' => $falhas, 'ligada_pelo_sistema' => $ligada], JSON_THROW_ON_ERROR));
    }

    public function test_liga_apos_tres_verificacoes_sem_rota(): void
    {
        $rede = $this->rede();
        self::assertSame('nada', $rede->verificar());
        self::assertSame('nada', $rede->verificar());
        self::assertSame('ligar', $rede->verificar());
        self::assertTrue(json_decode((string) file_get_contents($this->arquivo), true)['ligada_pelo_sistema']);
    }

    public function test_rota_qualquer_zera_contador(): void
    {
        $this->estado(2, false);
        $this->comandos->rota = 'default via 192.168.1.1 dev wlan0';
        self::assertSame('nada', $this->rede()->verificar());
        self::assertSame(0, json_decode((string) file_get_contents($this->arquivo), true)['falhas']);
    }

    public function test_ponto_ligado_por_alguem_nao_desliga_com_cabo(): void
    {
        $this->comandos->ligado = true;
        $this->comandos->rota = 'default via 192.168.1.1 dev eth0';
        self::assertSame('nada', $this->rede()->verificar());
        self::assertTrue($this->comandos->ligado);
    }

    public function test_ponto_ligado_pelo_sistema_nao_desliga_com_cabo_e_servidor_offline(): void
    {
        $this->estadoModo->registrarResultado(false);
        $this->estadoModo->registrarResultado(false);
        $this->estado(0, true);
        $this->comandos->ligado = true;
        $this->comandos->rota = 'default via 192.168.1.1 dev eth0';
        self::assertSame('nada', $this->rede()->verificar());
        self::assertTrue($this->comandos->ligado);
        self::assertTrue(json_decode((string) file_get_contents($this->arquivo), true)['ligada_pelo_sistema']);
    }

    public function test_ponto_ligado_pelo_sistema_desliga_com_cabo_e_servidor_online(): void
    {
        $this->estado(0, true);
        $this->comandos->ligado = true;
        $this->comandos->rota = 'default via 192.168.1.1 dev eth0';
        self::assertSame('desligar', $this->rede()->verificar());
        self::assertFalse($this->comandos->ligado);
        self::assertFalse(json_decode((string) file_get_contents($this->arquivo), true)['ligada_pelo_sistema']);
    }

    public function test_ponto_ligado_pelo_sistema_nao_desliga_com_rota_wifi(): void
    {
        $this->estado(0, true);
        $this->comandos->ligado = true;
        $this->comandos->rota = 'default dev wlan0';
        self::assertSame('nada', $this->rede()->verificar());
        self::assertTrue($this->comandos->ligado);
    }

    public function test_ponto_desligado_pelo_botao_limpa_marcacao(): void
    {
        $this->estado(3, true);
        self::assertSame('nada', $this->rede()->verificar());
        self::assertFalse(json_decode((string) file_get_contents($this->arquivo), true)['ligada_pelo_sistema']);
    }

    public function test_windows_nao_executa_comandos(): void
    {
        self::assertSame('nada', $this->rede('Windows')->verificar());
        self::assertSame([], $this->comandos->chamadas);
    }

    public function test_comando_desativado_nao_executa_comandos_de_rede(): void
    {
        config(['modo_igreja.ativo' => false]);
        $this->app->instance(CommandRunnerInterface::class, $this->comandos);
        $this->artisan('modo-igreja:rede-propria')->assertSuccessful();
        self::assertSame([], $this->comandos->chamadas);
    }

    public function test_estado_sobrevive_a_duas_instancias(): void
    {
        self::assertSame('nada', $this->rede()->verificar());
        self::assertSame('nada', $this->rede()->verificar());
        self::assertSame('ligar', $this->rede()->verificar());
    }
}

class ComandosRedeFalsos implements CommandRunnerInterface
{
    public array $chamadas = [];
    public string $rota = '';
    public bool $ligado = false;

    public function executar(array $comando, array $ambiente = []): array
    {
        $this->chamadas[] = $comando;
        if ($comando === ['ip', '-4', 'route', 'show', 'default']) {
            return ['codigo' => 0, 'saida' => $this->rota];
        }
        if ($comando === ['nmcli', '-t', '-f', 'DEVICE,TYPE', 'device']) {
            return ['codigo' => 0, 'saida' => "wlan0:wifi\neth0:ethernet"];
        }
        if ($comando === ['nmcli', '-t', '-f', 'NAME', 'connection', 'show', '--active']) {
            return ['codigo' => 0, 'saida' => $this->ligado ? 'CheckPlanilha-Igreja' : ''];
        }
        if (in_array('hotspot', $comando, true)) {
            $this->ligado = true;
        }
        if (in_array('down', $comando, true)) {
            $this->ligado = false;
        }
        return ['codigo' => 0, 'saida' => ''];
    }
}

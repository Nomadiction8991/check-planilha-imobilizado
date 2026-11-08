<?php

declare(strict_types=1);

namespace Tests\Feature\Conexao;

use App\Contracts\AccessPointInterface;
use App\Contracts\CommandRunnerInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Support\Connection\HotspotCredentials;
use Mockery\MockInterface;
use Tests\Support\FakeAccessPoint;
use Tests\Support\FakeNetworkCommandRunner;
use Tests\TestCase;

/**
 * Tela "Conexão": status, QR codes, erro sem permissão e ligar/desligar o ponto de acesso.
 */
class ConexaoTest extends TestCase
{
    private FakeAccessPoint $pontoDeAcesso;
    private FakeNetworkCommandRunner $comandos;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'modo_igreja.ativo' => true,
            'modo_igreja.rede_nome' => 'CheckPlanilha',
            'modo_igreja.porta' => 8080,
        ]);

        $this->pontoDeAcesso = new FakeAccessPoint();
        $this->comandos = new FakeNetworkCommandRunner();
        $this->app->instance(CommandRunnerInterface::class, $this->comandos);
        $this->app->instance(AccessPointInterface::class, $this->pontoDeAcesso);
        $this->app->instance(HotspotCredentials::class, new HotspotCredentials(
            sys_get_temp_dir() . '/cp-conexao-' . uniqid() . '/rede.senha',
        ));

        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 1,
                'nome' => 'Administrador',
                'email' => 'admin@teste.local',
                'comum_id' => null,
                'administracao_id' => null,
                'is_admin' => true,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn(null);
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('availableAdministrations')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });

        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([]);
            $mock->shouldReceive('can')->andReturnTrue();
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });
    }

    public function test_tela_nao_existe_fora_do_modo_igreja(): void
    {
        config(['modo_igreja.ativo' => false]);

        $this->comoAdministrador()->get('/conexao')->assertNotFound();
    }

    public function test_quem_nao_e_administrador_nao_ve_a_tela(): void
    {
        $this->comoVisitante()->get('/conexao')->assertRedirect(route('migration.dashboard'));
    }

    public function test_desligado_mostra_o_botao_para_ativar_e_nenhum_qr_code(): void
    {
        $this->comoAdministrador()->get('/conexao')
            ->assertOk()
            ->assertSee('Ativar modo offline')
            ->assertDontSee('<svg', false);
    }

    public function test_ligado_mostra_os_dois_qr_codes_e_o_endereco_do_sistema(): void
    {
        $this->pontoDeAcesso->ligado = true;

        $this->comoAdministrador()->get('/conexao')
            ->assertOk()
            ->assertSee('CheckPlanilha')
            ->assertSee('http://10.42.0.1:8080')
            ->assertSee('Desligar modo offline');

        $this->assertSame(2, substr_count($this->comoAdministrador()->get('/conexao')->getContent(), '<svg'));
    }

    public function test_desligado_mostra_endereco_da_rede_existente(): void
    {
        $this->comandos->saida = 'src 10.0.0.229';
        $this->comoAdministrador()->get('/conexao')->assertOk()
            ->assertSee('Endereço para digitar no navegador do celular:')
            ->assertSee('http://10.0.0.229:8080');
    }

    public function test_ativar_liga_o_ponto_de_acesso_e_volta_com_aviso(): void
    {
        $this->comoAdministrador()->post('/conexao/ligar')
            ->assertRedirect(route('migration.conexao.index'))
            ->assertSessionHas('status');

        $this->assertTrue($this->pontoDeAcesso->ligado);
    }

    public function test_erro_ao_ativar_aparece_na_tela_com_a_mensagem_clara(): void
    {
        $this->pontoDeAcesso->erroAoLigar = 'Nenhum adaptador Wi-Fi foi encontrado neste computador.';

        $this->comoAdministrador()
            ->followingRedirects()
            ->post('/conexao/ligar')
            ->assertOk()
            ->assertSee('Nenhum adaptador Wi-Fi foi encontrado', false);

        $this->assertFalse($this->pontoDeAcesso->ligado);
    }

    public function test_desligar_derruba_o_ponto_de_acesso(): void
    {
        $this->pontoDeAcesso->ligado = true;

        $this->comoAdministrador()->post('/conexao/desligar')
            ->assertRedirect(route('migration.conexao.index'));

        $this->assertFalse($this->pontoDeAcesso->ligado);
    }

    public function test_quem_nao_e_administrador_nao_consegue_ligar(): void
    {
        $this->comoVisitante()->post('/conexao/ligar')->assertRedirect(route('migration.dashboard'));

        $this->assertFalse($this->pontoDeAcesso->ligado);
    }

    public function test_cabecalho_mostra_que_e_copia_local(): void
    {
        $this->comoAdministrador()->get('/conexao')->assertSee('Cópia local');
    }

    private function comoAdministrador(): static
    {
        return $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 1,
            'usuario_nome' => 'Administrador',
            'usuario_email' => 'admin@teste.local',
            'is_admin' => true,
        ]);
    }

    private function comoVisitante(): static
    {
        return $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 7,
            'usuario_nome' => 'Visitante',
            'usuario_email' => 'visitante@teste.local',
            'is_admin' => false,
        ]);
    }
}

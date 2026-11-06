<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Modo\EstadoModo;
use App\Support\Modo\VerificadorDeServidor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModoIgrejaEstadoTest extends TestCase
{
    private string $arquivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arquivo = sys_get_temp_dir().'/estado-modo-'.uniqid().'/estado.json';
        $this->app->instance(EstadoModo::class, new EstadoModo($this->arquivo));
        config(['modo_igreja.ativo' => true, 'modo_igreja.servidor_url' => 'https://usuario:senha@servidor.test']);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        if (is_file($this->arquivo)) {
            unlink($this->arquivo);
            unlink($this->arquivo.'.lock');
            rmdir(dirname($this->arquivo));
        }
        parent::tearDown();
    }

    public function test_estado_modo_aplica_histerese_e_persiste(): void
    {
        $estado = new EstadoModo($this->arquivo);
        self::assertTrue($estado->estaOnline());
        $estado->registrarResultado(false);
        self::assertTrue($estado->estaOnline());
        $estado->registrarResultado(true);
        $estado->registrarResultado(false);
        self::assertTrue($estado->estaOnline());
        $estado->registrarResultado(false);
        self::assertFalse((new EstadoModo($this->arquivo))->estaOnline());
        $estado->registrarResultado(true);
        self::assertFalse($estado->estaOnline());
        $estado->registrarResultado(true);
        self::assertTrue($estado->estaOnline());
    }

    public function test_arquivo_de_estado_corrompido_nao_derruba_o_sistema(): void
    {
        // Um arquivo com defeito não pode impedir o sistema de responder: volta ao padrão (online) e é regravado.
        File::ensureDirectoryExists(dirname($this->arquivo));
        file_put_contents($this->arquivo, '{ isto não é json');

        $estado = new EstadoModo($this->arquivo);
        self::assertTrue($estado->estaOnline());

        $estado->registrarResultado(false);
        self::assertTrue($estado->estaOnline());
        self::assertIsArray(json_decode((string) file_get_contents($this->arquivo), true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_verificador_de_servidor_aceita_apenas_sucesso_e_trata_excecao(): void
    {
        Http::fakeSequence()->push('', 200)->push('', 503)->pushFailedConnection();
        $verificador = new VerificadorDeServidor();
        self::assertTrue($verificador->verificar());
        self::assertFalse($verificador->verificar());
        self::assertFalse($verificador->verificar());
        Http::assertSent(fn ($request) => $request->url() === 'https://usuario:senha@servidor.test/up');
    }

    public function test_verificar_rede_alterna_modo_e_nao_executa_quando_desligado(): void
    {
        Http::fakeSequence()->push('', 503)->push('', 503)->push('', 200)->push('', 200);
        $estado = app(EstadoModo::class);
        $this->artisan('modo-igreja:verificar-rede')->assertSuccessful();
        self::assertTrue($estado->estaOnline());
        $this->artisan('modo-igreja:verificar-rede')->assertSuccessful();
        self::assertFalse($estado->estaOnline());
        $this->artisan('modo-igreja:verificar-rede')->assertSuccessful();
        self::assertFalse($estado->estaOnline());
        $this->artisan('modo-igreja:verificar-rede')->assertSuccessful();
        self::assertTrue($estado->estaOnline());
        config(['modo_igreja.ativo' => false]);
        $this->artisan('modo-igreja:verificar-rede')->assertSuccessful();
        Http::assertSentCount(4);
    }

    public function test_modo_igreja_estado_exige_login_e_oculta_credenciais(): void
    {
        $this->withSession(['_enforce_legacy_auth' => true])->get('/modo-igreja/estado')->assertRedirect();
        $this->withSession(['_enforce_legacy_auth' => true, 'usuario_id' => 1, 'usuario_nome' => 'Usuário'])
            ->get('/modo-igreja/estado')->assertOk()->assertJson(['modo' => 'online', 'servidor' => 'https://servidor.test', 'desde' => null]);
        config(['modo_igreja.ativo' => false]);
        $this->get('/modo-igreja/estado')->assertNotFound();
    }
}

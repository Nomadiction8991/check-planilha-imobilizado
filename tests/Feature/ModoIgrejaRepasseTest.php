<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Modo\EstadoModo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ModoIgrejaRepasseTest extends TestCase
{
    private string $arquivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arquivo = sys_get_temp_dir().'/estado-repasse-'.uniqid().'/estado.json';
        $this->app->instance(EstadoModo::class, new EstadoModo($this->arquivo));
        config(['modo_igreja.ativo' => true, 'modo_igreja.servidor_url' => 'https://servidor.test']);
        Http::preventStrayRequests();
        Route::get('/teste-local', fn () => response('local'))->middleware('web');
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

    public function test_online_repassa_o_pedido_ao_servidor(): void
    {
        Http::fake(['*' => Http::response('servidor', 200, ['Content-Type' => 'text/plain'])]);
        $this->get('/teste-local')->assertOk()->assertSeeText('servidor');
        Http::assertSent(fn ($pedido) => $pedido->url() === 'https://servidor.test/teste-local');
    }

    public function test_modo_desligado_atende_localmente(): void
    {
        Http::fake();
        config(['modo_igreja.ativo' => false]);
        $this->get('/teste-local')->assertOk()->assertSeeText('local');
        Http::assertNothingSent();
    }

    public function test_offline_atende_localmente(): void
    {
        Http::fake();
        $estado = app(EstadoModo::class);
        $estado->registrarResultado(false);
        $estado->registrarResultado(false);
        $this->get('/teste-local')->assertOk()->assertSeeText('local');
        Http::assertNothingSent();
    }

    public function test_rota_de_saude_nao_e_repassada(): void
    {
        Http::fake();
        $this->get('/up')->assertOk();
        Http::assertNothingSent();
    }

    public function test_endereco_vazio_atende_localmente(): void
    {
        Http::fake();
        config(['modo_igreja.servidor_url' => '']);
        $this->get('/teste-local')->assertOk()->assertSeeText('local');
        Http::assertNothingSent();
    }

    public function test_falha_em_leitura_atende_localmente_e_registra_falha(): void
    {
        Http::fakeSequence()->pushFailedConnection();
        $this->get('/teste-local')->assertOk()->assertSeeText('local');
        $estado = new EstadoModo($this->arquivo);
        self::assertTrue($estado->estaOnline());
        $estado->registrarResultado(false);
        self::assertFalse((new EstadoModo($this->arquivo))->estaOnline());
    }

    public function test_falha_em_gravacao_nao_executa_rota_local(): void
    {
        $executou = false;
        Route::post('/teste-local', function () use (&$executou) {
            $executou = true;

            return response('local-escreveu');
        });
        Http::fakeSequence()->pushFailedConnection();
        $this->postJson('/teste-local')->assertStatus(503)->assertJsonPath('message', 'Não consegui falar com o servidor agora. Confira se a ação foi salva antes de tentar de novo.');
        self::assertFalse($executou);
    }

    public function test_cookie_do_servidor_chega_sem_domain_e_secure(): void
    {
        Http::fake(['*' => Http::response('servidor', 200, ['Content-Type' => 'text/plain', 'Set-Cookie' => 'sessao=valor; Domain=servidor.test; Secure; HttpOnly; Path=/'])]);
        $resposta = $this->get('/teste-local')->assertOk();
        $cookie = $resposta->headers->get('Set-Cookie');
        self::assertNotNull($cookie);
        self::assertStringContainsString('sessao=valor', $cookie);
        self::assertStringNotContainsStringIgnoringCase('domain=', $cookie);
        self::assertStringNotContainsStringIgnoringCase('secure', $cookie);
    }

    public function test_redirecionamento_do_servidor_aponta_para_origem_local(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'https://servidor.test/destino'])]);
        $this->get('http://localhost/teste-local')->assertRedirect('http://localhost/destino');
    }
}

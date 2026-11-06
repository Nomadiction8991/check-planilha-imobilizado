<?php

namespace Tests\Unit\Modo;

use App\Support\Modo\RepassePedido;
use App\Support\Modo\ServidorIndisponivelException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class RepassePedidoTest extends TestCase
{
    public function test_encaminha_origin_para_origem_publica_sem_mudar_destino(): void
    {
        Http::fake();
        $pedido = Request::create('http://local.test/', 'GET', [], [], [], ['HTTP_ORIGIN' => 'http://local.test', 'HTTP_REFERER' => 'http://local.test/pagina']);
        (new RepassePedido('http://servidor.test:8080', 'https://publico.test/'))->enviar($pedido);
        Http::assertSent(fn ($r) => $r->url() === 'http://servidor.test:8080/' && $r->hasHeader('Origin', 'https://publico.test') && $r->hasHeader('Referer', 'https://publico.test/pagina'));
    }

    public function test_sem_origem_publica_usa_origem_de_acesso(): void
    {
        Http::fake();
        $pedido = Request::create('http://local.test/', 'GET', [], [], [], ['HTTP_ORIGIN' => 'http://local.test']);
        (new RepassePedido('https://servidor.test', ''))->enviar($pedido);
        Http::assertSent(fn ($r) => $r->hasHeader('Origin', 'https://servidor.test'));
    }

    public function test_fecha_arquivo_multipart_apos_envio(): void
    {
        $arquivo = UploadedFile::fake()->createWithContent('planilha.csv', 'conteudo');
        $caminho = $arquivo->getRealPath();
        $abertos = [];
        $anteriores = get_resources('stream');
        Http::fake(function () use ($caminho, &$abertos, $anteriores) {
            foreach (get_resources('stream') as $recurso) {
                if (! in_array($recurso, $anteriores, true) && (stream_get_meta_data($recurso)['uri'] ?? null) === $caminho) {
                    $abertos[] = $recurso;
                }
            }

            return Http::response();
        });
        $pedido = Request::create('http://local.test/', 'POST', [], [], ['arquivo' => $arquivo], ['CONTENT_TYPE' => 'multipart/form-data']);
        (new RepassePedido('https://servidor.test'))->enviar($pedido);
        self::assertNotEmpty($abertos);
        foreach ($abertos as $recurso) {
            self::assertFalse(is_resource($recurso));
        }
    }

    public function test_host_enviado_e_o_do_servidor(): void
    {
        Http::fake();
        (new RepassePedido('https://servidor.test'))->enviar(Request::create('http://local.test/'));
        Http::assertSent(fn ($r) => $r->hasHeader('Host', 'servidor.test'));
    }

    public function test_encaminha_protocolo_https_e_host_do_servidor(): void
    {
        Http::fake();
        (new RepassePedido('https://servidor.test'))->enviar(Request::create('http://local.test/'));
        Http::assertSent(fn ($r) => $r->hasHeader('X-Forwarded-Proto', 'https') && $r->hasHeader('X-Forwarded-Host', 'servidor.test'));
    }

    public function test_solicita_conteudo_sem_compressao(): void
    {
        Http::fake();
        (new RepassePedido('https://servidor.test'))->enviar(Request::create('http://local.test/'));
        Http::assertSent(fn ($r) => $r->hasHeader('Accept-Encoding', 'identity'));
    }

    public function test_reescreve_origin_e_referer_locais(): void
    {
        Http::fake();
        $pedido = Request::create('http://local.test/', 'GET', [], [], [], ['HTTP_ORIGIN' => 'http://local.test', 'HTTP_REFERER' => 'http://local.test/pagina']);
        (new RepassePedido('https://servidor.test'))->enviar($pedido);
        Http::assertSent(fn ($r) => $r->hasHeader('Origin', 'https://servidor.test') && $r->hasHeader('Referer', 'https://servidor.test/pagina'));
    }

    public function test_recalcula_tamanho_do_texto_reescrito(): void
    {
        Http::fake(['*' => Http::response('https://servidor.test/a', 200, ['Content-Type' => 'text/html', 'Content-Length' => '23'])]);
        $r = (new RepassePedido('https://servidor.test'))->enviar(Request::create('http://local.test/'));
        self::assertSame((string) strlen((string) $r->getContent()), $r->headers->get('Content-Length'));
    }

    public function test_devolve_redirecionamento_sem_seguir(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'https://servidor.test/proximo'])]);
        $r = (new RepassePedido('https://servidor.test'))->enviar(Request::create('http://local.test/'));
        self::assertSame(302, $r->getStatusCode());
        Http::assertSentCount(1);
    }

    public function test_envia_campos_aninhados_no_multipart(): void
    {
        Http::fake();
        $pedido = Request::create('http://local.test/', 'POST', ['ids' => ['1', '2']], [], [], ['CONTENT_TYPE' => 'multipart/form-data; boundary=antigo']);
        (new RepassePedido('https://servidor.test'))->enviar($pedido);
        Http::assertSent(fn ($r) => str_contains($r->body(), 'name="ids[0]"') && str_contains($r->body(), 'name="ids[1]"'));
    }

    public function test_preserva_disposicao_e_bytes_do_download(): void
    {
        $bytes = "\x00\xffhttps://servidor.test";
        Http::fake(['*' => Http::response($bytes, 200, ['Content-Type' => 'application/octet-stream', 'Content-Disposition' => 'attachment; filename="arquivo.bin"'])]);
        $r = (new RepassePedido('https://servidor.test'))->enviar(Request::create('http://local.test/'));
        self::assertSame('attachment; filename="arquivo.bin"', $r->headers->get('Content-Disposition'));
        ob_start();
        $r->sendContent();
        self::assertSame($bytes, ob_get_clean());
    }

    public function test_erro_de_programacao_nao_e_convertido(): void
    {
        Http::fake(fn () => throw new \RuntimeException('erro de programação'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('erro de programação');
        (new RepassePedido('https://servidor.test'))->enviar(Request::create('http://local.test/'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_repassa_get_e_reescreve_resposta(): void
    {
        Http::fake(['*' => Http::response('<a href="https://servidor.test/a">', 200, ['Content-Type' => 'text/html', 'Set-Cookie' => ['a=b; Domain=servidor.test; Secure', 'c=d; SameSite=None'], 'Location' => 'https://servidor.test/proximo'])]);
        $pedido = Request::create('http://local.test/caminho?a=1', 'GET', [], [], [], ['HTTP_COOKIE' => 'sessao=abc']);
        $resposta = (new RepassePedido('https://servidor.test/'))->enviar($pedido);
        self::assertSame(200, $resposta->getStatusCode());
        self::assertStringContainsString('http://local.test/a', (string) $resposta->getContent());
        self::assertSame('http://local.test/proximo', $resposta->headers->get('Location'));
        self::assertCount(2, $resposta->headers->getCookies());
        self::assertStringNotContainsString('secure', strtolower((string) $resposta->headers->getCookies()[0]));
        Http::assertSent(fn ($r) => $r->url() === 'https://servidor.test/caminho?a=1' && $r->hasHeader('Cookie', 'sessao=abc') && $r->hasHeader('X-Forwarded-Proto', 'https') && $r->hasHeader('X-Forwarded-Host', 'servidor.test') && $r->hasHeader('Accept-Encoding', 'identity') && ! $r->hasHeader('Host', 'local.test'));
    }

    public function test_post_e_redirecionamento(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => '/proximo'])]);
        $pedido = Request::create('http://local.test/envio', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], 'a=1&b=2');
        self::assertSame(302, (new RepassePedido('https://servidor.test'))->enviar($pedido)->getStatusCode());
        Http::assertSent(fn ($r) => $r->body() === 'a=1&b=2' && $r->hasHeader('Content-Type', 'application/x-www-form-urlencoded'));
    }

    public function test_multipart_e_download_binario(): void
    {
        $corpoEnviado = '';
        Http::fake(function ($r) use (&$corpoEnviado) {
            $corpoEnviado = $r->body();

            return Http::response("\x00\xffbinario", 200, ['Content-Type' => 'application/octet-stream', 'Content-Length' => '9']);
        });
        $arquivo = UploadedFile::fake()->createWithContent('planilha.csv', 'conteudo');
        $pedido = Request::create('http://local.test/envio', 'POST', ['campo' => 'valor'], [], ['arquivo' => $arquivo], ['CONTENT_TYPE' => 'multipart/form-data; boundary=antigo']);
        $resposta = (new RepassePedido('https://servidor.test'))->enviar($pedido);
        self::assertInstanceOf(StreamedResponse::class, $resposta);
        ob_start();
        $resposta->sendContent();
        self::assertSame("\x00\xffbinario", ob_get_clean());
        Http::assertSentCount(1);
        self::assertStringContainsString('planilha.csv', $corpoEnviado);
        self::assertStringContainsString('conteudo', $corpoEnviado);
    }

    public function test_erro_de_conexao_vira_excecao_de_servidor(): void
    {
        Http::fake(fn () => throw new ConnectionException('falha'));
        $this->expectException(ServidorIndisponivelException::class);
        (new RepassePedido('https://servidor.test'))->enviar(Request::create('http://local.test/'));
    }
}

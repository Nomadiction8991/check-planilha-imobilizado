<?php

namespace Tests\Unit\Modo;

use App\Support\Modo\ReescritorDeResposta;
use PHPUnit\Framework\TestCase;

class ReescritorDeRespostaTest extends TestCase
{
    public function test_remove_domain_e_secure_do_cookie(): void
    {
        $h = (new ReescritorDeResposta('https://servidor.test', 'http://local.test'))->cabecalhos(['Set-Cookie' => ['a=b; Domain=servidor.test; Secure; Path=/']]);
        self::assertSame(['a=b; Path=/'], $h['Set-Cookie']);
    }

    public function test_troca_samesite_none_por_lax(): void
    {
        $h = (new ReescritorDeResposta('https://servidor.test', 'http://local.test'))->cabecalhos(['Set-Cookie' => ['a=b; SameSite=None']]);
        self::assertSame(['a=b; SameSite=Lax'], $h['Set-Cookie']);
    }

    public function test_remove_diretiva_de_atualizacao_da_csp(): void
    {
        $h = (new ReescritorDeResposta('https://servidor.test', 'http://local.test'))->cabecalhos(['Content-Security-Policy' => ["default-src 'self'; upgrade-insecure-requests"]]);
        self::assertSame(["default-src 'self'"], $h['Content-Security-Policy']);
    }

    public function test_remove_csp_quando_fica_vazia(): void
    {
        $h = (new ReescritorDeResposta('https://servidor.test', 'http://local.test'))->cabecalhos(['Content-Security-Policy' => ['upgrade-insecure-requests']]);
        self::assertArrayNotHasKey('Content-Security-Policy', $h);
    }

    public function test_remove_hsts(): void
    {
        $h = (new ReescritorDeResposta('https://servidor.test', 'http://local.test'))->cabecalhos(['Strict-Transport-Security' => ['max-age=1']]);
        self::assertArrayNotHasKey('Strict-Transport-Security', $h);
    }

    public function test_preserva_png_com_url_do_servidor(): void
    {
        $corpo = "\x89PNG\x00https://servidor.test\xff";
        self::assertSame($corpo, (new ReescritorDeResposta('https://servidor.test', 'http://local.test'))->corpo($corpo, 'image/png'));
    }

    public function test_reescreve_origem_escapada_em_json(): void
    {
        $r = new ReescritorDeResposta('https://servidor.test', 'http://local.test');
        self::assertSame('{"url":"http:\/\/local.test\/produtos"}', $r->corpo('{"url":"https:\/\/servidor.test\/produtos"}', 'application/json'));
    }

    public function test_reescreve_cabecalhos_e_corpos(): void
    {
        $r = new ReescritorDeResposta('https://servidor.test/', 'http://local.test/');
        $h = $r->cabecalhos(['Set-Cookie' => ['a=b; Domain=servidor.test; Secure; SameSite=None; Path=/; HttpOnly'], 'Location' => ['https://servidor.test/a', '/relativo'], 'Content-Security-Policy' => ["default-src 'self'; upgrade-insecure-requests"], 'Content-Security-Policy-Report-Only' => ['upgrade-insecure-requests'], 'Strict-Transport-Security' => ['max-age=1']]);
        self::assertSame(['a=b; SameSite=Lax; Path=/; HttpOnly'], $h['Set-Cookie']);
        self::assertSame(['http://local.test/a', '/relativo'], $h['Location']);
        self::assertSame(["default-src 'self'"], $h['Content-Security-Policy']);
        self::assertArrayNotHasKey('Content-Security-Policy-Report-Only', $h);
        self::assertArrayNotHasKey('Strict-Transport-Security', $h);
        foreach (['text/html', 'application/json', 'text/css', 'application/javascript'] as $tipo) {
            self::assertSame('http://local.test/a', $r->corpo('https://servidor.test/a', $tipo));
        }
        self::assertSame('https://servidor.test', $r->corpo('https://servidor.test', 'image/png'));
        self::assertTrue(ReescritorDeResposta::ehTexto('TEXT/HTML; charset=utf-8'));
        self::assertTrue(ReescritorDeResposta::ehTexto('application/problem+json'));
    }
}

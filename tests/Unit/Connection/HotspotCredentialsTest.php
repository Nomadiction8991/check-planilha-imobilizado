<?php

declare(strict_types=1);

namespace Tests\Unit\Connection;

use App\Support\Connection\HotspotCredentials;
use PHPUnit\Framework\TestCase;

class HotspotCredentialsTest extends TestCase
{
    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pasta = sys_get_temp_dir() . '/cp-rede-' . uniqid();
    }

    protected function tearDown(): void
    {
        @unlink($this->pasta . '/rede.senha');
        @rmdir($this->pasta);

        parent::tearDown();
    }

    public function test_cria_uma_senha_de_doze_caracteres_na_primeira_vez(): void
    {
        $senha = (new HotspotCredentials($this->pasta . '/rede.senha'))->senha();

        $this->assertSame(12, strlen($senha));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $senha);
    }

    public function test_devolve_a_mesma_senha_nas_proximas_vezes(): void
    {
        $credenciais = new HotspotCredentials($this->pasta . '/rede.senha');

        $this->assertSame($credenciais->senha(), (new HotspotCredentials($this->pasta . '/rede.senha'))->senha());
    }

    public function test_arquivo_da_senha_fica_so_para_o_dono(): void
    {
        (new HotspotCredentials($this->pasta . '/rede.senha'))->senha();

        $this->assertSame(0600, fileperms($this->pasta . '/rede.senha') & 0777);
    }
}

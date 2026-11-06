<?php

declare(strict_types=1);

namespace Tests\Feature\Installer;

use Tests\TestCase;

/**
 * O comando que cria a configuração do notebook: só na primeira vez, sempre com permissão restrita.
 */
class ConfigureLocalModeCommandTest extends TestCase
{
    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pasta = sys_get_temp_dir() . '/cp-ambiente-' . uniqid();
        mkdir($this->pasta);
    }

    protected function tearDown(): void
    {
        @unlink($this->arquivo());
        @rmdir($this->pasta);

        parent::tearDown();
    }

    public function test_cria_a_configuracao_com_banco_local_e_modo_igreja_ligado(): void
    {
        $this->artisan('modo-igreja:configurar', ['--arquivo' => $this->arquivo()])
            ->assertSuccessful();

        $conteudo = (string) file_get_contents($this->arquivo());
        $this->assertStringContainsString('DB_HOST=127.0.0.1', $conteudo);
        $this->assertStringContainsString('MODO_IGREJA_ATIVO=true', $conteudo);
        $this->assertStringContainsString('APP_KEY=base64:', $conteudo);
        $this->assertSame(0600, fileperms($this->arquivo()) & 0777);
    }

    public function test_gera_senha_do_banco_forte_e_que_muda_a_cada_instalacao(): void
    {
        $this->artisan('modo-igreja:configurar', ['--arquivo' => $this->arquivo()])->assertSuccessful();

        preg_match('/^DB_PASSWORD=([A-Za-z0-9]{24})$/m', (string) file_get_contents($this->arquivo()), $casamento);

        $this->assertCount(2, $casamento, 'A senha do banco precisa ter 24 letras e números.');
    }

    public function test_nao_troca_a_configuracao_quando_o_arquivo_ja_existe(): void
    {
        $this->artisan('modo-igreja:configurar', ['--arquivo' => $this->arquivo()])->assertSuccessful();
        $primeira = (string) file_get_contents($this->arquivo());

        $this->artisan('modo-igreja:configurar', ['--arquivo' => $this->arquivo()])
            ->expectsOutputToContain('Nada foi alterado')
            ->assertSuccessful();

        $this->assertSame($primeira, file_get_contents($this->arquivo()), 'Trocar a chave faria os dados cifrados sumirem.');
    }

    public function test_exige_o_caminho_do_arquivo(): void
    {
        $this->artisan('modo-igreja:configurar')
            ->expectsOutputToContain('--arquivo')
            ->assertFailed();
    }

    public function test_com_socket_grava_o_caminho_do_socket_para_o_sistema_nao_precisar_de_porta(): void
    {
        $socket = $this->pasta . '/mysql.sock';

        $this->artisan('modo-igreja:configurar', ['--arquivo' => $this->arquivo(), '--socket' => $socket])
            ->assertSuccessful();

        $this->assertStringContainsString('DB_SOCKET=' . $socket, (string) file_get_contents($this->arquivo()));
    }

    public function test_sem_socket_nao_grava_a_variavel_do_socket(): void
    {
        $this->artisan('modo-igreja:configurar', ['--arquivo' => $this->arquivo()])->assertSuccessful();

        $this->assertStringNotContainsString('DB_SOCKET', (string) file_get_contents($this->arquivo()));
    }

    private function arquivo(): string
    {
        return $this->pasta . '/modo-igreja.env';
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Installer;

use App\Support\Installer\LocalEnvWriter;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class LocalEnvWriterTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $sobrescrever
     * @return array<string, mixed>
     */
    private function valores(array $sobrescrever = []): array
    {
        return array_merge([
            'app_key' => 'base64:' . base64_encode(str_repeat('a', 32)),
            'porta' => 8080,
            'banco' => 'check_planilha',
            'usuario' => 'check_planilha',
            'senha' => 'senhaSegura123456',
            'banco_porta' => 3307,
        ], $sobrescrever);
    }

    public function test_nao_aponta_para_o_host_do_docker(): void
    {
        $conteudo = (new LocalEnvWriter())->render($this->valores());

        $this->assertStringContainsString('DB_HOST=127.0.0.1', $conteudo);
        $this->assertStringNotContainsString('DB_HOST=db', $conteudo);
    }

    public function test_cookie_de_sessao_funciona_em_endereco_local_sem_https(): void
    {
        $conteudo = (new LocalEnvWriter())->render($this->valores());

        $this->assertStringContainsString('SESSION_SECURE_COOKIE=false', $conteudo);
    }

    public function test_endereco_do_sistema_usa_a_porta_escolhida(): void
    {
        $conteudo = (new LocalEnvWriter())->render($this->valores(['porta' => 9090]));

        $this->assertStringContainsString('APP_URL=http://localhost:9090', $conteudo);
    }

    public function test_liga_o_modo_igreja(): void
    {
        $conteudo = (new LocalEnvWriter())->render($this->valores());

        $this->assertStringContainsString('MODO_IGREJA_ATIVO=true', $conteudo);
    }

    public function test_grava_o_endereco_padrao_do_servidor_sem_token(): void
    {
        $conteudo = (new LocalEnvWriter())->render($this->valores());

        $this->assertStringContainsString('MODO_IGREJA_SERVIDOR_URL=https://checkplanilha.anvy.com.br', $conteudo);
        $this->assertStringNotContainsString('MODO_IGREJA_APARELHO_TOKEN', $conteudo);
    }

    public function test_grava_o_endereco_do_servidor_configurado(): void
    {
        config(['modo_igreja.servidor_padrao' => 'https://servidor.exemplo.com.br']);

        $conteudo = (new LocalEnvWriter())->render($this->valores());

        $this->assertStringContainsString('MODO_IGREJA_SERVIDOR_URL=https://servidor.exemplo.com.br', $conteudo);
    }

    public function test_valor_com_espaco_fica_entre_aspas_simples(): void
    {
        $conteudo = (new LocalEnvWriter())->render($this->valores(['senha' => 'senha com espaço 1']));

        $this->assertStringContainsString("DB_PASSWORD='senha com espaço 1'", $conteudo);
    }

    public function test_recusa_valor_com_aspas_simples(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new LocalEnvWriter())->render($this->valores(['senha' => "it's"]));
    }

    public function test_gera_arquivo_so_para_o_dono(): void
    {
        $dir = sys_get_temp_dir() . '/cp-env-' . uniqid();
        mkdir($dir);
        $caminho = $dir . '/.env';

        try {
            (new LocalEnvWriter())->write($caminho, $this->valores());

            $this->assertSame(0600, fileperms($caminho) & 0777);
        } finally {
            @unlink($caminho);
            @rmdir($dir);
        }
    }

    public function test_falha_com_mensagem_clara_quando_a_pasta_nao_permite_escrita(): void
    {
        $dir = sys_get_temp_dir() . '/cp-env-ro-' . uniqid();
        mkdir($dir);
        chmod($dir, 0555);

        try {
            $this->expectException(RuntimeException::class);

            (new LocalEnvWriter())->write($dir . '/.env', $this->valores());
        } finally {
            chmod($dir, 0755);
            @rmdir($dir);
        }
    }

    public function test_gera_chave_de_aplicacao_de_32_bytes(): void
    {
        $chave = LocalEnvWriter::gerarChaveDeAplicacao();

        $this->assertStringStartsWith('base64:', $chave);
        $this->assertSame(32, strlen((string) base64_decode(substr($chave, 7), true)));
    }
}

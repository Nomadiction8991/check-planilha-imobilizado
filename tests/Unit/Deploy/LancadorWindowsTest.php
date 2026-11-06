<?php

declare(strict_types=1);

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;

/**
 * Confere o lançador do Windows pelo texto. A execução real em PowerShell é validada
 * em separado (pwsh -File ... -Simular), porque o contêiner de testes não tem PowerShell.
 */
class LancadorWindowsTest extends TestCase
{
    private const LANCADOR = __DIR__ . '/../../../deploy/pacotes/windows/lancador.ps1';

    private string $conteudo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conteudo = (string) file_get_contents(self::LANCADOR);
    }

    public function test_tem_o_modo_de_simulacao_usado_pelos_testes(): void
    {
        $this->assertStringContainsString('[switch]$Simular', $this->conteudo);
    }

    public function test_banco_local_so_escuta_no_proprio_computador_e_nao_replica(): void
    {
        $this->assertStringContainsString("'--bind-address=127.0.0.1'", $this->conteudo);
        $this->assertStringContainsString("'--skip-log-bin'", $this->conteudo, 'Sem isso, os gatilhos pedem SUPER (erro 1419).');
    }

    public function test_segue_a_mesma_sequencia_do_lancador_do_linux(): void
    {
        $this->assertStringContainsString("'modo-igreja:configurar'", $this->conteudo);
        $this->assertStringContainsString("'migrate', '--force'", $this->conteudo);
        $this->assertStringContainsString("'sincronizacao:gatilhos'", $this->conteudo);
        $this->assertStringContainsString('0.0.0.0:$porta', $this->conteudo);
        $this->assertStringContainsString("'schedule:run'", $this->conteudo);
    }

    public function test_senha_do_banco_nao_vai_para_a_linha_de_comando_fixa(): void
    {
        $this->assertStringNotContainsString('spike', $this->conteudo);
        $this->assertStringContainsString('$env:DB_PASSWORD', $this->conteudo, 'A senha vem da configuração do notebook.');
    }

    public function test_aponta_os_certificados_do_pacote_para_o_https(): void
    {
        $this->assertStringContainsString("'php\\cacert.pem'", $this->conteudo);
        $this->assertStringContainsString('$env:SSL_CERT_FILE', $this->conteudo);
    }

    public function test_avisa_que_ainda_nao_foi_testado_no_windows(): void
    {
        $this->assertStringContainsString('SEM TESTE REAL NO WINDOWS', $this->conteudo, 'O aviso de pendência precisa estar no arquivo.');
    }
}

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

    public function test_inicializa_o_banco_com_senha_inicial_e_nunca_sem_senha(): void
    {
        // Sem senha, qualquer usuário do computador entra como administrador do banco.
        $this->assertStringContainsString("'--initialize'", $this->conteudo);
        $this->assertStringNotContainsString('--initialize-insecure', $this->conteudo);
        $this->assertStringContainsString('temporary password is generated for root@localhost', $this->conteudo, 'A senha inicial é lida do registro do MySQL.');
    }

    public function test_troca_a_senha_inicial_por_uma_senha_gerada_com_gerador_seguro(): void
    {
        $this->assertStringContainsString('function Gerar-Senha', $this->conteudo);
        $this->assertStringContainsString('RandomNumberGenerator', $this->conteudo);
        $this->assertStringContainsString("ALTER USER 'root'@'localhost' IDENTIFIED BY", $this->conteudo);
        // A senha inicial vem expirada: sem essa opção, o cliente recusa qualquer comando.
        $this->assertStringContainsString("'--connect-expired-password'", $this->conteudo);
    }

    public function test_senhas_nao_vao_para_a_linha_de_comando(): void
    {
        $this->assertStringNotContainsString("'-uroot'", $this->conteudo);
        $this->assertStringNotContainsString("'-e'", $this->conteudo, 'O SQL, com as senhas, entra pela entrada padrão.');
        $this->assertStringContainsString('$Sql | & $Programa @Argumentos', $this->conteudo);
        $this->assertStringContainsString('--defaults-extra-file=$Acesso', $this->conteudo);
    }

    public function test_arquivos_de_acesso_ficam_protegidos_antes_de_receber_a_senha(): void
    {
        $this->assertStringContainsString('icacls.exe', $this->conteudo);
        $this->assertStringContainsString('/inheritance:r', $this->conteudo);
        $this->assertStringContainsString('/grant:r', $this->conteudo);
        $this->assertLessThan(
            strpos($this->conteudo, 'WriteAllText'),
            strpos($this->conteudo, 'Restringir-Arquivo $Caminho'),
            'A proteção precisa vir antes da escrita da senha.',
        );
    }

    public function test_aplicacao_e_encerramento_usam_o_acesso_do_administrador(): void
    {
        $this->assertStringContainsString('Executar-Sql $mysql (Argumentos-Banco $arquivoAdmin $dbPorta)', $this->conteudo);
        $this->assertStringContainsString("Argumentos-Banco \$arquivoAdmin \$dbPorta @('shutdown')", $this->conteudo);
    }

    public function test_arquivos_temporarios_da_primeira_configuracao_somem_ao_fim(): void
    {
        $this->assertStringContainsString('Remove-Item -LiteralPath $arquivoInicial', $this->conteudo);
        $this->assertStringContainsString('Remove-Item -LiteralPath $logInicializacao', $this->conteudo);
    }

    public function test_nao_reinicializa_o_banco_quando_o_acesso_do_administrador_existe(): void
    {
        $this->assertStringContainsString('$primeiraVez = -not (Test-Path -LiteralPath $arquivoAdmin)', $this->conteudo);
        // Sem o acesso, uma pasta de banco com dados não pode ser recriada: a pessoa precisa de suporte.
        $this->assertStringContainsString('Não apague a pasta do banco', $this->conteudo);
    }

    public function test_cria_a_pasta_de_dados_antes_de_gerar_a_configuracao(): void
    {
        // O gerador da configuração exige a pasta pronta: sem ela, a primeira execução falha.
        $pasta = strpos($this->conteudo, 'New-Item -ItemType Directory -Path $dados');
        $configurar = strpos($this->conteudo, "'modo-igreja:configurar'");

        $this->assertNotFalse($pasta, 'O lançador precisa criar a pasta de dados.');
        $this->assertLessThan($configurar, $pasta, 'A pasta precisa existir antes de configurar.');
    }

    public function test_inicializacao_do_banco_tem_limite_de_tempo_e_encerra_o_processo_travado(): void
    {
        // Sem limite, uma inicialização travada deixaria a primeira execução parada para sempre.
        $this->assertStringContainsString('WaitForExit($Limite * 1000)', $this->conteudo);
        $this->assertStringContainsString('Stop-Process -Id $processo.Id -Force', $this->conteudo);
        $this->assertStringContainsString('não terminou em $Limite segundos', $this->conteudo);
    }

    public function test_inicializacao_do_banco_trata_codigo_de_erro_do_programa(): void
    {
        $this->assertStringContainsString('ExitCode -ne 0', $this->conteudo);
        $this->assertStringContainsString('falhou (código', $this->conteudo);
    }

    public function test_limite_da_inicializacao_pode_ser_ajustado_pela_linha_de_comando(): void
    {
        $this->assertStringContainsString('[int]$LimiteInicializacaoSegundos = 300', $this->conteudo);
    }

    public function test_avisa_que_ainda_nao_foi_testado_no_windows(): void
    {
        $this->assertStringContainsString('SEM TESTE REAL NO WINDOWS', $this->conteudo, 'O aviso de pendência precisa estar no arquivo.');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Lançador do Linux: confere a sintaxe e os comandos que ele montaria, sem subir nada.
 */
class LancadorLinuxTest extends TestCase
{
    private const LANCADOR = __DIR__ . '/../../../deploy/pacotes/linux/lancador.sh';

    public function test_script_tem_sintaxe_valida(): void
    {
        $processo = new Process(['bash', '-n', self::LANCADOR]);
        $processo->run();

        $this->assertSame(0, $processo->getExitCode(), $processo->getErrorOutput());
    }

    public function test_simulacao_sobe_banco_servidor_e_sincronizacao(): void
    {
        $saida = $this->simular();

        $this->assertStringContainsString('--skip-networking', $saida, 'O banco não pode abrir porta de rede: só o socket dentro da pasta de dados.');
        $this->assertStringNotContainsString('--port=', $saida, 'Com porta de rede aberta, outro usuário do computador tenta entrar por TCP.');
        $this->assertStringNotContainsString('--bind-address', $saida);
        $this->assertStringContainsString('chmod 700 /tmp/cp-dados-inexistentes', $saida, 'A pasta de dados só pode ser lida pelo dono.');
        $this->assertMatchesRegularExpression('/modo-igreja:configurar --arquivo=\S+ --socket=\S+/', $saida, 'O ambiente precisa apontar para o socket.');
        $this->assertStringContainsString('--basedir=/opt/cp-inexistente/mysql', $saida, 'Sem o basedir, o MySQL procura os arquivos no caminho de compilação.');
        $this->assertStringContainsString('--skip-log-bin', $saida, 'Sem o log binário, os gatilhos não pedem o privilégio SUPER (erro 1419).');
        $this->assertStringContainsString('migrate --force', $saida);
        $this->assertStringContainsString('sincronizacao:gatilhos', $saida);
        $this->assertStringContainsString('-S 0.0.0.0:8080 -t public public/index.php', $saida);
        $this->assertStringContainsString('schedule:run', $saida);
    }

    public function test_simulacao_cria_a_configuracao_quando_ela_nao_existe(): void
    {
        $this->assertStringContainsString('modo-igreja:configurar', $this->simular());
    }

    public function test_script_fecha_as_permissoes_dos_arquivos_criados_para_so_o_dono(): void
    {
        // Sem essa linha, arquivos criados pelo lançador (socket, logs, dados) podem ficar legíveis por outros usuários.
        $this->assertStringContainsString('umask 077', (string) file_get_contents(self::LANCADOR));
        $this->assertStringContainsString('chmod 600 "$CP_SOCKET"', (string) file_get_contents(self::LANCADOR), 'O socket precisa de permissão 600.');
    }

    public function test_usuario_do_sistema_e_criado_para_o_host_localhost_do_socket(): void
    {
        // Conexões pelo socket chegam ao MySQL como "localhost". Um usuário criado só para 127.0.0.1 não consegue entrar.
        $conteudo = (string) file_get_contents(self::LANCADOR);

        $this->assertStringContainsString("'\$DB_USERNAME'@'localhost'", $conteudo);
        $this->assertStringNotContainsString("'\$DB_USERNAME'@'127.0.0.1'", $conteudo);
    }

    private function simular(): string
    {
        $processo = new Process(['bash', self::LANCADOR, '--simular'], null, [
            'PATH' => (string) getenv('PATH'),
            'CP_HOME' => '/opt/cp-inexistente',
            'CP_APP' => '/opt/cp-inexistente/app',
            'CP_DADOS' => '/tmp/cp-dados-inexistentes',
        ]);
        $processo->run();

        $this->assertSame(0, $processo->getExitCode(), $processo->getErrorOutput());

        return $processo->getOutput();
    }
}

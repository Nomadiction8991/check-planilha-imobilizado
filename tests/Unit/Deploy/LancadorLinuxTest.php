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

    public function test_servidor_local_atende_mais_de_um_pedido_ao_mesmo_tempo(): void
    {
        // Com um processo só, um pedido lento trava todos os celulares enquanto ele espera.
        $this->assertMatchesRegularExpression(
            '/PHP_CLI_SERVER_WORKERS=\d+ \S+ -S 0\.0\.0\.0:8080 /',
            $this->simular(),
        );
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

    public function test_inicializa_o_banco_com_senha_inicial_e_nunca_sem_senha(): void
    {
        $conteudo = (string) file_get_contents(self::LANCADOR);

        // Sem senha, qualquer usuário do computador entra como administrador do banco.
        $this->assertStringNotContainsString('--initialize-insecure', $conteudo);
        $this->assertStringContainsString('--initialize --basedir=', $conteudo);
        $this->assertStringContainsString('temporary password is generated for root@localhost: ', $conteudo, 'A senha inicial é lida do registro do MySQL.');
    }

    public function test_troca_a_senha_inicial_por_uma_senha_gerada(): void
    {
        $conteudo = (string) file_get_contents(self::LANCADOR);

        $this->assertStringContainsString('gerar_senha()', $conteudo);
        $this->assertStringContainsString('/dev/urandom', $conteudo, 'A senha precisa vir de um gerador seguro.');
        $this->assertStringContainsString("ALTER USER 'root'@'localhost' IDENTIFIED BY '\$senha_admin';", $conteudo);
        // A senha inicial vem expirada: sem essa opção, o cliente recusa qualquer comando.
        $this->assertStringContainsString('--connect-expired-password', $conteudo);
    }

    public function test_senhas_nao_vao_para_a_linha_de_comando(): void
    {
        $conteudo = (string) file_get_contents(self::LANCADOR);

        $this->assertStringNotContainsString('-uroot', $conteudo);
        $this->assertStringNotContainsString(' -e "', $conteudo, 'O SQL, com as senhas, entra pela entrada padrão.');
        $this->assertStringContainsString('printf \'%s\n\' "$sql" | "$@"', $conteudo);
    }

    public function test_acesso_do_administrador_fica_guardado_e_o_temporario_some(): void
    {
        $conteudo = (string) file_get_contents(self::LANCADOR);

        $this->assertStringContainsString('CP_ADMIN="$CP_DADOS/mysql-administrador.cnf"', $conteudo);
        $this->assertStringContainsString('rm -f "$CP_INICIAL"', $conteudo);
        $this->assertStringContainsString('rm -f "$CP_LOG_INICIALIZACAO"', $conteudo);
        $this->assertStringContainsString('chmod 600 "$arquivo"', $conteudo, 'O arquivo de acesso só pode ser lido pelo dono.');
    }

    public function test_nao_reinicializa_o_banco_quando_o_acesso_do_administrador_existe(): void
    {
        $conteudo = (string) file_get_contents(self::LANCADOR);

        $this->assertStringContainsString('if [[ ! -f "$CP_ADMIN" ]]; then', $conteudo);
        // Sem o acesso, uma pasta de banco com dados não pode ser recriada: a pessoa precisa de suporte.
        $this->assertStringContainsString('Não apague a pasta do banco', $conteudo);
    }

    public function test_simulacao_nao_mostra_senhas(): void
    {
        $this->assertStringNotContainsString('IDENTIFIED', $this->simular());
    }

    public function test_porta_ocupada_impede_abertura_do_navegador(): void
    {
        [$codigo, $saida, $abertura] = $this->executarComProgramasFalsos('ocupada');

        $this->assertNotSame(0, $codigo);
        $this->assertStringContainsString('8081', $saida);
        $this->assertStringContainsString('porta', $saida);
        $this->assertSame('', $abertura);
    }

    public function test_servidor_iniciado_abre_o_navegador(): void
    {
        [$codigo, $saida, $abertura] = $this->executarComProgramasFalsos('inicia');

        $this->assertSame(0, $codigo, $saida);
        $this->assertSame("http://localhost:8081\n", $abertura);
    }

    public function test_servidor_que_nao_inicia_impede_abertura_do_navegador(): void
    {
        [$codigo, $saida, $abertura] = $this->executarComProgramasFalsos('falha');

        $this->assertNotSame(0, $codigo);
        $this->assertStringContainsString('servidor', $saida);
        $this->assertSame('', $abertura);
    }

    private function executarComProgramasFalsos(string $cenario): array
    {
        $diretorio = sys_get_temp_dir() . '/lancador-' . bin2hex(random_bytes(8));
        mkdir("$diretorio/bin", 0700, true);
        mkdir("$diretorio/pacote/php/bin", 0700, true);
        mkdir("$diretorio/pacote/mysql/bin", 0700, true);
        mkdir("$diretorio/app", 0700, true);
        mkdir("$diretorio/dados", 0700, true);
        file_put_contents("$diretorio/dados/modo-igreja.env", "MODO_IGREJA_PORTA=8081\n");
        file_put_contents("$diretorio/dados/mysql-administrador.cnf", '');
        file_put_contents("$diretorio/ambiente.sh", "kill() { :; }\n");
        $programas = [
            'bin/ss' => 'if [ "$CENARIO" = ocupada ] || [ -f "$TEMP_TESTE/subiu" ]; then echo "LISTEN 0 128 0.0.0.0:8081 0.0.0.0:*"; fi',
            'bin/xdg-open' => 'printf "%s\n" "$1" > "$TEMP_TESTE/abertura"',
            'bin/sleep' => 'if [ "$1" = 60 ]; then exit 1; fi; /bin/sleep 0.01',
            'pacote/php/bin/php' => 'if [ "$1" = -S ] && [ "$CENARIO" = inicia ]; then touch "$TEMP_TESTE/subiu"; fi',
            'pacote/mysql/bin/mysqld' => ':',
            'pacote/mysql/bin/mysql' => ':',
            'pacote/mysql/bin/mysqladmin' => ':',
        ];
        foreach ($programas as $caminho => $conteudo) {
            file_put_contents("$diretorio/$caminho", "#!/bin/bash\n$conteudo\n");
            chmod("$diretorio/$caminho", 0700);
        }

        try {
            $processo = new Process(['bash', self::LANCADOR], null, [
                'PATH' => "$diretorio/bin:" . getenv('PATH'),
                'CP_HOME' => "$diretorio/pacote",
                'CP_APP' => "$diretorio/app",
                'CP_DADOS' => "$diretorio/dados",
                'TEMP_TESTE' => $diretorio,
                'CENARIO' => $cenario,
                'BASH_ENV' => "$diretorio/ambiente.sh",
            ]);
            $processo->run();

            return [$processo->getExitCode(), $processo->getOutput() . $processo->getErrorOutput(),
                is_file("$diretorio/abertura") ? file_get_contents("$diretorio/abertura") : ''];
        } finally {
            $arquivos = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($diretorio, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($arquivos as $arquivo) {
                $arquivo->isDir() ? rmdir($arquivo->getPathname()) : unlink($arquivo->getPathname());
            }
            rmdir($diretorio);
        }
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

<?php

declare(strict_types=1);

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Script que baixa e confere os binários do pacote: ele tem de recusar arquivo com soma errada.
 */
class PrepararBinariosTest extends TestCase
{
    private const SCRIPT = __DIR__ . '/../../../deploy/pacotes/linux/preparar-binarios.sh';

    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pasta = sys_get_temp_dir() . '/cp-binarios-' . uniqid();
        mkdir($this->pasta . '/cache', 0700, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->pasta));

        parent::tearDown();
    }

    public function test_script_tem_sintaxe_valida(): void
    {
        $processo = new Process(['bash', '-n', self::SCRIPT]);
        $processo->run();

        $this->assertSame(0, $processo->getExitCode(), $processo->getErrorOutput());
    }

    public function test_recusa_arquivo_com_soma_diferente_e_nao_monta_nada(): void
    {
        file_put_contents($this->pasta . '/php-falso.tar.gz', 'conteudo que nao e o PHP oficial');

        $processo = new Process(['bash', self::SCRIPT, $this->pasta . '/destino'], null, [
            'PATH' => (string) getenv('PATH'),
            'HOME' => $this->pasta,
            'CP_CACHE' => $this->pasta . '/cache',
            'CP_PHP_URL' => 'file://' . $this->pasta . '/php-falso.tar.gz',
            'CP_PHP_SHA256' => str_repeat('0', 64),
        ]);
        $processo->run();

        $this->assertNotSame(0, $processo->getExitCode());
        $this->assertStringContainsString('SHA-256 de php-falso.tar.gz não confere', $processo->getErrorOutput());
        $this->assertFileDoesNotExist($this->pasta . '/cache/php-falso.tar.gz', 'O arquivo com soma errada sai do cache.');
        $this->assertDirectoryDoesNotExist($this->pasta . '/destino/php', 'Nada pode ser montado com arquivo não conferido.');
    }

    public function test_poda_do_mysql_mantem_o_componente_que_o_servidor_carrega_na_inicializacao(): void
    {
        // Sem esse componente, o MySQL avisa "Can't open shared library" a cada inicialização.
        $conteudo = (string) file_get_contents(self::SCRIPT);

        $this->assertMatchesRegularExpression(
            '/find "\$destino\/mysql\/lib\/plugin" -type f ! -name auth_socket\.so ! -name component_reference_cache\.so -delete/',
            $conteudo,
        );
    }

    public function test_somas_fixadas_no_script_tem_formato_sha256(): void
    {
        $conteudo = (string) file_get_contents(self::SCRIPT);

        preg_match_all('/SHA256="\$\{CP_[A-Z0-9_]+:-([0-9a-f]+)\}"/', $conteudo, $casamentos);

        $this->assertCount(2, $casamentos[1], 'O PHP e o MySQL precisam ter a soma fixada.');
        foreach ($casamentos[1] as $soma) {
            $this->assertSame(64, strlen($soma));
        }
    }
}

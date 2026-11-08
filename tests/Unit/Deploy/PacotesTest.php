<?php

declare(strict_types=1);

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Pacotes do Linux e do Windows: confere as regras que impedem dependência faltando e
 * arquivo de teste dentro da instalação do cliente.
 */
class PacotesTest extends TestCase
{
    private const BUILD_DEB = __DIR__ . '/../../../deploy/pacotes/linux/build-deb.sh';

    private const PREPARAR_LINUX = __DIR__ . '/../../../deploy/pacotes/linux/preparar-binarios.sh';

    private const PREPARAR_WINDOWS = __DIR__ . '/../../../deploy/pacotes/windows/preparar-binarios-windows.sh';

    public function test_scripts_de_pacote_tem_sintaxe_valida(): void
    {
        foreach ([self::BUILD_DEB, self::PREPARAR_LINUX, self::PREPARAR_WINDOWS] as $script) {
            $processo = new Process(['bash', '-n', $script]);
            $processo->run();

            $this->assertSame(0, $processo->getExitCode(), $script . ': ' . $processo->getErrorOutput());
        }
    }

    public function test_deb_declara_as_bibliotecas_do_sistema_que_o_mysql_precisa(): void
    {
        // A libnuma copiada deste computador exigia uma glibc mais nova que a do Debian 12.
        $this->assertStringContainsString(
            'Depends: libc6 (>= 2.28), libstdc++6, libnuma1, libncurses6',
            (string) file_get_contents(self::BUILD_DEB),
        );
    }

    public function test_pacote_linux_nao_copia_a_libnuma_e_confere_a_glibc(): void
    {
        $conteudo = (string) file_get_contents(self::PREPARAR_LINUX);

        $this->assertStringNotContainsString('libnuma.so.1)', $conteudo);
        $this->assertStringContainsString('GLIBC_2.28', $conteudo, 'Cada programa do pacote precisa rodar em glibc 2.28.');
    }

    public function test_pasta_storage_fica_de_fora_da_instalacao(): void
    {
        $this->assertStringContainsString(':(exclude)storage', (string) file_get_contents(self::BUILD_DEB));
        $this->assertStringContainsString(':(exclude)storage', (string) file_get_contents(self::PREPARAR_WINDOWS));
    }

    public function test_pasta_de_planilhas_nasce_vazia_e_o_pacote_confere_isso(): void
    {
        $this->assertStringContainsString(
            '[[ -z "$(find "$instalacao/app/storage/importacao" -type f)" ]]',
            (string) file_get_contents(self::BUILD_DEB),
        );
        $this->assertStringContainsString('storage/importacao', (string) file_get_contents(self::PREPARAR_WINDOWS));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Compilação do instalador do Windows em contêiner. O roteiro precisa ter sintaxe válida, a imagem
 * precisa trazer o Inno Setup 6.5 ou mais novo (o idioma português exige essa versão) e o roteiro
 * precisa compilar o instalador do repositório. A compilação real roda à parte, porque usa o Wine.
 */
class CompilarInstaladorTest extends TestCase
{
    private const ROTEIRO = __DIR__ . '/../../../deploy/pacotes/windows/compilar/compilar-instalador.sh';

    private const IMAGEM = __DIR__ . '/../../../deploy/pacotes/windows/compilar/Dockerfile';

    public function test_roteiro_tem_sintaxe_valida(): void
    {
        $processo = new Process(['bash', '-n', self::ROTEIRO]);
        $processo->run();

        $this->assertSame(0, $processo->getExitCode(), $processo->getErrorOutput());
    }

    public function test_imagem_instala_o_inno_setup_6_5_ou_mais_novo(): void
    {
        $conteudo = (string) file_get_contents(self::IMAGEM);

        $this->assertMatchesRegularExpression('/innosetup-6\.(5|[6-9])\.\d+\.exe/', $conteudo);
        $this->assertStringContainsString('ISCC.exe', $conteudo);
    }

    public function test_roteiro_compila_o_instalador_do_repositorio_com_a_pasta_do_windows(): void
    {
        $conteudo = (string) file_get_contents(self::ROTEIRO);

        $this->assertStringContainsString('deploy/pacotes/windows/instalador.iss', $conteudo);
        // Sem a pasta do Windows montada, a compilação não tem o que empacotar.
        $this->assertStringContainsString('build/windows/app/artisan', $conteudo);
    }
}

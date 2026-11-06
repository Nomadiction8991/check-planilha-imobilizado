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

        $this->assertStringContainsString('--bind-address=127.0.0.1', $saida, 'O banco precisa ficar só no computador.');
        $this->assertStringContainsString('migrate --force', $saida);
        $this->assertStringContainsString('sincronizacao:gatilhos', $saida);
        $this->assertStringContainsString('-S 0.0.0.0:8080 -t public public/index.php', $saida);
        $this->assertStringContainsString('schedule:run', $saida);
    }

    public function test_simulacao_cria_a_configuracao_quando_ela_nao_existe(): void
    {
        $this->assertStringContainsString('modo-igreja:configurar', $this->simular());
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

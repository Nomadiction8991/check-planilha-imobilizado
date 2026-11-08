<?php

declare(strict_types=1);

namespace Tests\Unit\Connection;

use App\Support\Connection\WindowsAccessPoint;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\FakeCommandRunner;

/**
 * Enquanto a rede dos celulares no Windows não for testada, ela fica desligada: nenhum comando
 * do Windows é chamado, e a ligação é recusada com uma mensagem clara.
 */
class WindowsAccessPointLiberacaoTest extends TestCase
{
    public function test_ligar_sem_liberacao_recusa_e_nao_chama_o_windows(): void
    {
        $executor = new FakeCommandRunner();
        $ponto = new WindowsAccessPoint($executor, sys_get_temp_dir(), liberado: false);

        try {
            $ponto->ligar('CheckPlanilha', 'senha-segura-123');
            $this->fail('Precisava recusar a ligação enquanto o Windows não estiver liberado.');
        } catch (RuntimeException $erro) {
            $this->assertStringContainsString('não foi testado', $erro->getMessage());
        }

        $this->assertCount(0, $executor->chamadas);
    }

    public function test_sem_liberacao_o_ponto_aparece_desligado_sem_consultar_o_windows(): void
    {
        $executor = new FakeCommandRunner();
        $ponto = new WindowsAccessPoint($executor, sys_get_temp_dir(), liberado: false);

        $this->assertFalse($ponto->estaLigado());
        $this->assertNull($ponto->enderecoDaRede());
        $this->assertCount(0, $executor->chamadas);
    }

    public function test_desligar_sem_liberacao_nao_chama_o_windows(): void
    {
        $executor = new FakeCommandRunner();

        (new WindowsAccessPoint($executor, sys_get_temp_dir(), liberado: false))->desligar();

        $this->assertCount(0, $executor->chamadas);
    }
}

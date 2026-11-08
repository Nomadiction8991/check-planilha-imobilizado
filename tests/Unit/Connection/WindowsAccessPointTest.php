<?php

declare(strict_types=1);

namespace Tests\Unit\Connection;

use App\Support\Connection\WindowsAccessPoint;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\FakeCommandRunner;

/**
 * Ponto de acesso no Windows. Estes testes conferem o script gerado e a forma de chamada.
 * Rodar o script de verdade só é possível numa máquina Windows (ainda não testado).
 */
class WindowsAccessPointTest extends TestCase
{
    private const SENHA = 'senha-segura-123';

    public function test_script_le_nome_e_senha_do_ambiente_e_nao_os_contem(): void
    {
        $script = (new WindowsAccessPoint(new FakeCommandRunner(), sys_get_temp_dir()))->scriptLigar();

        $this->assertStringContainsString('$env:CP_REDE_SENHA', $script);
        $this->assertStringContainsString('$env:CP_REDE_NOME', $script);
        $this->assertStringContainsString('StartTetheringAsync', $this->semComentarios($script));
        $this->assertStringNotContainsString(self::SENHA, $script);
    }

    public function test_ligar_passa_a_senha_so_pelo_ambiente_do_processo(): void
    {
        $executor = new FakeCommandRunner();

        (new WindowsAccessPoint($executor, sys_get_temp_dir(), liberado: true))->ligar('CheckPlanilha', self::SENHA);

        $chamada = $executor->chamadas[0];
        $this->assertSame(self::SENHA, $chamada['ambiente']['CP_REDE_SENHA']);
        $this->assertSame('CheckPlanilha', $chamada['ambiente']['CP_REDE_NOME']);
        $this->assertStringNotContainsString(self::SENHA, implode(' ', $chamada['comando']));
    }

    public function test_falha_do_script_nao_mostra_a_senha(): void
    {
        $executor = new FakeCommandRunner([
            'powershell.exe' => ['codigo' => 1, 'saida' => 'Falhou com a chave ' . self::SENHA],
        ]);

        try {
            (new WindowsAccessPoint($executor, sys_get_temp_dir(), liberado: true))->ligar('CheckPlanilha', self::SENHA);
            $this->fail('Precisava falhar quando o script falha.');
        } catch (RuntimeException $erro) {
            $this->assertStringNotContainsString(self::SENHA, $erro->getMessage());
        }
    }

    /**
     * O cabeçalho e o corpo de cada script precisam ficar em linhas separadas. Sem essa quebra,
     * o PowerShell lê a primeira linha do corpo grudada na última do cabeçalho e não roda o script.
     */
    public function test_cabecalho_termina_em_quebra_de_linha_antes_do_corpo_de_cada_script(): void
    {
        $ponto = new WindowsAccessPoint(new FakeCommandRunner(), sys_get_temp_dir());
        $fimDoCabecalho = 'CreateFromConnectionProfile($perfil)' . "\n";

        $this->assertStringContainsString($fimDoCabecalho . '$configuracao = ', $ponto->scriptLigar());
        $this->assertStringContainsString($fimDoCabecalho . '$resultado = ', $this->scriptPrivado('scriptDesligar'));
        $this->assertStringContainsString($fimDoCabecalho . 'if (', $this->scriptPrivado('scriptEstado'));
    }

    private function scriptPrivado(string $metodo): string
    {
        $ponto = new WindowsAccessPoint(new FakeCommandRunner(), sys_get_temp_dir());
        $reflexao = new \ReflectionMethod($ponto, $metodo);

        return (string) $reflexao->invoke($ponto);
    }

    private function semComentarios(string $script): string
    {
        return preg_replace('/^\s*#.*$/m', '', $script) ?? $script;
    }
}

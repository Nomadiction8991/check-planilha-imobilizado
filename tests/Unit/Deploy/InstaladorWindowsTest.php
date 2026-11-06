<?php

declare(strict_types=1);

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;

/**
 * Confere o instalador do Windows pelo texto e pelos arquivos que ele referencia.
 * A compilação real (Inno Setup) só roda numa máquina Windows.
 */
class InstaladorWindowsTest extends TestCase
{
    private const INSTALADOR = __DIR__.'/../../../deploy/pacotes/windows/instalador.iss';

    private string $conteudo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conteudo = (string) file_get_contents(self::INSTALADOR);
    }

    public function test_pastas_do_laravel_ficam_graviveis_pelo_usuario_comum(): void
    {
        // O programa fica em Program Files, onde o usuário comum só lê. O Laravel grava logs, cache e
        // sessões em storage e bootstrap/cache, então essas pastas precisam liberar a escrita.
        $this->assertStringContainsString('[Dirs]', $this->conteudo);
        $this->assertStringContainsString('Name: "{app}\app\storage"; Permissions: users-modify', $this->conteudo);
        $this->assertStringContainsString('Name: "{app}\app\bootstrap\cache"; Permissions: users-modify', $this->conteudo);
    }

    public function test_arquivos_de_origem_sem_constante_existem_na_pasta_do_instalador(): void
    {
        // As origens com {#PastaDeBuild} saem da pasta build\windows, montada por preparar-binarios-windows.sh.
        preg_match_all('/^Source: "([^"{]+)"/m', $this->conteudo, $casamentos);

        $this->assertNotEmpty($casamentos[1]);
        foreach ($casamentos[1] as $origem) {
            $this->assertFileExists(dirname(self::INSTALADOR).'/'.$origem);
        }
    }
}

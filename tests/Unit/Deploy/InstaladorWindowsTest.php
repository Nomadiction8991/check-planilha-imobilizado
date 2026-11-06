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

    public function test_instalador_instala_o_visual_c_2022_quando_o_computador_nao_tem_a_versao_nova(): void
    {
        // PHP e MySQL precisam do runtime do Visual C++ 2022 (versão 14.44 ou mais nova).
        $this->assertStringContainsString('Source: "{#PastaDeBuild}\vc_redist.x64.exe"; DestDir: "{tmp}"', $this->conteudo);
        $this->assertStringContainsString('Parameters: "/install /quiet /norestart"', $this->conteudo);
        $this->assertStringContainsString('Check: VcRedistNeedsInstall', $this->conteudo);
        $this->assertStringContainsString('function VcRedistNeedsInstall', $this->conteudo);
        $this->assertStringContainsString('HKLM64', $this->conteudo);
        $this->assertStringContainsString('SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64', $this->conteudo);
    }

    public function test_preparo_do_windows_baixa_o_visual_c_com_soma_fixada(): void
    {
        $preparo = (string) file_get_contents(__DIR__.'/../../../deploy/pacotes/windows/preparar-binarios-windows.sh');

        $this->assertMatchesRegularExpression('/^VC_REDIST_URL="\$\{CP_VC_REDIST_URL:-https:\/\/download\.visualstudio\.microsoft\.com\/[^}]+\}"/m', $preparo);
        $this->assertMatchesRegularExpression('/^VC_REDIST_SHA256="\$\{CP_VC_REDIST_SHA256:-[a-f0-9]{64}\}"/m', $preparo);
        $this->assertStringContainsString('baixar_conferido "$VC_REDIST_URL" sha256 "$VC_REDIST_SHA256"', $preparo);
    }

    public function test_mensagem_de_download_do_preparo_nao_entra_no_caminho_do_arquivo(): void
    {
        // baixar_conferido devolve o caminho pela saída padrão, que é capturada em variável. A mensagem
        // "Baixando..." precisa ir para o erro, senão o caminho sai junto com ela quando não há cache.
        $preparo = (string) file_get_contents(__DIR__.'/../../../deploy/pacotes/windows/preparar-binarios-windows.sh');

        $this->assertStringContainsString('echo "Baixando $arquivo..." >&2', $preparo);
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

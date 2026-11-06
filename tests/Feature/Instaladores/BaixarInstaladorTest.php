<?php

declare(strict_types=1);

namespace Tests\Feature\Instaladores;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Confere o download dos instaladores: quem pode baixar e o que acontece quando o arquivo falta.
 */
class BaixarInstaladorTest extends TestCase
{
    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();

        // Pasta temporária com arquivos pequenos: o teste não usa os instaladores de verdade (cerca de 80 MB).
        $this->pasta = sys_get_temp_dir().'/cp-instaladores-'.bin2hex(random_bytes(4));
        File::makeDirectory($this->pasta);
        config(['instaladores.pasta' => $this->pasta]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pasta);

        parent::tearDown();
    }

    public function test_visitante_sem_login_e_mandado_para_a_tela_de_entrada(): void
    {
        $this->arquivo('CheckPlanilhaIgreja-0.1.0-setup.exe');

        $resposta = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.instaladores.baixar', 'windows'));

        $resposta->assertRedirect(route('migration.login'));
    }

    public function test_usuario_comum_baixa_o_instalador_do_windows(): void
    {
        $this->arquivo('CheckPlanilhaIgreja-0.1.0-setup.exe');

        $resposta = $this->usuarioComum()->get(route('migration.instaladores.baixar', 'windows'));

        $resposta->assertOk();
        $resposta->assertDownload('CheckPlanilhaIgreja-0.1.0-setup.exe');
    }

    public function test_usuario_comum_baixa_o_pacote_do_linux(): void
    {
        $this->arquivo('check-planilha-igreja_0.1.0_amd64.deb');

        $resposta = $this->usuarioComum()->get(route('migration.instaladores.baixar', 'linux'));

        $resposta->assertOk();
        $resposta->assertDownload('check-planilha-igreja_0.1.0_amd64.deb');
    }

    public function test_instalador_que_ainda_nao_foi_publicado_responde_404(): void
    {
        $resposta = $this->usuarioComum()->get(route('migration.instaladores.baixar', 'windows'));

        $resposta->assertNotFound();
    }

    public function test_sistema_desconhecido_responde_404(): void
    {
        $this->arquivo('CheckPlanilhaIgreja-0.1.0-setup.exe');

        $resposta = $this->usuarioComum()->get('/instaladores/macos');

        $resposta->assertNotFound();
    }

    private function usuarioComum(): static
    {
        return $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ]);
    }

    private function arquivo(string $nome): void
    {
        File::put($this->pasta.'/'.$nome, 'conteudo de teste');
    }
}

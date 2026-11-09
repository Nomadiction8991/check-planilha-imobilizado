<?php

declare(strict_types=1);

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Verificador de segredos do commit (.githooks/pre-commit): bloqueia arquivo de ambiente com valor,
 * chave de aplicação com valor, chave privada e token. Modelos com valor vazio e código comum passam.
 */
class PreCommitTest extends TestCase
{
    private const HOOK = __DIR__ . '/../../../.githooks/pre-commit';

    private string $repositorio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositorio = sys_get_temp_dir() . '/cp-precommit-' . bin2hex(random_bytes(6));
        mkdir($this->repositorio);
        $this->git(['init', '-q']);
    }

    protected function tearDown(): void
    {
        (new Process(['rm', '-rf', $this->repositorio]))->run();

        parent::tearDown();
    }

    public function test_bloqueia_arquivo_de_ambiente_com_valores(): void
    {
        $this->adicionar('.env', "APP_NAME=Teste\n");

        $this->assertBloqueado($this->executarHook(), '.env');
    }

    public function test_permite_modelo_de_ambiente_sem_valores(): void
    {
        $this->adicionar('.env.example', "APP_KEY=\nDB_PASSWORD=\n");

        $this->assertPermitido($this->executarHook());
    }

    public function test_permite_placeholder_de_senha_sem_segredo_em_modelo(): void
    {
        $this->adicionar('.env.example', "DB_PASSWORD=change-me\nDB_ROOT_PASSWORD=change-me\n");

        $this->assertPermitido($this->executarHook());
    }

    public function test_bloqueia_chave_de_aplicacao_com_valor_em_qualquer_arquivo(): void
    {
        $this->adicionar('notas.md', "APP_KEY=base64:Zm9vYmFyYmF6cXV4MTIzNDU2Nzg5MGFiY2RlZmdoaWpr\n");

        $this->assertBloqueado($this->executarHook(), 'APP_KEY');
    }

    public function test_aceita_o_texto_null_usado_no_lugar_de_senha_vazia(): void
    {
        $this->adicionar('notas.md', "MAIL_PASSWORD=null\n");

        $this->assertPermitido($this->executarHook());
    }

    public function test_nao_confunde_variavel_do_php_com_segredo(): void
    {
        $this->adicionar('app.php', "<?php\n\$password = \$request->input('password');\n");

        $this->assertPermitido($this->executarHook());
    }

    public function test_bloqueia_chave_privada(): void
    {
        // O cabeçalho é montado em partes para que este próprio arquivo não seja bloqueado no commit.
        $this->adicionar('chave.txt', '-----BEGIN ' . "OPENSSH PRIVATE KEY-----\nconteudo\n");

        $this->assertBloqueado($this->executarHook(), 'chave privada');
    }

    private function git(array $argumentos): void
    {
        (new Process(['git', ...$argumentos], $this->repositorio))->mustRun();
    }

    private function adicionar(string $arquivo, string $conteudo): void
    {
        file_put_contents($this->repositorio . '/' . $arquivo, $conteudo);
        $this->git(['add', $arquivo]);
    }

    private function executarHook(): Process
    {
        $processo = new Process(['sh', self::HOOK], $this->repositorio);
        $processo->run();

        return $processo;
    }

    private function assertBloqueado(Process $processo, string $motivo): void
    {
        $this->assertNotSame(0, $processo->getExitCode(), $processo->getOutput());
        $this->assertStringContainsString('Commit bloqueado', $processo->getOutput());
        $this->assertStringContainsString($motivo, $processo->getOutput());
    }

    private function assertPermitido(Process $processo): void
    {
        $this->assertSame(0, $processo->getExitCode(), $processo->getOutput());
    }
}

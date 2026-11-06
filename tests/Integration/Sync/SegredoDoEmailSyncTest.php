<?php

declare(strict_types=1);

namespace Tests\Integration\Sync;

use App\Sync\CopyExporter;
use App\Sync\CopyImporter;
use App\Sync\Device;
use App\Sync\DeviceRegistry;
use App\Sync\NotebookAgent;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Support\LocalSyncTransport;
use Tests\TestCase;

/**
 * A senha do e-mail é cifrada com a chave de cada instalação.
 *
 * Os testes usam chaves diferentes para o servidor e para o notebook. Se a senha viajar
 * cifrada, o outro lado não consegue abri-la, e o teste mostra isso.
 */
class SegredoDoEmailSyncTest extends TestCase
{
    private string $chaveServidor;

    private string $chaveNotebook;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->fail('Estes testes precisam de MySQL. Rode com: phpunit -c phpunit.mysql.xml');
        }

        foreach (['notebook_a' => 'cp_nb_a', 'servidor' => 'cp_servidor'] as $conexao => $banco) {
            DB::connection('mysql')->statement(
                "CREATE DATABASE IF NOT EXISTS `{$banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
            );

            config(["database.connections.{$conexao}" => array_merge(
                config('database.connections.mysql'),
                ['database' => $banco],
            )]);
            DB::purge($conexao);

            $this->artisan('migrate:fresh', ['--database' => $conexao, '--force' => true])->assertSuccessful();
        }

        $this->chaveServidor = (string) config('app.key');
        $this->chaveNotebook = 'base64:' . base64_encode(random_bytes(32));
    }

    public function test_senha_alterada_no_servidor_chega_ao_notebook_legivel_com_a_chave_dele(): void
    {
        $dispositivo = $this->cadastrarAparelho();
        $this->gravarSenhaNoServidor('senha-1');
        $this->copiarParaNotebook();
        $this->mudarSenhaNoServidor('senha-2');

        $this->sincronizar($dispositivo);

        $this->assertSame('senha-2', $this->lerSenhaNoNotebook());
        $this->assertNotSame(
            $this->senhaCifradaNoServidor(),
            $this->senhaCifradaNoNotebook(),
            'Cada instalação guarda a senha com a própria chave.',
        );
    }

    public function test_senha_trocada_no_notebook_chega_ao_servidor_legivel_com_a_chave_dele(): void
    {
        $dispositivo = $this->cadastrarAparelho();
        $this->gravarSenhaNoServidor('senha-1');
        $this->copiarParaNotebook();
        $this->sincronizar($dispositivo);

        $this->mudarSenhaNoNotebook('senha-3');
        $this->sincronizar($dispositivo);

        $this->assertSame('senha-3', $this->lerSenhaNoServidor());
    }

    private function cadastrarAparelho(): Device
    {
        return $this->noServidor(fn (): Device => app(DeviceRegistry::class)->registrar('Notebook A', 7)['aparelho']);
    }

    private function gravarSenhaNoServidor(string $senha): void
    {
        $this->noServidor(fn () => DB::table('configuracoes')->insert([
            'mail_host' => 'smtp.teste.local',
            'mail_password' => Crypt::encryptString($senha),
        ]));
    }

    private function mudarSenhaNoServidor(string $senha): void
    {
        $this->noServidor(fn () => DB::table('configuracoes')->update([
            'mail_password' => Crypt::encryptString($senha),
        ]));
    }

    private function mudarSenhaNoNotebook(string $senha): void
    {
        $this->noNotebook(fn () => DB::table('configuracoes')->update([
            'mail_password' => Crypt::encryptString($senha),
        ]));
    }

    private function copiarParaNotebook(): void
    {
        $linhas = $this->noServidor(fn (): array => iterator_to_array(app(CopyExporter::class)->linhas()));

        $this->noNotebook(fn () => app(CopyImporter::class)->importar($linhas));
    }

    /**
     * @return array{enviadas: int, recebidas: int, erros: list<array{uuid: string, motivo: string}>}
     */
    private function sincronizar(Device $dispositivo): array
    {
        return $this->noNotebook(fn (): array => app(NotebookAgent::class)->executar(
            new LocalSyncTransport((int) $dispositivo->id, 'servidor', $this->chaveServidor),
        ));
    }

    private function lerSenhaNoNotebook(): string
    {
        return $this->noNotebook(fn (): string => Crypt::decryptString($this->senhaCifradaNoNotebook()));
    }

    private function lerSenhaNoServidor(): string
    {
        return $this->noServidor(fn (): string => Crypt::decryptString($this->senhaCifradaNoServidor()));
    }

    private function senhaCifradaNoNotebook(): string
    {
        return (string) DB::connection('notebook_a')->table('configuracoes')->value('mail_password');
    }

    private function senhaCifradaNoServidor(): string
    {
        return (string) DB::connection('servidor')->table('configuracoes')->value('mail_password');
    }

    /**
     * Roda a ação no servidor, com a chave do servidor e o banco do servidor.
     */
    private function noServidor(callable $acao): mixed
    {
        return $this->comChave($this->chaveServidor, fn (): mixed => DB::usingConnection('servidor', $acao));
    }

    /**
     * Roda a ação no notebook, com a chave do notebook e o banco do notebook.
     */
    private function noNotebook(callable $acao): mixed
    {
        return $this->comChave($this->chaveNotebook, fn (): mixed => DB::usingConnection('notebook_a', $acao));
    }

    /**
     * @template T
     *
     * @param  callable(): T  $acao
     * @return T
     */
    private function comChave(string $chave, callable $acao): mixed
    {
        $original = Crypt::getFacadeRoot();
        Crypt::swap(new Encrypter(base64_decode(substr($chave, 7)), 'AES-256-CBC'));

        try {
            return $acao();
        } finally {
            Crypt::swap($original);
        }
    }
}

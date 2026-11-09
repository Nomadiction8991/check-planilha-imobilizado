<?php

declare(strict_types=1);

namespace Tests\Feature\Marca;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class MarcaConfiguracoesTest extends TestCase
{
    private const ADMIN = ['_enforce_legacy_auth' => true, 'usuario_id' => 1, 'usuario_nome' => 'Administrador', 'usuario_email' => 'ADMIN@LOCALHOST', 'is_admin' => true];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge();
        Schema::create('configuracoes', function (Blueprint $table): void {
            $table->string('app_name', 40)->nullable();
            $table->longText('app_logo')->nullable();
            $table->string('app_logo_mime', 50)->nullable();
            $table->char('app_logo_hash', 32)->nullable();
        });
    }

    public function test_nome_vazio_tem_mensagem_em_portugues(): void
    {
        $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 1,
            'usuario_nome' => 'Administrador',
            'usuario_email' => 'ADMIN@LOCALHOST',
            'is_admin' => true,
        ])->post('/configuracoes/marca', ['app_name' => ''])
            ->assertSessionHasErrors(['app_name' => 'Informe o nome do sistema.']);
    }

    public function test_salva_nome_e_logo_e_mantem_logo_ao_salvar_so_nome(): void
    {
        $bytes = file_get_contents(public_path('icons/pwa-192.png'));
        $arquivo = UploadedFile::fake()->createWithContent('logo.png', $bytes);
        $this->withSession(self::ADMIN)->post('/configuracoes/marca', ['app_name' => 'Minha marca', 'app_logo' => $arquivo])
            ->assertRedirect(route('migration.configuracoes.index'))->assertSessionHas('status_type', 'success');
        $registro = DB::table('configuracoes')->first();
        self::assertSame('Minha marca', $registro->app_name);
        self::assertSame('image/png', $registro->app_logo_mime);
        self::assertSame(md5($bytes), $registro->app_logo_hash);
        $this->withSession(self::ADMIN)->post('/configuracoes/marca', ['app_name' => 'Outra marca'])->assertSessionHas('status_type', 'success');
        self::assertSame(base64_encode($bytes), DB::table('configuracoes')->first()->app_logo);
        $this->get('/marca/logo')->assertOk()->assertHeader('Content-Type', 'image/png')->assertSee($bytes, false);
    }

    public function test_recusa_nome_longo_e_arquivos_invalidos(): void
    {
        $this->withSession(self::ADMIN)->post('/configuracoes/marca', ['app_name' => str_repeat('a', 41)])->assertSessionHasErrors('app_name');
        $texto = tmpfile();
        fwrite($texto, 'isto é apenas texto');
        foreach ([
            UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            UploadedFile::fake()->create('grande.png', 513, 'image/png'),
            new UploadedFile(stream_get_meta_data($texto)['uri'], 'texto.png', null, null, true),
        ] as $arquivo) {
            $response = $this->withSession(self::ADMIN)->post('/configuracoes/marca', ['app_name' => 'Marca', 'app_logo' => $arquivo]);
            self::assertTrue($response->getSession()->has('errors'), 'Arquivo aceito: '.$arquivo->getClientOriginalName());
            $response->assertSessionHasErrors('app_logo');
        }
    }

    public function test_usuario_sem_permissao_e_redirecionado(): void
    {
        $this->withSession([...self::ADMIN, 'is_admin' => false])->post('/configuracoes/marca', ['app_name' => 'Marca'])
            ->assertRedirect(route('migration.dashboard'));
        self::assertSame(0, DB::table('configuracoes')->count());
    }
}

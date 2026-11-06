<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;

/**
 * Conflitos: quando os dois lados mudaram o mesmo campo, uma pessoa escolhe qual valor fica.
 */
class ConflitosTest extends SincronizacaoTestCase
{
    private int $conflitoId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('produtos', function (Blueprint $table): void {
            $table->increments('id_produto');
            $table->string('codigo')->unique();
            $table->string('bem')->nullable();
        });

        DB::table('produtos')->insert(['id_produto' => 8, 'codigo' => 'P-8', 'bem' => 'Sofá']);

        $this->conflitoId = (int) DB::table('sincronizacao_conflitos')->insertGetId([
            'aparelho_id' => null,
            'tabela' => 'produtos',
            'chave' => '8',
            'operacao' => 'update',
            'antes' => json_encode(['id_produto' => 8, 'codigo' => 'P-8', 'bem' => 'Cadeira']),
            'depois' => json_encode(['id_produto' => 8, 'codigo' => 'P-8', 'bem' => 'Armário']),
            'motivo' => 'o servidor mudou os mesmos campos',
            'status' => 'pendente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mockarAdministrador();
    }

    public function test_manter_o_valor_do_notebook_aplica_a_mudanca_do_notebook(): void
    {
        $this->comoAdministrador()->post("/conflitos/{$this->conflitoId}/notebook")
            ->assertRedirect(route('migration.conflitos.index'));

        $this->assertSame('Armário', DB::table('produtos')->where('id_produto', 8)->value('bem'));
        $this->assertSame(
            'mantido_notebook',
            DB::table('sincronizacao_conflitos')->where('id', $this->conflitoId)->value('status'),
        );
    }

    public function test_manter_o_valor_do_servidor_deixa_o_produto_como_esta(): void
    {
        $this->comoAdministrador()->post("/conflitos/{$this->conflitoId}/servidor")
            ->assertRedirect(route('migration.conflitos.index'));

        $this->assertSame('Sofá', DB::table('produtos')->where('id_produto', 8)->value('bem'));
        $this->assertSame(
            'mantido_servidor',
            DB::table('sincronizacao_conflitos')->where('id', $this->conflitoId)->value('status'),
        );
    }

    public function test_tela_mostra_os_dois_valores_do_conflito_pendente(): void
    {
        $this->comoAdministrador()->get('/conflitos')
            ->assertOk()
            ->assertSee('Sofá')
            ->assertSee('Armário')
            ->assertSee('Manter o valor do notebook');
    }

    public function test_conflito_resolvido_sai_da_lista(): void
    {
        $this->comoAdministrador()->post("/conflitos/{$this->conflitoId}/servidor");

        $this->comoAdministrador()->get('/conflitos')
            ->assertOk()
            ->assertSee('Nenhum conflito pendente')
            ->assertDontSee('Manter o valor do notebook');
    }

    public function test_conflito_que_nao_existe_ou_ja_foi_resolvido_responde_404(): void
    {
        $this->comoAdministrador()->post('/conflitos/999/notebook')->assertNotFound();

        $this->comoAdministrador()->post("/conflitos/{$this->conflitoId}/servidor");
        $this->comoAdministrador()->post("/conflitos/{$this->conflitoId}/notebook")->assertNotFound();
    }

    public function test_quem_nao_e_administrador_nao_resolve_conflitos(): void
    {
        $this->comoVisitante()->post("/conflitos/{$this->conflitoId}/notebook")
            ->assertRedirect(route('migration.dashboard'));

        $this->assertSame('Sofá', DB::table('produtos')->where('id_produto', 8)->value('bem'));
    }

    private function mockarAdministrador(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 1,
                'nome' => 'Administrador',
                'email' => 'admin@teste.local',
                'comum_id' => null,
                'administracao_id' => null,
                'is_admin' => true,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn(null);
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('availableAdministrations')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });

        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([]);
            $mock->shouldReceive('can')->andReturnTrue();
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });
    }

    private function comoAdministrador(): static
    {
        return $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 1,
            'usuario_nome' => 'Administrador',
            'usuario_email' => 'admin@teste.local',
            'is_admin' => true,
        ]);
    }

    private function comoVisitante(): static
    {
        return $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 7,
            'usuario_nome' => 'Visitante',
            'usuario_email' => 'visitante@teste.local',
            'is_admin' => false,
        ]);
    }
}

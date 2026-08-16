<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Legacy\Comum;
use App\Models\Legacy\Usuario;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LegacySessionRotationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->createTables();
    }

    private function createTables(): void
    {
        DB::statement('
            CREATE TABLE IF NOT EXISTS "comums" (
                "id" INTEGER PRIMARY KEY AUTOINCREMENT,
                "codigo" TEXT NOT NULL UNIQUE,
                "cnpj" TEXT,
                "descricao" TEXT,
                "administracao" TEXT,
                "cidade" TEXT,
                "setor" TEXT
            )
        ');

        DB::statement('
            CREATE TABLE IF NOT EXISTS "usuarios" (
                "id" INTEGER PRIMARY KEY AUTOINCREMENT,
                "nome" TEXT NOT NULL,
                "email" TEXT,
                "tipo" TEXT,
                "comum_id" INTEGER,
                "ativo" INTEGER DEFAULT 1,
                "senha" TEXT,
                "cpf" TEXT,
                "rg" TEXT,
                "telefone" TEXT,
                "permissions" TEXT,
                "ui_preferences" TEXT,
                "administracao_id" INTEGER,
                "administracoes_permitidas" TEXT
            )
        ');
    }

    public function testSwitchChurchRegeneratesSessionId(): void
    {
        $church7 = new Comum();
        $church7->forceFill(['codigo' => '12-3456', 'descricao' => 'Central Cuiabá']);
        $church7->save();

        $church11 = new Comum();
        $church11->forceFill(['codigo' => '12-7890', 'descricao' => 'Várzea Grande']);
        $church11->save();

        $user = new Usuario();
        $user->forceFill([
            'nome' => 'Maria Silva',
            'email' => 'maria@exemplo.com',
            'tipo' => 'administrador',
            'comum_id' => (int) $church7->id,
            'ativo' => 1,
        ]);
        $user->save();

        $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => (int) $user->id,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => (int) $church7->id,
        ]);

        $sessionIdBefore = $this->app['session']->getId();

        $response = $this->post(route('migration.session.church'), [
            'comum_id' => (int) $church11->id,
            'redirect_to' => '/products',
        ]);

        $response->assertRedirect('/products');
        $response->assertSessionHas('comum_id', (int) $church11->id);
        $response->assertSessionHas('status', 'Igreja ativa atualizada.');

        $sessionIdAfter = $this->app['session']->getId();

        $this->assertNotNull($sessionIdBefore);
        $this->assertNotNull($sessionIdAfter);
        $this->assertNotSame($sessionIdBefore, $sessionIdAfter, 'Session ID must change after church switch to prevent session fixation.');
    }

    public function testSwitchChurchRegeneratesSessionIdViaUsersSelectChurch(): void
    {
        $church7 = new Comum();
        $church7->forceFill(['codigo' => '12-3456', 'descricao' => 'Central Cuiabá']);
        $church7->save();

        $church11 = new Comum();
        $church11->forceFill(['codigo' => '12-7890', 'descricao' => 'Várzea Grande']);
        $church11->save();

        $user = new Usuario();
        $user->forceFill([
            'nome' => 'Maria Silva',
            'email' => 'maria@exemplo.com',
            'tipo' => 'administrador',
            'comum_id' => (int) $church7->id,
            'ativo' => 1,
        ]);
        $user->save();

        $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => (int) $user->id,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => (int) $church7->id,
        ]);

        $sessionIdBefore = $this->app['session']->getId();

        $response = $this->postJson('/users/select-church', [
            'comum_id' => (int) $church11->id,
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'message' => 'Comum selecionada com sucesso',
        ]);
        $response->assertSessionHas('comum_id', (int) $church11->id);

        $sessionIdAfter = $this->app['session']->getId();

        $this->assertNotNull($sessionIdBefore);
        $this->assertNotNull($sessionIdAfter);
        $this->assertNotSame($sessionIdBefore, $sessionIdAfter, 'Session ID must change after church switch via users/select-church endpoint.');
    }
}

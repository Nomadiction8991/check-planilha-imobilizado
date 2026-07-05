<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class PublicAccessTest extends TestCase
{
    private int $churchId;
    private string $churchName;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the comums table manually since the migration uses MySQL-specific
        // syntax (AUTO_INCREMENT, ENGINE=InnoDB) that fails on SQLite :memory:
        if (! Schema::hasTable('comums')) {
            Schema::create('comums', function ($table) {
                $table->integer('id', true);
                $table->string('codigo', 50)->unique();
                $table->string('cnpj')->nullable()->unique();
                $table->string('descricao')->nullable();
                $table->string('administracao')->nullable();
                $table->string('cidade')->nullable();
                $table->string('setor')->nullable();
            });
        }

        $this->churchId = (int) DB::table('comums')->insertGetId([
            'codigo' => 'PUB-001',
            'descricao' => 'Igreja Teste Público',
            'cidade' => 'Cidade Teste',
            'setor' => 'Setor Teste',
        ]);

        $this->churchName = 'Igreja Teste Público';
    }

    public function testCreateReturns200WithChurchesList(): void
    {
        $response = $this->get(route('public.access.create'));

        $response->assertOk();
        $response->assertViewIs('public-access.create');
        $response->assertViewHas('churches');
        $response->assertSee('Igreja Teste Público');
    }

    public function testCreateClearsPreviousSessionData(): void
    {
        $response = $this->withSession([
            'public_acesso' => true,
            'public_planilha_id' => 999,
            'public_comum_id' => 999,
            'public_comum' => 'Old Church',
        ])->get(route('public.access.create'));

        $response->assertSessionMissing('public_acesso');
        $response->assertSessionMissing('public_planilha_id');
        $response->assertSessionMissing('public_comum_id');
        $response->assertSessionMissing('public_comum');
    }

    public function testStoreWithValidChurchRedirectsAndSetsSession(): void
    {
        $response = $this->post(route('public.access.store'), [
            'comum_id' => (string) $this->churchId,
        ]);

        $response->assertRedirect();
        $redirectUrl = (string) $response->headers->get('Location');
        $this->assertStringContainsString('/churches/public', $redirectUrl);
        $this->assertStringContainsString('contexto=planilha', $redirectUrl);
        $this->assertStringContainsString('publico=1', $redirectUrl);
        $this->assertStringContainsString('id=' . $this->churchId, $redirectUrl);

        $this->assertTrue(session('public_acesso'));
        $this->assertSame($this->churchId, session('public_planilha_id'));
        $this->assertSame($this->churchId, session('public_comum_id'));
        $this->assertSame($this->churchName, session('public_comum'));
    }

    public function testStoreFailsWhenComumIdIsMissing(): void
    {
        $response = $this->from(route('public.access.create'))
            ->post(route('public.access.store'), []);

        $response->assertSessionHasErrors(['comum_id']);
        $response->assertRedirect(route('public.access.create'));
    }

    public function testStoreFailsWhenComumIdIsNotInteger(): void
    {
        $response = $this->from(route('public.access.create'))
            ->post(route('public.access.store'), [
                'comum_id' => 'abc',
            ]);

        $response->assertSessionHasErrors(['comum_id']);
        $response->assertRedirect(route('public.access.create'));
    }

    public function testStoreFailsWhenComumIdIsLessThanOne(): void
    {
        $response = $this->from(route('public.access.create'))
            ->post(route('public.access.store'), [
                'comum_id' => '0',
            ]);

        $response->assertSessionHasErrors(['comum_id']);
        $response->assertRedirect(route('public.access.create'));
    }

    public function testStoreFailsWhenChurchNotFound(): void
    {
        $nonExistentId = $this->churchId + 99999;

        $response = $this->from(route('public.access.create'))
            ->post(route('public.access.store'), [
                'comum_id' => (string) $nonExistentId,
            ]);

        $response->assertRedirect(route('public.access.create'));
        $response->assertSessionHas('status');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testLogoutClearsSessionAndDoesNotRedirectToAuthRoutes(): void
    {
        $response = $this->withSession([
            'public_acesso' => true,
            'public_planilha_id' => $this->churchId,
            'public_comum_id' => $this->churchId,
            'public_comum' => $this->churchName,
        ])->post(route('public.access.logout'));

        $response->assertSessionMissing('public_acesso');
        $response->assertSessionMissing('public_planilha_id');
        $response->assertSessionMissing('public_comum_id');
        $response->assertSessionMissing('public_comum');
    }

    public function testLogoutRedirectsToLoginRoute(): void
    {
        $response = $this->post(route('public.access.logout'));

        $response->assertRedirect(route('migration.login'));
    }

    public function testLogoutWorksWithoutPriorSession(): void
    {
        $response = $this->post(route('public.access.logout'));

        $response->assertRedirect(route('migration.login'));
        $response->assertSessionMissing('public_acesso');
    }
}

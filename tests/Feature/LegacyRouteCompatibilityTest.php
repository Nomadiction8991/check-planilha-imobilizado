<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyRouteCompatibilityTest extends TestCase
{
    public function testLegacyProductsViewRouteRedirectsToLaravelIndex(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get('/products/view?comum_id=7&codigo=A-10');

        $response->assertRedirect('/products?comum_id=7&busca=A-10');
    }

    public function testLegacyProductsNewRouteRedirectsToLaravelFilteredIndex(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get('/products/novo?comum_id=7');

        $response->assertRedirect('/products?comum_id=7&status=novos&somente_novos=1');
    }

    public function testLegacyProductsPostEditRouteRedirectsToCanonicalUpdateRoute(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->post('/products/edit', [
            'id_produto' => 19,
        ]);

        $response->assertStatus(307);
        $response->assertRedirect('/products/19');
    }

    public function testLegacyProductsPostDeleteRouteIsUnavailable(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->post('/products/delete', [
            'id_produto' => 19,
        ]);

        $response->assertNotFound();
    }

    public function testLegacyReportsViewRouteRedirectsToLaravelPreview(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get('/reports/view?formulario=14.1&comum_id=7');

        $response->assertRedirect('/reports/14.1?comum_id=7');
    }

    public function testLegacySpreadsheetPreviewRouteRedirectsToLaravelPreview(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->get('/spreadsheets/preview?id=15');

        $response->assertRedirect('/spreadsheets/preview/15');
    }

    public function testLegacyUsersEditRouteRedirectsToLaravelEditPage(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ])->get('/users/edit?id=11');

        $response->assertRedirect('/users/11/edit');
    }

    public function testLegacyUsersPostEditRouteRedirectsToCanonicalUpdateRoute(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ])->post('/users/edit', [
            'id' => 11,
        ]);

        $response->assertStatus(307);
        $response->assertRedirect('/users/11');
    }

    public function testLegacyUsersPostDeleteRouteRedirectsToCanonicalDestroyRoute(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ])->post('/users/delete', [
            'id' => 11,
        ]);

        $response->assertStatus(307);
        $response->assertRedirect('/users/11/delete');
    }

    public function testLegacyUsersSelectChurchRouteReturnsLegacyJsonContract(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('switchChurch')->once()->with(11);
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => false,
        ])->postJson('/users/select-church', [
            'comum_id' => 11,
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'message' => 'Comum selecionada com sucesso',
        ]);
        $response->assertSessionHas('comum_id', 11);
    }
}

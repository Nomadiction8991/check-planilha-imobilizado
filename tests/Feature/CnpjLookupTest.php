<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CnpjLookupTest extends TestCase
{
    public function testLookupReturnsCompanyData(): void
    {
        Http::fake([
            'https://www.cnpj.dev/api/v1/cnpj/12345678000190' => Http::response([
                'razao_social' => 'Igreja Central',
                'municipio' => 'Cuiabá',
            ], 200),
        ]);

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ])->postJson(route('migration.api.cnpj.lookup'), [
            'cnpj' => '12345678000190',
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'data' => [
                'nome' => 'Igreja Central',
                'cidade' => 'Cuiabá',
            ],
            'source' => 'cnpj.dev',
        ]);
    }

    public function testLookupReturnsLegacyErrorWhenCnpjIsInvalid(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ])->postJson(route('migration.api.cnpj.lookup'), [
            'cnpj' => '123',
        ]);

        $response->assertStatus(400);
        $response->assertExactJson([
            'success' => false,
            'error' => 'CNPJ inválido',
        ]);
    }

    public function testLookupReturnsNotFoundWhenExternalApiDoesNotFindCompany(): void
    {
        Http::fake([
            'https://www.cnpj.dev/api/v1/cnpj/12345678000190' => Http::response([], 404),
        ]);

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ])->postJson(route('migration.api.cnpj.lookup'), [
            'cnpj' => '12345678000190',
        ]);

        $response->assertNotFound();
        $response->assertExactJson([
            'success' => false,
            'data' => null,
            'message' => 'CNPJ não encontrado',
        ]);
    }
}
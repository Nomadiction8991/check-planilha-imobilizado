<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CnpjLookupControllerTest extends TestCase
{
    private const string VALID_CNPJ = '12345678000190';

    private const array AUTH_SESSION = [
        '_enforce_legacy_auth' => true,
        'usuario_id' => 9,
        'usuario_nome' => 'Maria Silva',
        'usuario_email' => 'MARIA@EXEMPLO.COM',
        'comum_id' => 7,
        'is_admin' => true,
    ];

    public function testValidCnpjLookup(): void
    {
        Http::fake([
            'https://www.cnpj.dev/api/v1/cnpj/*' => Http::response([
                'razao_social' => 'Igreja Central',
                'municipio' => 'Cuiabá',
            ], 200),
        ]);

        $response = $this->withSession(self::AUTH_SESSION)
            ->postJson(route('migration.api.cnpj.lookup'), [
                'cnpj' => self::VALID_CNPJ,
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

    public function testInvalidCnpjLookup(): void
    {
        $response = $this->withSession(self::AUTH_SESSION)
            ->postJson(route('migration.api.cnpj.lookup'), [
                'cnpj' => '123',
            ]);

        $response->assertStatus(400);
        $response->assertExactJson([
            'success' => false,
            'error' => 'CNPJ inválido',
        ]);
    }

    public function testExternalApiFailure(): void
    {
        Http::fake([
            'https://www.cnpj.dev/api/v1/cnpj/*' => Http::response([], 500),
        ]);

        $response = $this->withSession(self::AUTH_SESSION)
            ->postJson(route('migration.api.cnpj.lookup'), [
                'cnpj' => self::VALID_CNPJ,
            ]);

        // Controller must return a friendly 404 without leaking API details
        $response->assertNotFound();
        $response->assertExactJson([
            'success' => false,
            'data' => null,
            'message' => 'CNPJ não encontrado',
        ]);
        $response->assertJsonMissing(['error']);
        $response->assertJsonMissing(['trace']);
        $response->assertJsonMissing(['exception']);
        $response->assertJsonMissing(['Internal Server Error']);
    }

    public function testAuthentication(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->postJson(route('migration.api.cnpj.lookup'), [
                'cnpj' => self::VALID_CNPJ,
            ]);

        $response->assertRedirect(route('migration.login'));
    }
}

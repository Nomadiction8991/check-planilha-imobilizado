<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class BrazilLocalityControllerTest extends TestCase
{
    public function testStatesReturnsNormalizedPayload(): void
    {
        Http::fake([
            'https://servicodados.ibge.gov.br/api/v1/localidades/estados' => Http::response([
                ['sigla' => 'MT', 'nome' => 'Mato Grosso'],
                ['sigla' => 'SP', 'nome' => 'São Paulo'],
            ]),
            'https://brasilapi.com.br/api/ibge/uf/v1' => Http::response([
                ['sigla' => 'RJ', 'nome' => 'Rio de Janeiro'],
            ]),
        ]);

        $response = $this->get(route('migration.api.localidades.states'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                ['sigla' => 'MT', 'nome' => 'Mato Grosso'],
                ['sigla' => 'SP', 'nome' => 'São Paulo'],
            ],
            'source' => 'IBGE',
        ]);
    }

    public function testStatesFallsBackToBrasilApiWhenIbgeFails(): void
    {
        Http::fake([
            'https://servicodados.ibge.gov.br/api/v1/localidades/estados' => Http::response([], 503),
            'https://brasilapi.com.br/api/ibge/uf/v1' => Http::response([
                ['sigla' => 'MT', 'nome' => 'Mato Grosso'],
                ['sigla' => 'SP', 'nome' => 'São Paulo'],
            ]),
        ]);

        $response = $this->get(route('migration.api.localidades.states'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                ['sigla' => 'MT', 'nome' => 'Mato Grosso'],
                ['sigla' => 'SP', 'nome' => 'São Paulo'],
            ],
            'source' => 'BrasilAPI',
        ]);
    }

    public function testCitiesReturnsNormalizedPayload(): void
    {
        Http::fake([
            'https://servicodados.ibge.gov.br/api/v1/localidades/estados/MT/municipios' => Http::response([
                ['nome' => 'Cuiabá'],
                ['nome' => 'Várzea Grande'],
            ]),
            'https://brasilapi.com.br/api/ibge/municipios/v1/MT' => Http::response([
                ['nome' => 'CUIABÁ'],
            ]),
        ]);

        $response = $this->get(route('migration.api.localidades.cities', ['state' => 'MT']));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => ['Cuiabá', 'Várzea Grande'],
            'source' => 'IBGE',
        ]);
    }

    public function testCitiesFallsBackToBrasilApiWhenIbgeFails(): void
    {
        Http::fake([
            'https://servicodados.ibge.gov.br/api/v1/localidades/estados/MT/municipios' => Http::response([], 503),
            'https://brasilapi.com.br/api/ibge/municipios/v1/MT' => Http::response([
                ['nome' => 'CUIABÁ'],
                ['nome' => 'VÁRZEA GRANDE'],
            ]),
        ]);

        $response = $this->get(route('migration.api.localidades.cities', ['state' => 'MT']));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => ['Cuiabá', 'Várzea Grande'],
            'source' => 'BrasilAPI',
        ]);
    }

    public function testCitiesRejectsInvalidState(): void
    {
        Http::fake();

        $response = $this->get(route('migration.api.localidades.cities', ['state' => 'XX']));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'UF inválida.',
            'data' => [],
        ]);

        Http::assertNothingSent();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class BrazilLocalityControllerTest extends TestCase
{
    public function testCitiesReturnsNormalizedPayload(): void
    {
        Http::fake([
            'https://servicodados.ibge.gov.br/api/v1/localidades/estados/MT/municipios' => Http::response([
                ['nome' => 'Cuiabá'],
                ['nome' => 'Várzea Grande'],
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

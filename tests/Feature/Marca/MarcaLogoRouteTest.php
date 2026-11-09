<?php

declare(strict_types=1);

namespace Tests\Feature\Marca;

use Tests\TestCase;

final class MarcaLogoRouteTest extends TestCase
{
    public function test_visitante_recebe_logo_padrao(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])->get('/marca/logo');

        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        self::assertSame(file_get_contents(public_path('icons/pwa-192.png')), $response->getContent());
    }
}

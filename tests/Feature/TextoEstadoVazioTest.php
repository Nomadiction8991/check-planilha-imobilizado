<?php

namespace Tests\Feature;

use App\Contracts\LegacyChurchBrowserServiceInterface;
use App\Contracts\LegacyProductBrowserServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TextoEstadoVazioTest extends TestCase
{
    public function test_verificacao_apresenta_orientacao_curta(): void
    {
        $service = $this->mock(LegacyProductBrowserServiceInterface::class);
        $service->shouldReceive('paginate')->once()->andReturn(new LengthAwarePaginator([], 0, 20));
        foreach (['churchOptions', 'administrationOptions', 'dependencyOptions', 'assetTypeOptions'] as $method) {
            $service->shouldReceive($method)->once()->andReturn(collect());
        }
        $service->shouldReceive('statusOptions')->once()->andReturn([]);

        $response = $this->get(route('migration.products.verification'));
        $response->assertOk();
        $this->assertOrientacaoCurta($response->getContent());
    }

    public function test_igrejas_apresenta_orientacao_curta(): void
    {
        $service = $this->mock(LegacyChurchBrowserServiceInterface::class);
        $service->shouldReceive('paginate')->andReturn(new LengthAwarePaginator([], 0, 20));
        $service->shouldReceive('countAll')->andReturn(0);
        $service->shouldReceive('administrationOptions')->andReturn(collect());

        $response = $this->get(route('migration.churches.index'));
        $response->assertOk();
        $this->assertOrientacaoCurta($response->getContent());
    }

    private function assertOrientacaoCurta(string $html): void
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $nodes = (new \DOMXPath($document))->query('//div[contains(concat(" ", normalize-space(@class), " "), " empty-state ")]/p');
        $this->assertCount(1, $nodes);
        $this->assertLessThanOrEqual(60, mb_strlen(trim($nodes->item(0)->textContent)));
    }
}

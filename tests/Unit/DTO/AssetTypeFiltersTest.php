<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\AssetTypeFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

final class AssetTypeFiltersTest extends TestCase
{
    public function testFromRequestBuildsFiltersCorrectly(): void
    {
        $request = Request::create('/asset-types', 'GET', [
            'administracao_id' => '15',
            'busca' => 'CADEIRA',
            'pagina' => '2',
        ]);

        $filters = AssetTypeFilters::fromRequest($request);

        self::assertSame(15, $filters->administrationId);
        self::assertSame('CADEIRA', $filters->search);
        self::assertSame(2, $filters->page);
        self::assertSame(20, $filters->perPage);

        $query = $filters->toQuery();
        self::assertSame([
            'administracao_id' => 15,
            'busca' => 'CADEIRA',
        ], $query);
    }

    public function testFromRequestHandlesDefaults(): void
    {
        $request = Request::create('/asset-types', 'GET');

        $filters = AssetTypeFilters::fromRequest($request);

        self::assertNull($filters->administrationId);
        self::assertSame('', $filters->search);
        self::assertSame(1, $filters->page);
        self::assertSame(20, $filters->perPage);

        self::assertSame([], $filters->toQuery());
    }
}

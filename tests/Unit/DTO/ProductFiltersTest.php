<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\ProductFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

final class ProductFiltersTest extends TestCase
{
    public function testFromRequestBuildsFiltersCorrectly(): void
    {
        $request = Request::create('/products', 'GET', [
            'administracao_id' => '10',
            'comum_id' => '7',
            'busca' => 'CADEIRA',
            'dependencia_id' => '3',
            'tipo_bem_id' => '2',
            'estado' => 'sp',
            'status' => 'com_nota',
            'somente_novos' => '1',
            'pagina' => '4',
        ]);

        $filters = ProductFilters::fromRequest($request);

        self::assertSame(10, $filters->administrationId);
        self::assertSame(7, $filters->comumId);
        self::assertSame('CADEIRA', $filters->search);
        self::assertSame(3, $filters->dependencyId);
        self::assertSame(2, $filters->assetTypeId);
        self::assertSame('SP', $filters->state);
        self::assertSame('com_nota', $filters->status);
        self::assertTrue($filters->onlyNew);
        self::assertSame(4, $filters->page);
        self::assertSame(20, $filters->perPage);

        $query = $filters->toQuery();
        self::assertSame([
            'administracao_id' => 10,
            'comum_id' => 7,
            'busca' => 'CADEIRA',
            'dependencia_id' => 3,
            'tipo_bem_id' => 2,
            'estado' => 'SP',
            'status' => 'com_nota',
            'somente_novos' => 1,
        ], $query);
    }

    public function testFromRequestHandlesDefaults(): void
    {
        $request = Request::create('/products', 'GET');

        $filters = ProductFilters::fromRequest($request);

        self::assertNull($filters->administrationId);
        self::assertNull($filters->comumId);
        self::assertSame('', $filters->search);
        self::assertNull($filters->dependencyId);
        self::assertNull($filters->assetTypeId);
        self::assertNull($filters->state);
        self::assertSame('', $filters->status);
        self::assertFalse($filters->onlyNew);
        self::assertSame(1, $filters->page);
        self::assertSame(20, $filters->perPage);

        self::assertSame([], $filters->toQuery());
    }

    public function testFromRequestSanitizesState(): void
    {
        $request = Request::create('/products', 'GET', [
            'estado' => ' mg ',
        ]);

        $filters = ProductFilters::fromRequest($request);

        self::assertSame('MG', $filters->state);
        self::assertSame(['estado' => 'MG'], $filters->toQuery());
    }
}

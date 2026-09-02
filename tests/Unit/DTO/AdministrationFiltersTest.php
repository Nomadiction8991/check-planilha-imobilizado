<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\AdministrationFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

final class AdministrationFiltersTest extends TestCase
{
    public function testFromRequestBuildsFiltersCorrectly(): void
    {
        $request = Request::create('/administrations', 'GET', [
            'busca' => 'Central',
            'estado' => 'SP',
            'pagina' => '2',
        ]);

        $filters = AdministrationFilters::fromRequest($request);

        self::assertSame('Central', $filters->search);
        self::assertSame('SP', $filters->state);
        self::assertSame(2, $filters->page);
        self::assertSame(20, $filters->perPage);

        $query = $filters->toQuery();
        self::assertSame([
            'busca' => 'Central',
            'estado' => 'SP',
        ], $query);
    }

    public function testFromRequestHandlesDefaults(): void
    {
        $request = Request::create('/administrations', 'GET');

        $filters = AdministrationFilters::fromRequest($request);

        self::assertSame('', $filters->search);
        self::assertNull($filters->state);
        self::assertSame(1, $filters->page);
        self::assertSame(20, $filters->perPage);

        self::assertSame([], $filters->toQuery());
    }

    public function testFromRequestSanitizesState(): void
    {
        $request = Request::create('/administrations', 'GET', [
            'estado' => '  pr  ',
        ]);

        $filters = AdministrationFilters::fromRequest($request);

        self::assertSame('PR', $filters->state);
        self::assertSame(['estado' => 'PR'], $filters->toQuery());
    }
}

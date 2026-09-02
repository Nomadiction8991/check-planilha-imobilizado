<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\DepartmentFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

final class DepartmentFiltersTest extends TestCase
{
    public function testFromRequestBuildsFiltersCorrectly(): void
    {
        $request = Request::create('/departments', 'GET', [
            'administracao_id' => '12',
            'comum_id' => '5',
            'busca' => 'SALAO',
            'pagina' => '3',
        ]);

        $filters = DepartmentFilters::fromRequest($request);

        self::assertSame(12, $filters->administrationId);
        self::assertSame(5, $filters->comumId);
        self::assertSame('SALAO', $filters->search);
        self::assertSame(3, $filters->page);
        self::assertSame(20, $filters->perPage);

        $query = $filters->toQuery();
        self::assertSame([
            'administracao_id' => 12,
            'comum_id' => 5,
            'busca' => 'SALAO',
        ], $query);
    }

    public function testFromRequestHandlesDefaults(): void
    {
        $request = Request::create('/departments', 'GET');

        $filters = DepartmentFilters::fromRequest($request);

        self::assertNull($filters->administrationId);
        self::assertNull($filters->comumId);
        self::assertSame('', $filters->search);
        self::assertNull($filters->state);
        self::assertSame(1, $filters->page);
        self::assertSame(20, $filters->perPage);

        self::assertSame([], $filters->toQuery());
    }

    public function testFromRequestSanitizesState(): void
    {
        $request = Request::create('/departments', 'GET', [
            'estado' => ' sp ',
        ]);

        $filters = DepartmentFilters::fromRequest($request);

        self::assertSame('SP', $filters->state);
        self::assertSame(['estado' => 'SP'], $filters->toQuery());
    }
}

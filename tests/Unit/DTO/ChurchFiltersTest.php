<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\ChurchFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

final class ChurchFiltersTest extends TestCase
{
    public function testFromRequestBuildsFiltersCorrectly(): void
    {
        $request = Request::create('/churches', 'GET', [
            'administracao_id' => '12',
            'busca' => 'Central',
            'estado' => 'SP',
            'pagina' => '3',
        ]);

        $filters = ChurchFilters::fromRequest($request);

        self::assertSame(12, $filters->administrationId);
        self::assertSame('Central', $filters->search);
        self::assertSame('SP', $filters->state);
        self::assertSame(3, $filters->page);
        self::assertSame(20, $filters->perPage);

        $query = $filters->toQuery();
        self::assertSame([
            'administracao_id' => 12,
            'busca' => 'Central',
            'estado' => 'SP',
        ], $query);
    }

    public function testFromRequestHandlesDefaults(): void
    {
        $request = Request::create('/churches', 'GET');

        $filters = ChurchFilters::fromRequest($request);

        self::assertNull($filters->administrationId);
        self::assertSame('', $filters->search);
        self::assertNull($filters->state);
        self::assertSame(1, $filters->page);
        self::assertSame(20, $filters->perPage);

        self::assertSame([], $filters->toQuery());
    }

    public function testFromRequestSanitizesState(): void
    {
        $request = Request::create('/churches', 'GET', [
            'estado' => '  mt  ',
        ]);

        $filters = ChurchFilters::fromRequest($request);

        self::assertSame('MT', $filters->state);
        self::assertSame(['estado' => 'MT'], $filters->toQuery());
    }
}

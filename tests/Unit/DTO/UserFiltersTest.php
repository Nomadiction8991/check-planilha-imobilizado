<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\UserFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

final class UserFiltersTest extends TestCase
{
    public function testFromRequestBuildsFiltersCorrectly(): void
    {
        $request = Request::create('/users', 'GET', [
            'administracao_id' => '12',
            'busca' => 'MARIA',
            'status' => '1',
            'pagina' => '3',
            'estado' => 'SP',
        ]);

        $filters = UserFilters::fromRequest($request);

        self::assertSame(12, $filters->administrationId);
        self::assertSame('MARIA', $filters->search);
        self::assertSame('1', $filters->status);
        self::assertSame('SP', $filters->state);
        self::assertSame(3, $filters->page);
        self::assertSame(20, $filters->perPage);

        $query = $filters->toQuery();
        self::assertSame([
            'administracao_id' => 12,
            'busca' => 'MARIA',
            'status' => '1',
            'estado' => 'SP',
        ], $query);
    }

    public function testFromRequestHandlesDefaults(): void
    {
        $request = Request::create('/users', 'GET');

        $filters = UserFilters::fromRequest($request);

        self::assertNull($filters->administrationId);
        self::assertSame('', $filters->search);
        self::assertSame('', $filters->status);
        self::assertNull($filters->state);
        self::assertSame(1, $filters->page);
        self::assertSame(20, $filters->perPage);

        self::assertSame([], $filters->toQuery());
    }

    public function testFromRequestSanitizesState(): void
    {
        $request = Request::create('/users', 'GET', [
            'estado' => ' mg ',
        ]);

        $filters = UserFilters::fromRequest($request);

        self::assertSame('MG', $filters->state);
        self::assertSame(['estado' => 'MG'], $filters->toQuery());
    }
}

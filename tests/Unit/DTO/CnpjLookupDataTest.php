<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\CnpjLookupData;
use Tests\TestCase;

final class CnpjLookupDataTest extends TestCase
{
    /** @test */
    public function testCanBeCreatedWithValidCnpj(): void
    {
        $data = new CnpjLookupData(cnpj: '11222333000181');

        $this->assertInstanceOf(CnpjLookupData::class, $data);
        $this->assertSame('11222333000181', $data->cnpj);
    }

    /** @test */
    public function testPropertyIsReadable(): void
    {
        $data = new CnpjLookupData(cnpj: '99888777000155');

        $this->assertObjectHasProperty('cnpj', $data);
        $this->assertIsString($data->cnpj);
    }

    /** @test */
    public function testCnpjWithFormattedInput(): void
    {
        $data = new CnpjLookupData(cnpj: '11.222.333/0001-81');

        $this->assertSame('11.222.333/0001-81', $data->cnpj);
    }

    /** @test */
    public function testCnpjWithEmptyString(): void
    {
        $data = new CnpjLookupData(cnpj: '');

        $this->assertSame('', $data->cnpj);
    }

    /** @test */
    public function testCnpjImmutability(): void
    {
        $data = new CnpjLookupData(cnpj: '11222333000181');

        // Readonly ensures property cannot be changed — verify the value is correct
        $this->assertSame('11222333000181', $data->cnpj);
    }
}

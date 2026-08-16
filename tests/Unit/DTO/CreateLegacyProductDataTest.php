<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\CreateLegacyProductData;
use Tests\TestCase;

final class CreateLegacyProductDataTest extends TestCase
{
    /** @test */
    public function testCanBeCreatedWithAllFields(): void
    {
        $data = new CreateLegacyProductData(
            churchId: 1,
            code: 'PROD-001',
            assetTypeId: 5,
            itemName: 'Cadeira de Metal',
            complement: 'Braço direito',
            brand: 'MarcaX',
            heightMeters: '1.20',
            widthMeters: '0.50',
            lengthMeters: '0.60',
            dependencyId: 3,
            multiplier: 2,
            printReport141: true,
            condition141: 'Bom',
            invoiceNumber: 12345,
            invoiceDate: '2024-01-15',
            invoiceValue: '1500.00',
            invoiceSupplier: 'Fornecedor Ltda',
        );

        $this->assertInstanceOf(CreateLegacyProductData::class, $data);
        $this->assertSame(1, $data->churchId);
        $this->assertSame('PROD-001', $data->code);
        $this->assertSame(5, $data->assetTypeId);
        $this->assertSame('Cadeira de Metal', $data->itemName);
        $this->assertSame('Braço direito', $data->complement);
        $this->assertSame('MarcaX', $data->brand);
        $this->assertSame('1.20', $data->heightMeters);
        $this->assertSame('0.50', $data->widthMeters);
        $this->assertSame('0.60', $data->lengthMeters);
        $this->assertSame(3, $data->dependencyId);
        $this->assertSame(2, $data->multiplier);
        $this->assertTrue($data->printReport141);
        $this->assertSame('Bom', $data->condition141);
        $this->assertSame(12345, $data->invoiceNumber);
        $this->assertSame('2024-01-15', $data->invoiceDate);
        $this->assertSame('1500.00', $data->invoiceValue);
        $this->assertSame('Fornecedor Ltda', $data->invoiceSupplier);
    }

    /** @test */
    public function testCanBeCreatedWithNullableFieldsAsNull(): void
    {
        $data = new CreateLegacyProductData(
            churchId: 2,
            code: null,
            assetTypeId: 10,
            itemName: 'Mesa de Escritório',
            complement: 'Sem complemento',
            brand: null,
            heightMeters: null,
            widthMeters: null,
            lengthMeters: null,
            dependencyId: 1,
            multiplier: 1,
            printReport141: false,
            condition141: 'Regular',
            invoiceNumber: null,
            invoiceDate: null,
            invoiceValue: null,
            invoiceSupplier: null,
        );

        $this->assertNull($data->code);
        $this->assertNull($data->brand);
        $this->assertNull($data->heightMeters);
        $this->assertNull($data->widthMeters);
        $this->assertNull($data->lengthMeters);
        $this->assertNull($data->invoiceNumber);
        $this->assertNull($data->invoiceDate);
        $this->assertNull($data->invoiceValue);
        $this->assertNull($data->invoiceSupplier);
        $this->assertFalse($data->printReport141);
    }

    /** @test */
    public function testCanBeCreatedWithEmptyStrings(): void
    {
        $data = new CreateLegacyProductData(
            churchId: 3,
            code: '',
            assetTypeId: 7,
            itemName: '',
            complement: '',
            brand: '',
            heightMeters: '',
            widthMeters: '',
            lengthMeters: '',
            dependencyId: 2,
            multiplier: 1,
            printReport141: false,
            condition141: '',
            invoiceNumber: null,
            invoiceDate: null,
            invoiceValue: null,
            invoiceSupplier: null,
        );

        $this->assertSame('', $data->code);
        $this->assertSame('', $data->itemName);
        $this->assertSame('', $data->complement);
        $this->assertSame('', $data->brand);
        $this->assertSame('', $data->condition141);
    }

    /** @test */
    public function testTypesAreCorrect(): void
    {
        $data = new CreateLegacyProductData(
            churchId: 1,
            code: 'CODE',
            assetTypeId: 2,
            itemName: 'Item',
            complement: 'Comp',
            brand: 'Brand',
            heightMeters: '1.0',
            widthMeters: '0.5',
            lengthMeters: '0.3',
            dependencyId: 3,
            multiplier: 5,
            printReport141: true,
            condition141: 'Ok',
            invoiceNumber: 999,
            invoiceDate: '2024-06-01',
            invoiceValue: '999.99',
            invoiceSupplier: 'Supp',
        );

        $this->assertIsInt($data->churchId);
        $this->assertIsString($data->code);
        $this->assertIsInt($data->assetTypeId);
        $this->assertIsString($data->itemName);
        $this->assertIsString($data->complement);
        $this->assertIsString($data->brand);
        $this->assertIsString($data->heightMeters);
        $this->assertIsString($data->widthMeters);
        $this->assertIsString($data->lengthMeters);
        $this->assertIsInt($data->dependencyId);
        $this->assertIsInt($data->multiplier);
        $this->assertIsBool($data->printReport141);
        $this->assertIsString($data->condition141);
        $this->assertIsInt($data->invoiceNumber);
        $this->assertIsString($data->invoiceDate);
        $this->assertIsString($data->invoiceValue);
        $this->assertIsString($data->invoiceSupplier);
    }
}

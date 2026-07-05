<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\UpdateLegacyProductData;
use Tests\TestCase;

final class UpdateLegacyProductDataTest extends TestCase
{
    /** @test */
    public function testCanBeCreatedWithAllFields(): void
    {
        $data = new UpdateLegacyProductData(
            editedAssetTypeId: 5,
            editedItemName: 'Cadeira de Metal',
            editedComplement: 'Braço direito reformado',
            editedBrand: 'MarcaX',
            editedHeightMeters: '1.25',
            editedWidthMeters: '0.55',
            editedLengthMeters: '0.65',
            editedDependencyId: 3,
            verified: true,
            printLabel: true,
            observation: 'Item em bom estado',
            printReport141: true,
            condition141: 'Bom',
            invoiceNumber: 12345,
            invoiceDate: '2024-01-15',
            invoiceValue: '1500.00',
            invoiceSupplier: 'Fornecedor Ltda',
        );

        $this->assertInstanceOf(UpdateLegacyProductData::class, $data);
        $this->assertSame(5, $data->editedAssetTypeId);
        $this->assertSame('Cadeira de Metal', $data->editedItemName);
        $this->assertSame('Braço direito reformado', $data->editedComplement);
        $this->assertSame('MarcaX', $data->editedBrand);
        $this->assertSame('1.25', $data->editedHeightMeters);
        $this->assertSame('0.55', $data->editedWidthMeters);
        $this->assertSame('0.65', $data->editedLengthMeters);
        $this->assertSame(3, $data->editedDependencyId);
        $this->assertTrue($data->verified);
        $this->assertTrue($data->printLabel);
        $this->assertSame('Item em bom estado', $data->observation);
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
        $data = new UpdateLegacyProductData(
            editedAssetTypeId: 10,
            editedItemName: 'Mesa',
            editedComplement: 'MDF',
            editedBrand: null,
            editedHeightMeters: null,
            editedWidthMeters: null,
            editedLengthMeters: null,
            editedDependencyId: 1,
            verified: false,
            printLabel: false,
            observation: '',
            printReport141: false,
            condition141: 'Ruim',
            invoiceNumber: null,
            invoiceDate: null,
            invoiceValue: null,
            invoiceSupplier: null,
        );

        $this->assertNull($data->editedBrand);
        $this->assertNull($data->editedHeightMeters);
        $this->assertNull($data->editedWidthMeters);
        $this->assertNull($data->editedLengthMeters);
        $this->assertNull($data->invoiceNumber);
        $this->assertNull($data->invoiceDate);
        $this->assertNull($data->invoiceValue);
        $this->assertNull($data->invoiceSupplier);
    }

    /** @test */
    public function testBooleanFieldsDefaultBehavior(): void
    {
        $data = new UpdateLegacyProductData(
            editedAssetTypeId: 1,
            editedItemName: 'Item',
            editedComplement: 'Comp',
            editedBrand: null,
            editedHeightMeters: null,
            editedWidthMeters: null,
            editedLengthMeters: null,
            editedDependencyId: 1,
            verified: true,
            printLabel: false,
            observation: 'Obs',
            printReport141: true,
            condition141: 'Ok',
            invoiceNumber: null,
            invoiceDate: null,
            invoiceValue: null,
            invoiceSupplier: null,
        );

        $this->assertTrue($data->verified);
        $this->assertFalse($data->printLabel);
        $this->assertTrue($data->printReport141);
    }

    /** @test */
    public function testTypesAreCorrect(): void
    {
        $data = new UpdateLegacyProductData(
            editedAssetTypeId: 2,
            editedItemName: 'Item',
            editedComplement: 'Comp',
            editedBrand: 'Br',
            editedHeightMeters: '1.0',
            editedWidthMeters: '0.5',
            editedLengthMeters: '0.3',
            editedDependencyId: 3,
            verified: true,
            printLabel: false,
            observation: 'Some observ',
            printReport141: false,
            condition141: 'Ok',
            invoiceNumber: 888,
            invoiceDate: '2024-07-01',
            invoiceValue: '500.00',
            invoiceSupplier: 'Supp',
        );

        $this->assertIsInt($data->editedAssetTypeId);
        $this->assertIsString($data->editedItemName);
        $this->assertIsString($data->editedComplement);
        $this->assertIsString($data->editedBrand);
        $this->assertIsString($data->editedHeightMeters);
        $this->assertIsString($data->editedWidthMeters);
        $this->assertIsString($data->editedLengthMeters);
        $this->assertIsInt($data->editedDependencyId);
        $this->assertIsBool($data->verified);
        $this->assertIsBool($data->printLabel);
        $this->assertIsString($data->observation);
        $this->assertIsBool($data->printReport141);
        $this->assertIsString($data->condition141);
        $this->assertIsInt($data->invoiceNumber);
        $this->assertIsString($data->invoiceDate);
        $this->assertIsString($data->invoiceValue);
        $this->assertIsString($data->invoiceSupplier);
    }
}

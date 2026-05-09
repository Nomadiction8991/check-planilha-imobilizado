<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\LegacyProductNameSupport;
use PHPUnit\Framework\TestCase;

final class LegacyProductNameSupportTest extends TestCase
{
    public function testFormatsDimensionsAtTheEndOfTheName(): void
    {
        self::assertSame(
            'CADEIRA METALICA TRAMONTINA A(1.2m) L(0.8m) C(2.5m)',
            LegacyProductNameSupport::formatName('CADEIRA', 'METALICA', 'TRAMONTINA', '1.200', '0.800', '2.500')
        );
    }

    public function testFormatsCurrentNameUsingEditedValuesWhenPresent(): void
    {
        $product = (object) [
            'bem' => 'CADEIRA',
            'complemento' => 'METALICA',
            'altura_m' => '1.100',
            'largura_m' => '0.900',
            'comprimento_m' => '2.000',
            'editado' => 1,
            'editado_bem' => 'MESA',
            'editado_complemento' => 'MADEIRA',
            'editado_marca' => 'TRAMONTINA',
            'editado_altura_m' => '1.300',
            'editado_largura_m' => null,
            'editado_comprimento_m' => '2.400',
        ];

        self::assertSame(
            'MESA MADEIRA TRAMONTINA A(1.3m) L(0.9m) C(2.4m)',
            LegacyProductNameSupport::formatCurrentName($product)
        );
    }
}

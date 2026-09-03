<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\LegacyProductClassificationSupport;
use PHPUnit\Framework\TestCase;

final class LegacyProductClassificationSupportTest extends TestCase
{
    public function testEditedProductUsesEditedTypeAndDependency(): void
    {
        $product = (object) [
            'editado' => 1,
            'tipoBem' => (object) ['codigo' => '4', 'descricao' => 'CADEIRA'],
            'dependencia' => (object) ['descricao' => 'SALAO'],
            'editadoTipoBem' => (object) ['codigo' => '7', 'descricao' => 'MESA'],
            'editadoDependencia' => (object) ['descricao' => 'SECRETARIA'],
        ];

        self::assertSame('7 - MESA', LegacyProductClassificationSupport::currentTypeLabel($product));
        self::assertSame('MESA', LegacyProductClassificationSupport::currentTypeDescription($product));
        self::assertSame('SECRETARIA', LegacyProductClassificationSupport::currentDependencyDescription($product));
    }

    public function testUneditedProductUsesOriginalTypeAndDependency(): void
    {
        $product = (object) [
            'editado' => 0,
            'tipoBem' => (object) ['codigo' => '4', 'descricao' => 'CADEIRA'],
            'dependencia' => (object) ['descricao' => 'SALAO'],
            'editadoTipoBem' => (object) ['codigo' => '7', 'descricao' => 'MESA'],
            'editadoDependencia' => (object) ['descricao' => 'SECRETARIA'],
        ];

        self::assertSame('4 - CADEIRA', LegacyProductClassificationSupport::currentTypeLabel($product));
        self::assertSame('CADEIRA', LegacyProductClassificationSupport::currentTypeDescription($product));
        self::assertSame('SALAO', LegacyProductClassificationSupport::currentDependencyDescription($product));
    }

    public function testEditedProductFallsBackToOriginalWhenEditedRelationsAreMissing(): void
    {
        $product = (object) [
            'editado' => 1,
            'tipoBem' => (object) ['codigo' => '4', 'descricao' => 'CADEIRA'],
            'dependencia' => (object) ['descricao' => 'SALAO'],
            'editadoTipoBem' => null,
            'editadoDependencia' => null,
        ];

        self::assertSame('4 - CADEIRA', LegacyProductClassificationSupport::currentTypeLabel($product));
        self::assertSame('CADEIRA', LegacyProductClassificationSupport::currentTypeDescription($product));
        self::assertSame('SALAO', LegacyProductClassificationSupport::currentDependencyDescription($product));
    }

    public function testEditedProductFallsBackWhenEditedRelationsHaveNoDisplayValue(): void
    {
        $product = (object) [
            'editado' => 1,
            'tipoBem' => (object) ['codigo' => '4', 'descricao' => 'CADEIRA'],
            'dependencia' => (object) ['descricao' => 'SALAO'],
            'editadoTipoBem' => (object) ['codigo' => '', 'descricao' => ''],
            'editadoDependencia' => (object) ['descricao' => ''],
        ];

        self::assertSame('4 - CADEIRA', LegacyProductClassificationSupport::currentTypeLabel($product));
        self::assertSame('SALAO', LegacyProductClassificationSupport::currentDependencyDescription($product));
    }
}

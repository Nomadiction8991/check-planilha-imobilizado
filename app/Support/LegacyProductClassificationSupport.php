<?php

declare(strict_types=1);

namespace App\Support;

use function data_get;

final class LegacyProductClassificationSupport
{
    public static function currentTypeLabel(mixed $product): string
    {
        $type = self::currentType($product);
        $code = trim((string) data_get($type, 'codigo', ''));
        $description = self::currentTypeDescription($product);

        if ($code === '') {
            return $description;
        }

        return $description !== '' ? $code . ' - ' . $description : $code;
    }

    public static function currentTypeDescription(mixed $product): string
    {
        return trim((string) data_get(self::currentType($product), 'descricao', ''));
    }

    public static function currentDependencyDescription(mixed $product): string
    {
        return trim((string) data_get(self::currentDependency($product), 'descricao', ''));
    }

    private static function currentType(mixed $product): mixed
    {
        return self::useEditedRelation($product, 'editadoTipoBem')
            ? data_get($product, 'editadoTipoBem')
            : data_get($product, 'tipoBem');
    }

    private static function currentDependency(mixed $product): mixed
    {
        return self::useEditedRelation($product, 'editadoDependencia')
            ? data_get($product, 'editadoDependencia')
            : data_get($product, 'dependencia');
    }

    private static function useEditedRelation(mixed $product, string $relation): bool
    {
        $editedRelation = data_get($product, $relation);

        return (int) data_get($product, 'editado', 0) === 1
            && $editedRelation !== null
            && self::relationHasValue($editedRelation);
    }

    private static function relationHasValue(mixed $relation): bool
    {
        return trim((string) data_get($relation, 'descricao', '')) !== ''
            || trim((string) data_get($relation, 'codigo', '')) !== '';
    }
}

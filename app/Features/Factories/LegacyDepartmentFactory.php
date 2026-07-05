<?php

namespace App\Features\Factories;

use App\Models\Legacy\Dependencia;
use Faker\Factory as Faker;

class LegacyDepartmentFactory
{
    public static function definition()
    {
        return Faker::define('departments', function (Faker $faker) {
            return [
                'comum_id' => $faker->numberBetween(1, 100),
                'descricao' => $faker->sentence(6),
            ];
        });
    }
}

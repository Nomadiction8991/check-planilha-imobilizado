<?php

namespace App\Routes;

use App\Controllers\AuthController;

class MapaRotas
{
    public static function obter(): array
    {
        return [
            'GET /' => [AuthController::class, 'login'],
            'GET /login' => [AuthController::class, 'login'],
            'POST /login' => [AuthController::class, 'authenticate'],
        ];
    }
}

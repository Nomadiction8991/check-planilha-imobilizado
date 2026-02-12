<?php

namespace App\Routes;

use App\Controllers\AuthController;
use App\Controllers\ComumController;
use App\Controllers\UsuarioController;

class MapaRotas
{
    public static function obter(): array
    {
        return [
            
            'GET /' => [AuthController::class, 'login'],
            'GET /login' => [AuthController::class, 'login'],
            'POST /login' => [AuthController::class, 'authenticate'],
            'GET /logout' => [AuthController::class, 'logout'],

            
            'GET /comuns' => [ComumController::class, 'index'],

            
            'GET /usuarios' => [UsuarioController::class, 'index'],
            'GET /usuarios/criar' => [UsuarioController::class, 'create'],
            'POST /usuarios/criar' => [UsuarioController::class, 'store'],
            'GET /usuarios/editar' => [UsuarioController::class, 'edit'],
            'POST /usuarios/editar' => [UsuarioController::class, 'update'],
            'POST /usuarios/deletar' => [UsuarioController::class, 'delete'],
        ];
    }
}

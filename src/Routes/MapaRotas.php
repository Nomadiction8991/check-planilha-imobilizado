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
            // Autenticação
            'GET /' => [AuthController::class, 'login'],
            'GET /login' => [AuthController::class, 'login'],
            'POST /login' => [AuthController::class, 'authenticate'],
            'GET /logout' => [AuthController::class, 'logout'],

            // Comuns (gerenciamento)
            'GET /comuns' => [ComumController::class, 'index'],
            
            // Usuários (CRUD completo)
            'GET /usuarios' => [UsuarioController::class, 'index'],
            'GET /usuarios/criar' => [UsuarioController::class, 'create'],
            'POST /usuarios/criar' => [UsuarioController::class, 'store'],
            'GET /usuarios/editar' => [UsuarioController::class, 'edit'],
            'POST /usuarios/editar' => [UsuarioController::class, 'update'],
            'POST /usuarios/deletar' => [UsuarioController::class, 'delete'],
        ];
    }
}

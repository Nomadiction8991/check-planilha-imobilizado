<?php

namespace App\Routes;

use App\Controllers\AuthController;
use App\Controllers\ComumController;
use App\Controllers\UsuarioController;
use App\Controllers\DependenciaController;
use App\Controllers\ProdutoController;
use App\Controllers\PlanilhaController;
use App\Controllers\RelatorioController;

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
            'GET /comuns/editar' => [ComumController::class, 'edit'],
            'POST /comuns/editar' => [ComumController::class, 'update'],

            
            'GET /usuarios' => [UsuarioController::class, 'index'],
            'GET /usuarios/criar' => [UsuarioController::class, 'create'],
            'POST /usuarios/criar' => [UsuarioController::class, 'store'],
            'GET /usuarios/editar' => [UsuarioController::class, 'edit'],
            'POST /usuarios/editar' => [UsuarioController::class, 'update'],
            'POST /usuarios/deletar' => [UsuarioController::class, 'delete'],

            
            'GET /dependencias' => [DependenciaController::class, 'index'],
            'GET /dependencias/criar' => [DependenciaController::class, 'create'],
            'POST /dependencias/criar' => [DependenciaController::class, 'store'],
            'GET /dependencias/editar' => [DependenciaController::class, 'edit'],
            'POST /dependencias/editar' => [DependenciaController::class, 'update'],
            'POST /dependencias/deletar' => [DependenciaController::class, 'delete'],

            
            'GET /produtos' => [ProdutoController::class, 'index'],
            'GET /produtos/criar' => [ProdutoController::class, 'create'],
            'POST /produtos/criar' => [ProdutoController::class, 'store'],
            'GET /produtos/editar' => [ProdutoController::class, 'edit'],
            'POST /produtos/editar' => [ProdutoController::class, 'update'],
            'POST /produtos/deletar' => [ProdutoController::class, 'delete'],
            'POST /produtos/observacao' => [ProdutoController::class, 'observacao'],
            'POST /produtos/check' => [ProdutoController::class, 'check'],
            'POST /produtos/etiqueta' => [ProdutoController::class, 'etiqueta'],
            'POST /produtos/assinar' => [ProdutoController::class, 'assinar'],

            
            'GET /planilhas/importar' => [PlanilhaController::class, 'importar'],
            'POST /planilhas/importar' => [PlanilhaController::class, 'processarImportacao'],
            'GET /planilhas/visualizar' => [PlanilhaController::class, 'visualizar'],
            'GET /planilhas/progresso' => [PlanilhaController::class, 'progresso'],

            
            'GET /relatorios/14-1' => [RelatorioController::class, 'relatorio141'],
            'GET /relatorios/visualizar' => [RelatorioController::class, 'visualizar'],
            'GET /relatorios/assinatura' => [RelatorioController::class, 'assinatura'],
        ];
    }
}

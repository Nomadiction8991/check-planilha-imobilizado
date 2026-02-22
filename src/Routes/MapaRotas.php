<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controllers\AuthController;
use App\Controllers\ComumController;
use App\Controllers\UsuarioController;
use App\Controllers\DependenciaController;
use App\Controllers\ProdutoController;
use App\Controllers\PlanilhaController;
use App\Controllers\RelatorioController;
use App\Controllers\TipoBemController;
use App\Controllers\ErrosImportacaoController;

class MapaRotas
{
    public static function obter(): array
    {
        return [

            'GET /' => [AuthController::class, 'login'],
            'GET /login' => [AuthController::class, 'login'],
            'POST /login' => [AuthController::class, 'authenticate'],
            'GET /logout' => [AuthController::class, 'logout'],


            'GET /churches' => [ComumController::class, 'index'],
            'GET /churches/edit' => [ComumController::class, 'edit'],
            'POST /churches/edit' => [ComumController::class, 'update'],
            'POST /churches/delete-products' => [ComumController::class, 'deleteProducts'],

            // Menu (página que centraliza cabeçalho / footer / navegação)
            'GET /menu' => [\App\Controllers\MenuController::class, 'index'],


            'GET /asset-types' => [TipoBemController::class, 'index'],
            'GET /asset-types/create' => [TipoBemController::class, 'create'],
            'POST /asset-types/create' => [TipoBemController::class, 'store'],
            'GET /asset-types/:id/edit' => [TipoBemController::class, 'edit'],
            'POST /asset-types/:id/edit' => [TipoBemController::class, 'update'],
            'POST /asset-types/delete' => [TipoBemController::class, 'delete'],


            'GET /users' => [UsuarioController::class, 'index'],
            'GET /users/create' => [UsuarioController::class, 'create'],
            'POST /users/create' => [UsuarioController::class, 'store'],
            'GET /users/show' => [UsuarioController::class, 'show'],
            'GET /users/edit' => [UsuarioController::class, 'edit'],
            'POST /users/edit' => [UsuarioController::class, 'update'],
            'POST /users/delete' => [UsuarioController::class, 'delete'],
            'POST /users/select-church' => [UsuarioController::class, 'selecionarComum'],


            'GET /departments' => [DependenciaController::class, 'index'],
            'GET /departments/create' => [DependenciaController::class, 'create'],
            'POST /departments/create' => [DependenciaController::class, 'store'],
            'GET /departments/edit' => [DependenciaController::class, 'edit'],
            'POST /departments/edit' => [DependenciaController::class, 'update'],
            'POST /departments/delete' => [DependenciaController::class, 'delete'],


            'GET /products/view' => [PlanilhaController::class, 'visualizar'],
            'GET /products/create' => [ProdutoController::class, 'create'],
            'POST /products/create' => [ProdutoController::class, 'store'],
            'GET /products/edit' => [ProdutoController::class, 'edit'],
            'POST /products/edit' => [ProdutoController::class, 'update'],
            'POST /products/delete' => [ProdutoController::class, 'delete'],
            'GET /products/observation' => [ProdutoController::class, 'observacaoForm'],
            'POST /products/observation' => [ProdutoController::class, 'observacao'],
            'POST /products/check' => [ProdutoController::class, 'check'],
            'GET /products/label' => [ProdutoController::class, 'etiqueta'],
            'POST /products/label' => [ProdutoController::class, 'etiqueta'],
            'POST /products/sign' => [ProdutoController::class, 'assinar'],


            'GET /spreadsheets/import' => [PlanilhaController::class, 'importar'],
            'POST /spreadsheets/import' => [PlanilhaController::class, 'processarImportacao'],
            'GET /spreadsheets/view' => [PlanilhaController::class, 'visualizar'],  // Mantido para compatibilidade, redireciona
            'GET /spreadsheets/preview' => [PlanilhaController::class, 'preview'],
            'POST /spreadsheets/preview/save-actions' => [PlanilhaController::class, 'salvarAcoesPreview'],
            'POST /spreadsheets/preview/bulk-action' => [PlanilhaController::class, 'acaoMassaPreview'],
            'POST /spreadsheets/confirm' => [PlanilhaController::class, 'confirmarImportacao'],


            'GET /spreadsheets/import-errors'          => [ErrosImportacaoController::class, 'listar'],
            'GET /spreadsheets/import-errors/download'  => [ErrosImportacaoController::class, 'downloadCsv'],
            'POST /spreadsheets/import-errors/resolver' => [ErrosImportacaoController::class, 'marcarResolvido'],


            'GET /reports/14-1' => [RelatorioController::class, 'relatorio141'],
            'GET /reports/view' => [RelatorioController::class, 'visualizar'],
            'GET /reports/alteracoes' => [RelatorioController::class, 'alteracoes'],
            'GET /reports/signature' => [RelatorioController::class, 'assinatura'],
        ];
    }
}

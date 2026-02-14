<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Core\SessionManager;
use App\Repositories\DependenciaRepository;
use App\Repositories\ProdutoRepository;
use App\Repositories\TipoBemRepository;
use App\Services\TipoBemService;
use PDO;

class ProdutoController extends BaseController
{
    private ProdutoRepository $produtoRepository;
    private TipoBemService $tipoBemService;
    private DependenciaRepository $dependenciaRepository;
    private PDO $conexao;

    public function __construct(?PDO $conexao = null)
    {
        $conexao = $conexao ?? ConnectionManager::getConnection();
        $this->conexao = $conexao;

        $this->produtoRepository = new ProdutoRepository($conexao);
        $this->dependenciaRepository = new DependenciaRepository($conexao);

        $tipoBemRepo = new TipoBemRepository($conexao);
        $this->tipoBemService = new TipoBemService($tipoBemRepo);
    }

    public function index(): void
    {
        $comumId = SessionManager::getComumId();

        if (!$comumId || $comumId <= 0) {
            $this->redirecionar('/churches?mensagem=' . urlencode('Selecione um Comum para ver os produtos'));
            return;
        }

        $pagina = max(1, (int) ($this->query('pagina', 1)));
        $limite = 10;

        $filtros = [
            'filtro_complemento' => trim($this->query('filtro_complemento', '')),
            'pesquisa_id'        => trim($this->query('pesquisa_id', '')),
            'filtro_tipo_ben'    => trim($this->query('filtro_tipo_ben', '')),
            'filtro_bem'         => trim($this->query('filtro_bem', '')),
            'filtro_dependencia' => trim($this->query('filtro_dependencia', '')),
            'filtro_STATUS'      => trim($this->query('filtro_STATUS', '')),
        ];

        $resultado = $this->produtoRepository->buscarPorComumPaginado($comumId, $pagina, $limite, $filtros);

        try {
            $tiposBens = $this->tipoBemService->buscarTodos();
        } catch (\Throwable $e) {
            error_log('Erro ao buscar tipos_bens: ' . $e->getMessage());
            $tiposBens = [];
        }

        $bemCodigos = $this->produtoRepository->buscarDistintosCodigos($comumId);
        $dependencias = $this->dependenciaRepository->buscarTodos();

        $this->renderizar('products/index', [
            'comum_id'           => $comumId,
            'produtos'           => $resultado['dados'],
            'pagina'             => $resultado['pagina'],
            'total_paginas'      => $resultado['totalPaginas'],
            'total_registros'    => $resultado['total'],
            'filtro_complemento' => $filtros['filtro_complemento'],
            'pesquisa_id'        => $filtros['pesquisa_id'],
            'filtro_tipo_ben'    => $filtros['filtro_tipo_ben'],
            'filtro_bem'         => $filtros['filtro_bem'],
            'filtro_dependencia' => $filtros['filtro_dependencia'],
            'filtro_STATUS'      => $filtros['filtro_STATUS'],
            'tipos_bens'         => $tiposBens,
            'bem_codigos'        => $bemCodigos,
            'dependencias'       => $dependencias,
        ]);
    }

    public function create(): void
    {
        $comumId = SessionManager::getComumId();

        if (!$comumId || $comumId <= 0) {
            $this->redirecionar('/churches?mensagem=' . urlencode('Selecione um Comum para criar um produto'));
            return;
        }

        try {
            $tiposBens = $this->tipoBemService->buscarTodos();
        } catch (\Throwable $e) {
            error_log('Erro ao buscar tipos_bens: ' . $e->getMessage());
            $tiposBens = [];
        }

        $this->renderizar('products/create', [
            'comum_id'   => $comumId,
            'tipos_bens' => $tiposBens,
        ]);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirecionar('/products');
            return;
        }

        http_response_code(501);
        die('Funcionalidade em implementação.');
    }

    public function edit(): void
    {
        $id = (int) ($this->query('id', 0));
        if ($id <= 0) {
            $this->redirecionar('/products?erro=ID inválido');
            return;
        }

        try {
            $tiposBens = $this->tipoBemService->buscarTodos();
        } catch (\Throwable $e) {
            error_log('Erro ao buscar tipos_bens: ' . $e->getMessage());
            $tiposBens = [];
        }

        $this->renderizar('products/edit', [
            'id'         => $id,
            'tipos_bens' => $tiposBens,
        ]);
    }

    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirecionar('/products');
            return;
        }

        http_response_code(501);
        die('Funcionalidade em implementação.');
    }

    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        $this->jsonErro('Funcionalidade em implementação.', 501);
    }

    public function observacao(): void
    {
        if (!$this->isPost()) {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        $comumId = SessionManager::getComumId();
        if (!$comumId) {
            $this->jsonErro('Comum não selecionada', 400);
            return;
        }

        $produtoId = (int) ($this->post('produto_id', 0));
        $observacao = trim($this->post('observacao', ''));

        if ($produtoId <= 0) {
            $this->jsonErro('Produto inválido', 400);
            return;
        }

        try {
            $this->produtoRepository->atualizarObservacao($produtoId, $comumId, $observacao);
            $this->json(['sucesso' => true, 'mensagem' => 'Observação salva com sucesso']);
        } catch (\Exception $e) {
            error_log('Erro ao salvar observação: ' . $e->getMessage());
            $this->jsonErro('Erro ao salvar observação', 500);
        }
    }

    public function check(): void
    {
        if (!$this->isPost()) {
            $this->redirecionar('/spreadsheets/view');
            return;
        }

        $comumId = SessionManager::getComumId();
        if (!$comumId) {
            $this->redirecionar('/spreadsheets/view?erro=Comum não selecionada');
            return;
        }

        $produtoId = (int) ($this->post('produto_id', 0));
        $checado = (int) ($this->post('checado', 0));

        if ($produtoId <= 0) {
            $this->redirecionar('/spreadsheets/view?erro=Produto inválido');
            return;
        }

        try {
            $this->produtoRepository->atualizarChecado($produtoId, $comumId, $checado);
            $this->redirecionar('/spreadsheets/view?sucesso=Produto atualizado');
        } catch (\Exception $e) {
            error_log('Erro ao atualizar check: ' . $e->getMessage());
            $this->redirecionar('/spreadsheets/view?erro=Erro ao atualizar produto');
        }
    }

    public function etiqueta(): void
    {
        if ($this->isGet()) {
            $comumId = SessionManager::getComumId();
            $this->renderizar('spreadsheets/copy-labels', [
                'id_planilha' => $this->query('id', $comumId),
                'comum_id'    => $comumId,
                'conexao'     => $this->conexao,
            ]);
            return;
        }

        $comumId = SessionManager::getComumId();
        if (!$comumId) {
            $this->redirecionar('/spreadsheets/view?erro=Comum não selecionada');
            return;
        }

        $produtoId = (int) ($this->post('produto_id', 0));
        $imprimir = (int) ($this->post('imprimir', 0));

        if ($produtoId <= 0) {
            $this->redirecionar('/spreadsheets/view?erro=Produto inválido');
            return;
        }

        try {
            $this->produtoRepository->atualizarEtiqueta($produtoId, $comumId, $imprimir);
            $this->redirecionar('/spreadsheets/view?sucesso=Etiqueta atualizada');
        } catch (\Exception $e) {
            error_log('Erro ao atualizar etiqueta: ' . $e->getMessage());
            $this->redirecionar('/spreadsheets/view?erro=Erro ao atualizar etiqueta');
        }
    }

    public function assinar(): void
    {
        if (!$this->isPost()) {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        $this->jsonErro('Funcionalidade em implementação.', 501);
    }
}

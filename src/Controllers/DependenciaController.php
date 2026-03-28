<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DependenciaService;
use App\Repositories\DependenciaRepository;
use App\Core\ConnectionManager;
use App\Core\SessionManager;
use PDO;

class DependenciaController extends BaseController
{
    private DependenciaService $dependenciaService;

    public function __construct(?PDO $conexao = null)
    {
        if ($conexao === null) {
            $conexao = ConnectionManager::getConnection();
        }

        $dependenciaRepo = new DependenciaRepository($conexao);
        $this->dependenciaService = new DependenciaService($dependenciaRepo);
    }

    public function index(): void
    {
        $comumId = SessionManager::getComumId();

        $busca = trim($this->query('busca', ''));
        $pagina = max(1, (int) $this->query('pagina', 1));
        $limite = 20;
        $offset = ($pagina - 1) * $limite;

        try {
            $dependencias = $this->dependenciaService->buscarPaginadoPorComum($comumId, $busca, $limite, $offset);

            $total = $this->dependenciaService->contarPorComum($comumId, $busca);
            $totalGeral = $this->dependenciaService->contarPorComum($comumId);
            $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;

            if ($this->query('ajax') === '1') {
                $this->retornarAjax($dependencias, $total, $totalGeral, $pagina, $totalPaginas, $busca);
                return;
            }

            $this->renderizarIndex($dependencias, $busca, $pagina, $limite, $total, $totalGeral, $totalPaginas);
        } catch (\Throwable $e) {
            $this->tratarErro($e, $busca, $pagina);
        }
    }

    private function renderizarIndex(
        array $dependencias,
        string $busca,
        int $pagina,
        int $limite,
        int $total,
        int $totalGeral,
        int $totalPaginas
    ): void {
        $this->renderizar('departments/index', [
            'dependencias' => $dependencias,
            'busca' => $busca,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_registros' => $total,
            'total_registros_all' => $totalGeral,
            'total_paginas' => $totalPaginas,
        ]);
    }

    private function tratarErro(\Throwable $e, string $busca, int $pagina): void
    {
        error_log('Erro no DependenciaController: ' . $e->getMessage());

        $this->renderizar('departments/index', [
            'dependencias' => [],
            'busca' => $busca,
            'pagina' => $pagina,
            'limite' => 10,
            'total_registros' => 0,
            'total_registros_all' => 0,
            'total_paginas' => 1,
            'erro' => 'Erro ao carregar dependências: ' . $e->getMessage(),
        ]);
    }

    private function retornarAjax(
        array $dependencias,
        int $total,
        int $totalGeral,
        int $pagina,
        int $totalPaginas,
        string $busca
    ): void {
        header('Content-Type: application/json');
        echo json_encode([
            'dependencias' => $dependencias,
            'total' => $total,
            'totalGeral' => $totalGeral,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'busca' => $busca,
        ]);
        exit;
    }

    public function create(): void
    {
        $this->renderizar('departments/create');
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirecionar('/departments');
            return;
        }

        try {
            $comumId = SessionManager::getComumId();
            if (!$comumId) {
                throw new \Exception('Selecione uma igreja antes de cadastrar uma dependência.');
            }

            $dados = [
                'comum_id' => $comumId,
                'descricao' => trim($this->post('descricao', '')),
            ];

            $this->dependenciaService->criar($dados);
            $this->redirecionar('/departments?success=1');
        } catch (\Exception $e) {
            $this->redirecionar('/departments/create?erro=' . urlencode($e->getMessage()));
        }
    }

    public function edit(): void
    {
        $comumId = SessionManager::getComumId() ?? 0;
        $id = (int) $this->query('id', 0);
        if ($id <= 0) {
            $this->redirecionar('/departments?erro=ID inválido');
            return;
        }

        $dependencia = $this->dependenciaService->buscarPorIdEComum($id, $comumId);
        if (!$dependencia) {
            $this->redirecionar('/departments?erro=Dependência não encontrada para a igreja selecionada');
            return;
        }

        $this->renderizar('departments/edit', [
            'id' => $id,
            'dependencia' => $dependencia,
        ]);
    }

    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirecionar('/departments');
            return;
        }

        $comumId = SessionManager::getComumId() ?? 0;
        $id = (int) $this->post('id', 0);
        if ($id <= 0) {
            $this->redirecionar('/departments?erro=ID inválido');
            return;
        }

        try {
            if (!$this->dependenciaService->buscarPorIdEComum($id, $comumId)) {
                throw new \Exception('Dependência não encontrada para a igreja selecionada.');
            }

            $dados = [
                'descricao' => trim($this->post('descricao', '')),
            ];

            $this->dependenciaService->atualizar($id, $dados);
            $this->redirecionar('/departments?success=1');
        } catch (\Exception $e) {
            $this->redirecionar('/departments/edit?id=' . $id . '&erro=' . urlencode($e->getMessage()));
        }
    }

    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        $comumId = SessionManager::getComumId() ?? 0;
        $id = (int) $this->post('id', 0);
        if ($id <= 0) {
            $this->jsonErro('ID inválido', 400);
            return;
        }

        try {
            $this->dependenciaService->deletarPorIdEComum($id, $comumId);
            $this->json([
                'success' => true,
                'message' => 'Dependência excluída com sucesso',
                'sucesso' => true,
                'mensagem' => 'Dependência excluída com sucesso'
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ], 400);
        }
    }
}

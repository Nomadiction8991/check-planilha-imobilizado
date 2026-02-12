<?php

namespace App\Controllers;

use App\Services\TipoBemService;
use App\Repositories\TipoBemRepository;
use App\Core\ViewRenderer;
use App\Core\ConnectionManager;
use PDO;

class TipoBemController extends BaseController
{
    private TipoBemService $tipoBemService;

    public function __construct(?PDO $conexao = null)
    {
        if ($conexao === null) {
            $conexao = ConnectionManager::getConnection();
        }

        $tipoBemRepo = new TipoBemRepository($conexao);
        $this->tipoBemService = new TipoBemService($tipoBemRepo);
    }

    public function index(): void
    {
        $busca = trim($this->query('busca', ''));
        $pagina = max(1, (int) $this->query('pagina', 1));
        $limite = 20;
        $offset = ($pagina - 1) * $limite;

        try {
            $tipos = $this->tipoBemService->buscarPaginado($busca, $limite, $offset);
            $total = $this->tipoBemService->contar($busca);
            $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;

            ViewRenderer::render('tipos_bens/index', [
                'pageTitle' => 'TIPOS DE BENS',
                'headerActions' => '',
                'tipos' => $tipos,
                'busca' => $busca,
                'pagina' => $pagina,
                'total' => $total,
                'totalPaginas' => $totalPaginas
            ]);
        } catch (\Throwable $e) {
            error_log('Erro TipoBemController::index: ' . $e->getMessage());
            
            // Renderiza a view mesmo com erro, mostrando lista vazia
            ViewRenderer::render('tipos_bens/index', [
                'pageTitle' => 'TIPOS DE BENS',
                'headerActions' => '',
                'tipos' => [],
                'busca' => $busca,
                'pagina' => 1,
                'total' => 0,
                'totalPaginas' => 1,
                'erro' => 'Erro ao carregar tipos de bens: ' . $e->getMessage()
            ]);
        }
    }

    public function create(): void
    {
        ViewRenderer::render('tipos_bens/criar', [
            'pageTitle' => 'CADASTRAR TIPO DE BEM',
            'headerActions' => ''
        ]);
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/tipos-bens');
            return;
        }

        try {
            $dados = [
                'codigo' => trim($_POST['codigo'] ?? ''),
                'descricao' => trim($_POST['descricao'] ?? '')
            ];

            // Validações
            if (empty($dados['codigo'])) {
                throw new \Exception('Código é obrigatório.');
            }
            if (empty($dados['descricao'])) {
                throw new \Exception('Descrição é obrigatória.');
            }

            $this->tipoBemService->criar($dados);

            $this->setMensagem('Tipo de bem cadastrado com sucesso!', 'success');
            $this->redirecionar('/tipos-bens');
        } catch (\Throwable $e) {
            error_log('Erro TipoBemController::store: ' . $e->getMessage());
            $this->setMensagem('Erro ao cadastrar tipo de bem: ' . $e->getMessage(), 'danger');
            $this->redirecionar('/tipos-bens/criar?erro=' . urlencode($e->getMessage()));
        }
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirecionar('/tipos-bens?erro=' . urlencode('ID inválido'));
            return;
        }

        try {
            $tipo = $this->tipoBemService->buscarPorId($id);

            if (!$tipo) {
                $this->setMensagem('Tipo de bem não encontrado.', 'danger');
                $this->redirecionar('/tipos-bens');
                return;
            }

            ViewRenderer::render('tipos_bens/editar', [
                'pageTitle' => 'EDITAR TIPO DE BEM',
                'headerActions' => '',
                'tipo' => $tipo
            ]);
        } catch (\Throwable $e) {
            error_log('Erro TipoBemController::edit: ' . $e->getMessage());
            $this->setMensagem('Erro ao carregar tipo de bem: ' . $e->getMessage(), 'danger');
            $this->redirecionar('/tipos-bens');
        }
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/tipos-bens');
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->setMensagem('ID inválido.', 'danger');
            $this->redirecionar('/tipos-bens');
            return;
        }

        try {
            $dados = [
                'codigo' => trim($_POST['codigo'] ?? ''),
                'descricao' => trim($_POST['descricao'] ?? '')
            ];

            // Validações
            if (empty($dados['codigo'])) {
                throw new \Exception('Código é obrigatório.');
            }
            if (empty($dados['descricao'])) {
                throw new \Exception('Descrição é obrigatória.');
            }

            $this->tipoBemService->atualizar($id, $dados);

            $this->setMensagem('Tipo de bem atualizado com sucesso!', 'success');
            $this->redirecionar('/tipos-bens');
        } catch (\Throwable $e) {
            error_log('Erro TipoBemController::update: ' . $e->getMessage());
            $this->setMensagem('Erro ao atualizar tipo de bem: ' . $e->getMessage(), 'danger');
            $this->redirecionar('/tipos-bens/editar?id=' . $id);
        }
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido'], 405);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }

        try {
            $this->tipoBemService->deletar($id);
            $this->json(['success' => true, 'message' => 'Tipo de bem excluído com sucesso!']);
        } catch (\Throwable $e) {
            error_log('Erro TipoBemController::delete: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

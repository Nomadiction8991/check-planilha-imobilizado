<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ComumService;
use App\Repositories\ComumRepository;
use App\Core\ViewRenderer;
use App\Core\ConnectionManager;
use PDO;

class ComumController extends BaseController
{
    private ComumService $comumService;

    public function __construct(?PDO $conexao = null)
    {
        if ($conexao === null) {
            $conexao = ConnectionManager::getConnection();
        }

        $comumRepo = new ComumRepository($conexao);
        $this->comumService = new ComumService($comumRepo);
    }

    public function index(): void
    {
        $busca = trim($this->query('busca', ''));
        $pagina = max(1, (int) $this->query('pagina', 1));
        $limite = 10;
        $offset = ($pagina - 1) * $limite;

        try {
            $comuns = $this->comumService->buscarPaginado($busca, $limite, $offset);

            $total = $this->comumService->contar($busca);
            $totalGeral = $this->comumService->contar();
            $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;

            if ($this->query('ajax') === '1') {
                $this->retornarAjax($comuns, $total, $totalGeral, $pagina, $totalPaginas, $busca);
                return;
            }

            $this->renderizarIndex($comuns, $busca, $pagina, $limite, $total, $totalGeral, $totalPaginas);
        } catch (\Throwable $e) {
            $this->tratarErro($e, $busca, $pagina);
        }
    }

    private function renderizarIndex(
        array $comuns,
        string $busca,
        int $pagina,
        int $limite,
        int $total,
        int $totalGeral,
        int $totalPaginas
    ): void {
        ViewRenderer::render('churches/index', [
            'pageTitle' => 'COMUNS',
            'backUrl' => null,
            'headerActions' => $this->gerarHeaderActions(),
            'customCss' => $this->getCustomCss(),
            'comuns' => $comuns,
            'busca' => $busca,
            'pagina' => $pagina,
            'limite' => $limite,
            'total' => $total,
            'totalPaginas' => $totalPaginas
        ]);
    }

    private function retornarAjax(
        array $comuns,
        int $total,
        int $totalGeral,
        int $pagina,
        int $totalPaginas,
        string $busca
    ): void {
        $rowsHtml = $this->gerarLinhasTabela($comuns, $busca, $pagina);

        $this->json([
            'rows' => $rowsHtml,
            'count' => $total,
            'count_all' => $totalGeral,
            'rows_count' => count($comuns),
            'page' => $pagina,
            'total_pages' => $totalPaginas
        ]);
    }

    private function gerarLinhasTabela(array $comuns, string $busca, int $pagina): string
    {
        if (empty($comuns)) {
            return '<tr><td colspan="3" class="text-center py-4 text-muted">' .
                '<i class="bi bi-inbox fs-3 d-block mb-2"></i>NENHUM COMUM ENCONTRADO</td></tr>';
        }

        $html = '';
        foreach ($comuns as $comum) {
            $cadastroOk = $this->verificarCadastroCompleto($comum);

            $qsEdit = http_build_query(['busca' => $busca, 'pagina' => $pagina]);
            $editHref = '/churches/edit?id=' . (int) $comum['id'] .
                ($qsEdit ? ('&' . $qsEdit) : '');
            $viewHref = '/products/view?comum_id=' . (int) $comum['id'];

            $html .= '<tr>';
            $html .= '<td class="fw-semibold text-uppercase">' . htmlspecialchars($comum['codigo']) . '</td>';
            $html .= '<td class="text-uppercase">' . htmlspecialchars($comum['descricao']) . '</td>';
            $html .= '<td>';
            $html .= '<div class="btn-group btn-group-sm" role="group">';
            $html .= '<a class="btn btn-outline-primary" href="' . $editHref . '" title="Editar">' .
                '<i class="bi bi-pencil"></i></a>';
            $html .= '<a class="btn btn-outline-secondary btn-view-planilha" href="' . $viewHref . '" ' .
                'data-cadastro-ok="' . ($cadastroOk ? '1' : '0') . '" ' .
                'data-edit-url="' . $editHref . '" title="Visualizar planilha">' .
                '<i class="bi bi-eye"></i></a>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    private function verificarCadastroCompleto(array $comum): bool
    {
        return trim((string) $comum['descricao']) !== ''
            && trim((string) $comum['cnpj']) !== ''
            && trim((string) $comum['administracao']) !== ''
            && trim((string) $comum['cidade']) !== '';
    }


    private function gerarHeaderActions(): string
    {
        // Verificar se usuário está logado (função global do auth_helper.php)
        if (!function_exists('isLoggedIn') || !\isLoggedIn()) {
            return '';
        }

        $actions = '
        <div class="dropdown">
            <button class="btn-header-action" type="button" id="menuPrincipal" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-list fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuPrincipal">
                <li><a class="dropdown-item" href="/users">
                    <i class="bi bi-people me-2"></i>LISTAGEM DE USUÁRIOS</a></li>
                <li><a class="dropdown-item" href="/departments">
                    <i class="bi bi-diagram-3 me-2"></i>LISTAGEM DE DEPENDÊNCIAS</a></li>';

        if (isset($_SESSION['usuario_id'])) {
            $actions .= '
                <li><a class="dropdown-item" href="/users/edit?id=' .
                (int)$_SESSION['usuario_id'] . '">
                    <i class="bi bi-pencil-square me-2"></i>EDITAR MEU USUÁRIO</a></li>';
        }

        $actions .= '
                <li><a class="dropdown-item" href="/spreadsheets/import">
                    <i class="bi bi-upload me-2"></i>IMPORTAR PLANILHA</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/logout">
                    <i class="bi bi-box-arrow-right me-2"></i>SAIR</a></li>
            </ul>
        </div>';

        return $actions;
    }

    private function getCustomCss(): string
    {
        return '
        .table-hover tbody tr { cursor: pointer; }
        .input-group .btn-clear { border-top-left-radius: 0; border-bottom-left-radius: 0; }
        .table.table-center thead th, .table.table-center tbody td { text-align: center; vertical-align: middle; }
        ';
    }

    private function tratarErro(\Throwable $e, string $busca, int $pagina): void
    {
        @is_dir(__DIR__ . '/../../storage/logs') || @mkdir(__DIR__ . '/../../storage/logs', 0755, true);
        @file_put_contents(
            __DIR__ . '/../../storage/logs/comuns_controller.log',
            date('c') . " ERROR " . json_encode([
                'busca' => $busca,
                'pagina' => $pagina,
                'message' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 1200)
            ]) . PHP_EOL,
            FILE_APPEND
        );

        if ($this->query('ajax') === '1') {
            $this->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }

        $this->renderizarIndex([], $busca, $pagina, 10, 0, 0, 1);
    }

    private function renderizarHtmlLegado(array $dados): void
    {
        extract($dados);

        require __DIR__ . '/../../index.php';
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirecionar('/churches?erro=' . urlencode('ID inválido'));
            return;
        }

        try {
            $comum = $this->comumService->buscarPorId($id);

            if (!$comum) {
                $this->setMensagem('Comum não encontrado.', 'danger');
                $this->redirecionar('/churches');
                return;
            }

            // Preservar parâmetros de busca/paginação
            $busca = $this->query('busca', '');
            $pagina = $this->query('pagina', 1);

            ViewRenderer::render('churches/edit', [
                'pageTitle' => 'EDITAR COMUM',
                'backUrl' => '/churches?busca=' . urlencode($busca) . '&pagina=' . $pagina,
                'headerActions' => '',
                'customCssPath' => '/assets/css/comuns/edit.css',
                'comum' => $comum,
                'busca' => $busca,
                'pagina' => $pagina
            ]);
        } catch (\Throwable $e) {
            error_log('Erro ComumController::edit: ' . $e->getMessage());
            $this->setMensagem('Erro ao carregar comum: ' . $e->getMessage(), 'danger');
            $this->redirecionar('/churches');
        }
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/churches');
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->setMensagem('ID inválido.', 'danger');
            $this->redirecionar('/churches');
            return;
        }

        try {
            $dados = [
                'codigo' => trim($_POST['codigo'] ?? ''),
                'descricao' => trim($_POST['descricao'] ?? ''),
                'cnpj' => trim($_POST['cnpj'] ?? ''),
                'administracao' => trim($_POST['administracao'] ?? ''),
                'cidade' => trim($_POST['cidade'] ?? ''),
                'estado' => trim($_POST['estado'] ?? ''),
                'endereco' => trim($_POST['endereco'] ?? ''),
                'telefone' => trim($_POST['telefone'] ?? '')
            ];

            // Validações básicas
            if (empty($dados['codigo'])) {
                throw new \Exception('Código é obrigatório.');
            }
            if (empty($dados['descricao'])) {
                throw new \Exception('Descrição é obrigatória.');
            }

            $this->comumService->atualizar($id, $dados);

            // Preservar parâmetros de busca/paginação
            $busca = $this->post('busca', '');
            $pagina = $this->post('pagina', 1);

            $this->setMensagem('Comum atualizado com sucesso!', 'success');
            $this->redirecionar('/churches?busca=' . urlencode($busca) . '&pagina=' . $pagina);
        } catch (\Throwable $e) {
            error_log('Erro ComumController::update: ' . $e->getMessage());
            $this->setMensagem('Erro ao atualizar comum: ' . $e->getMessage(), 'danger');

            // Voltar para edição com dados preservados
            $busca = $this->post('busca', '');
            $pagina = $this->post('pagina', 1);
            $this->redirecionar('/churches/edit?id=' . $id . '&busca=' . urlencode($busca) . '&pagina=' . $pagina);
        }
    }
}

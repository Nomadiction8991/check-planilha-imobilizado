<?php

namespace App\Controllers;

use App\Repositories\ComumRepository;
use PDO;

/**
 * Controller de Comuns
 * Gerencia listagem, visualização e edição de comuns
 */
class ComumController extends BaseController
{
    private ComumRepository $comumRepo;

    public function __construct(PDO $conexao)
    {
        $this->comumRepo = new ComumRepository($conexao);
    }

    /**
     * Lista comuns com paginação e busca
     */
    public function index(): void
    {
        // Parâmetros de busca e paginação
        $busca = trim($this->query('busca', ''));
        $pagina = max(1, (int) $this->query('pagina', 1));
        $limite = 10;
        $offset = ($pagina - 1) * $limite;

        try {
            // Buscar comuns
            $comuns = $this->comumRepo->buscarPaginado($busca, $limite, $offset);

            // Contar totais
            $total = $this->comumRepo->contarComFiltro($busca);
            $totalGeral = $this->comumRepo->contar();
            $totalPaginas = $total > 0 ? (int) ceil($total / $limite) : 1;

            // Se for requisição AJAX, retornar JSON
            if ($this->query('ajax') === '1') {
                $this->retornarAjax($comuns, $total, $totalGeral, $pagina, $totalPaginas, $busca);
                return;
            }

            // Renderizar página HTML
            $this->renderizarIndex($comuns, $busca, $pagina, $limite, $total, $totalGeral, $totalPaginas);
        } catch (\Throwable $e) {
            $this->tratarErro($e, $busca, $pagina);
        }
    }

    /**
     * Renderiza a página principal
     */
    private function renderizarIndex(
        array $comuns,
        string $busca,
        int $pagina,
        int $limite,
        int $total,
        int $totalGeral,
        int $totalPaginas
    ): void {
        $buscaDisplay = mb_strtoupper($busca, 'UTF-8');

        // Build query string para preservar filtros
        $qsArr = [];
        if ($busca !== '') {
            $qsArr['busca'] = $busca;
        }
        if ($pagina > 1) {
            $qsArr['pagina'] = $pagina;
        }
        $qs = http_build_query($qsArr);

        // Preparar dados para a view
        $dados = [
            'pageTitle' => 'COMUNS',
            'backUrl' => null,
            'headerActions' => $this->gerarHeaderActions(),
            'customCss' => $this->getCustomCss(),
            'comuns' => $comuns,
            'busca' => $busca,
            'buscaDisplay' => $buscaDisplay,
            'pagina' => $pagina,
            'limite' => $limite,
            'total' => $total,
            'totalGeral' => $totalGeral,
            'totalPaginas' => $totalPaginas,
            'qs' => $qs
        ];

        // Renderizar view (criar arquivo separado depois)
        $this->renderizarHtmlLegado($dados);
    }

    /**
     * Retorna dados em formato JSON para requisições AJAX
     */
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

    /**
     * Gera HTML das linhas da tabela
     */
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
            $editHref = 'app/views/comuns/comum_editar.php?id=' . (int) $comum['id'] .
                ($qsEdit ? ('&' . $qsEdit) : '');
            $viewHref = 'app/views/planilhas/planilha_visualizar.php?comum_id=' . (int) $comum['id'];

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

    /**
     * Verifica se o cadastro do comum está completo
     */
    private function verificarCadastroCompleto(array $comum): bool
    {
        return trim((string) $comum['descricao']) !== ''
            && trim((string) $comum['cnpj']) !== ''
            && trim((string) $comum['administracao']) !== ''
            && trim((string) $comum['cidade']) !== '';
    }

    /**
     * Gera ações do header
     */
    private function gerarHeaderActions(): string
    {
        if (!isLoggedIn()) {
            return '';
        }

        $actions = '
        <div class="dropdown">
            <button class="btn-header-action" type="button" id="menuPrincipal" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-list fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuPrincipal">
                <li><a class="dropdown-item" href="app/views/usuarios/usuarios_listar.php">
                    <i class="bi bi-people me-2"></i>LISTAGEM DE USUÁRIOS</a></li>
                <li><a class="dropdown-item" href="app/views/dependencias/dependencias_listar.php">
                    <i class="bi bi-diagram-3 me-2"></i>LISTAGEM DE DEPENDÊNCIAS</a></li>';

        if (isset($_SESSION['usuario_id'])) {
            $actions .= '
                <li><a class="dropdown-item" href="app/views/usuarios/usuario_editar.php?id=' .
                (int)$_SESSION['usuario_id'] . '">
                    <i class="bi bi-pencil-square me-2"></i>EDITAR MEU USUÁRIO</a></li>';
        }

        $actions .= '
                <li><a class="dropdown-item" href="app/views/planilhas/planilha_importar.php">
                    <i class="bi bi-upload me-2"></i>IMPORTAR PLANILHA</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>SAIR</a></li>
            </ul>
        </div>';

        return $actions;
    }

    /**
     * CSS customizado
     */
    private function getCustomCss(): string
    {
        return '
        .table-hover tbody tr { cursor: pointer; }
        .input-group .btn-clear { border-top-left-radius: 0; border-bottom-left-radius: 0; }
        .table.table-center thead th, .table.table-center tbody td { text-align: center; vertical-align: middle; }
        ';
    }

    /**
     * Trata erros e loga
     */
    private function tratarErro(\Throwable $e, string $busca, int $pagina): void
    {
        // Log do erro
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

        // Se for AJAX, retornar erro JSON
        if ($this->query('ajax') === '1') {
            $this->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }

        // Senão, renderizar página vazia
        $this->renderizarIndex([], $busca, $pagina, 10, 0, 0, 1);
    }

    /**
     * TEMPORÁRIO: Renderiza HTML legado até migrar a view
     * TODO: Criar src/Views/comuns/index.php
     */
    private function renderizarHtmlLegado(array $dados): void
    {
        extract($dados);

        // Incluir o arquivo original temporariamente
        // Depois vamos criar uma view limpa em src/Views/comuns/index.php
        require __DIR__ . '/../../index.php';
    }
}

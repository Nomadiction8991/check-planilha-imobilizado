<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Core\SessionManager;
use App\Repositories\ComumRepository;
use App\Repositories\DependenciaRepository;
use App\Repositories\ProdutoRepository;
use App\Services\ImportacaoService;
use App\Services\CsvParserService;
use PDO;

class PlanilhaController extends BaseController
{
    private ImportacaoService $importacaoService;
    private CsvParserService $csvParserService;
    private ComumRepository $comumRepository;
    private ProdutoRepository $produtoRepository;
    private DependenciaRepository $dependenciaRepository;

    public function __construct(?PDO $conexao = null)
    {
        $conexao = $conexao ?? ConnectionManager::getConnection();

        $this->importacaoService = new ImportacaoService($conexao);
        $this->csvParserService = new CsvParserService($conexao);
        $this->comumRepository = new ComumRepository($conexao);
        $this->produtoRepository = new ProdutoRepository($conexao);
        $this->dependenciaRepository = new DependenciaRepository($conexao);
    }

    public function importar(): void
    {
        if (!SessionManager::isAuthenticated()) {
            $this->redirecionar('/login');
            return;
        }
        $this->renderizar('spreadsheets/import');
    }

    /**
     * PASSO 1: Upload do CSV → salva arquivo → analisa → redireciona para preview.
     */
    public function processarImportacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/spreadsheets/import');
            return;
        }

        try {
            // Garante limites adequados para processar CSV grande
            set_time_limit(120);
            ini_set('memory_limit', '128M');

            SessionManager::start();
            $comumId = SessionManager::getComumId();
            $usuarioId = SessionManager::getUserId();

            if (!$usuarioId) {
                $this->redirecionar('/login');
                return;
            }

            if (!$comumId) {
                throw new \Exception('Selecione um Comum antes de importar');
            }

            // Valida upload — aceita 'arquivo_csv' e 'arquivo' (compatibilidade)
            $arquivo = null;
            if (isset($_FILES['arquivo_csv']) && $_FILES['arquivo_csv']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['arquivo_csv'];
            } elseif (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['arquivo'];
            }

            if (!$arquivo) {
                throw new \Exception('Erro ao fazer upload do arquivo');
            }

            // Valida tipo
            $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            if (!in_array($extensao, ['csv', 'txt'])) {
                throw new \Exception('Apenas arquivos CSV são permitidos');
            }

            // Move arquivo para storage/importacao
            $dirImportacao = __DIR__ . '/../../storage/importacao';
            if (!is_dir($dirImportacao)) {
                mkdir($dirImportacao, 0777, true);
            }

            $nomeArquivo = 'importacao_' . $comumId . '_' . time() . '.' . $extensao;
            $caminhoDestino = $dirImportacao . '/' . $nomeArquivo;

            if (!move_uploaded_file($arquivo['tmp_name'], $caminhoDestino)) {
                throw new \Exception('Erro ao salvar arquivo');
            }

            // Registra importação no banco
            $importacaoId = $this->importacaoService->iniciarImportacao(
                $usuarioId,
                $comumId,
                $arquivo['name'],
                $caminhoDestino
            );

            // Analisa CSV vs banco de dados
            $analise = $this->csvParserService->analisar($caminhoDestino, $comumId);

            // Salva análise em JSON para a tela de preview
            $this->csvParserService->salvarAnalise($importacaoId, $analise);

            // Redireciona para tela de preview (conferência)
            $this->redirecionar('/spreadsheets/preview?id=' . $importacaoId);
        } catch (\Exception $e) {
            error_log('Erro ao processar importação: ' . $e->getMessage());
            $this->setMensagem('Erro: ' . $e->getMessage(), 'danger');
            $this->redirecionar('/spreadsheets/import?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * PASSO 2: Tela de preview — mostra análise com diff e ações por registro.
     */
    public function preview(): void
    {
        $importacaoId = (int) ($_GET['id'] ?? 0);

        if ($importacaoId <= 0) {
            $this->redirecionar('/spreadsheets/import?erro=ID inválido');
            return;
        }

        // Carrega dados da importação
        $importacao = $this->importacaoService->buscarProgresso($importacaoId);
        if (!$importacao) {
            $this->redirecionar('/spreadsheets/import?erro=Importação não encontrada');
            return;
        }

        // Carrega análise salva
        $analise = $this->csvParserService->carregarAnalise($importacaoId);
        if (!$analise) {
            $this->redirecionar('/spreadsheets/import?erro=Análise não encontrada');
            return;
        }

        // Paginação: 50 registros por página
        $todosRegistros = $analise['registros'];
        $totalRegistros = count($todosRegistros);
        $itensPorPagina = 50;
        $paginaAtual = max(1, (int) ($_GET['pagina'] ?? 1));
        $totalPaginas = max(1, (int) ceil($totalRegistros / $itensPorPagina));
        $paginaAtual = min($paginaAtual, $totalPaginas);
        $offset = ($paginaAtual - 1) * $itensPorPagina;

        // Filtro por status
        $filtroStatus = $_GET['filtro'] ?? 'todos';
        if ($filtroStatus !== 'todos') {
            $todosRegistros = array_values(array_filter($todosRegistros, function ($reg) use ($filtroStatus) {
                return ($reg['status'] ?? '') === $filtroStatus;
            }));
            $totalRegistros = count($todosRegistros);
            $totalPaginas = max(1, (int) ceil($totalRegistros / $itensPorPagina));
            $paginaAtual = min($paginaAtual, $totalPaginas);
            $offset = ($paginaAtual - 1) * $itensPorPagina;
        }

        $registrosPagina = array_slice($todosRegistros, $offset, $itensPorPagina);

        // Carrega ações salvas anteriormente na sessão
        $acoesSalvas = $_SESSION['preview_acoes_' . $importacaoId] ?? [];

        $this->renderizar('spreadsheets/import-preview', [
            'importacao_id' => $importacaoId,
            'importacao' => $importacao,
            'resumo' => $analise['resumo'],
            'registros' => $registrosPagina,
            'pagina' => $paginaAtual,
            'total_paginas' => $totalPaginas,
            'total_registros' => $totalRegistros,
            'itens_por_pagina' => $itensPorPagina,
            'filtro_status' => $filtroStatus,
            'acoes_salvas' => $acoesSalvas,
        ]);
    }

    /**
     * AJAX: Salva ações selecionadas da página atual na sessão.
     */
    public function salvarAcoesPreview(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['erro' => 'Método não permitido']);
            exit;
        }

        $dados = json_decode(file_get_contents('php://input'), true);
        $importacaoId = (int) ($dados['importacao_id'] ?? 0);
        $acoes = $dados['acoes'] ?? [];

        if ($importacaoId <= 0) {
            echo json_encode(['erro' => 'ID inválido']);
            exit;
        }

        // Mescla com ações já salvas na sessão
        if (!isset($_SESSION['preview_acoes_' . $importacaoId])) {
            $_SESSION['preview_acoes_' . $importacaoId] = [];
        }

        foreach ($acoes as $linhaCsv => $acao) {
            $_SESSION['preview_acoes_' . $importacaoId][$linhaCsv] = $acao;
        }

        echo json_encode(['sucesso' => true, 'total_salvas' => count($_SESSION['preview_acoes_' . $importacaoId])]);
        exit;
    }

    /**
     * AJAX: Aplica ação em massa a TODOS os registros (todas as páginas).
     */
    public function acaoMassaPreview(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['erro' => 'Método não permitido']);
            exit;
        }

        $dados = json_decode(file_get_contents('php://input'), true);
        $importacaoId = (int) ($dados['importacao_id'] ?? 0);
        $acao = $dados['acao'] ?? '';

        if ($importacaoId <= 0 || !in_array($acao, ['importar', 'pular'])) {
            echo json_encode(['erro' => 'Parâmetros inválidos']);
            exit;
        }

        // Carrega análise para obter todas as linhas
        $analise = $this->csvParserService->carregarAnalise($importacaoId);
        if (!$analise) {
            echo json_encode(['erro' => 'Análise não encontrada']);
            exit;
        }

        // Aplica a ação a todos os registros (exceto erros)
        $acoes = [];
        foreach ($analise['registros'] as $reg) {
            $linhaCsv = (string) ($reg['linha_csv'] ?? '');
            $status = $reg['status'] ?? 'erro';
            if ($linhaCsv !== '' && $status !== 'erro') {
                $acoes[$linhaCsv] = $acao;
            }
        }

        $_SESSION['preview_acoes_' . $importacaoId] = $acoes;

        echo json_encode([
            'sucesso' => true,
            'acao' => $acao,
            'total_aplicadas' => count($acoes)
        ]);
        exit;
    }

    /**
     * PASSO 3: Confirma importação — recebe ações do usuário e processa.
     */
    public function confirmarImportacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/spreadsheets/import');
            return;
        }

        $importacaoId = (int) ($_POST['importacao_id'] ?? 0);

        if ($importacaoId <= 0) {
            $this->redirecionar('/spreadsheets/import?erro=ID inválido');
            return;
        }

        // Mescla ações salvas via AJAX (páginas anteriores) com ações do formulário (página atual)
        $acoesSalvas = $_SESSION['preview_acoes_' . $importacaoId] ?? [];
        $acoesFormulario = $_POST['acao'] ?? [];
        $acoes = array_merge($acoesSalvas, $acoesFormulario);

        // Para registros sem ação definida, carrega a ação sugerida da análise
        $analise = $this->csvParserService->carregarAnalise($importacaoId);
        if ($analise) {
            foreach ($analise['registros'] as $reg) {
                $linhaCsv = (string) ($reg['linha_csv'] ?? '');
                if ($linhaCsv !== '' && !isset($acoes[$linhaCsv])) {
                    $acoes[$linhaCsv] = $reg['acao_sugerida'] ?? 'pular';
                }
            }
        }

        // Salva ações completas em session para o processamento assíncrono
        $_SESSION['importacao_acoes_' . $importacaoId] = $acoes;

        // Limpa ações temporárias do preview
        unset($_SESSION['preview_acoes_' . $importacaoId]);

        // Redireciona para a tela de progresso
        $this->redirecionar('/spreadsheets/progress?id=' . $importacaoId);
    }

    public function visualizar(): void
    {
        // Se veio ?comum_id= pela URL (clique na listagem), atualiza a sessão
        $comumIdUrl = (int) ($this->query('comum_id', 0));
        if ($comumIdUrl > 0) {
            SessionManager::set('comum_id', $comumIdUrl);
        }

        $comumId = SessionManager::getComumId();

        if (!$comumId || $comumId <= 0) {
            $this->redirecionar('/churches?erro=Nenhuma comum disponível');
            return;
        }

        // Buscar dados da comum
        $planilha = $this->comumRepository->buscarPorId($comumId);

        if (!$planilha) {
            $this->redirecionar('/churches?erro=Comum não encontrada');
            return;
        }

        $planilha['comum_descricao'] = $planilha['descricao'] ?? 'Comum';

        // Filtros
        $paginaAtual = max(1, (int) ($this->query('pagina', 1)));
        $itensPorPagina = 50;

        $filtros = [
            'nome'        => $this->query('nome', ''),
            'dependencia' => $this->query('dependencia', ''),
            'status'      => $this->query('status', ''),
            'codigo'      => $this->query('filtro_codigo', ''),
        ];

        // Buscar produtos via repository
        $resultado = $this->produtoRepository->buscarParaPlanilha($comumId, $paginaAtual, $itensPorPagina, $filtros);

        // Buscar dependências para o filtro
        $dependencias = $this->dependenciaRepository->buscarTodos();

        $this->renderizar('spreadsheets/view', [
            'comum_id'            => $comumId,
            'planilha'            => $planilha,
            'produtos'            => $resultado['dados'],
            'total_registros'     => $resultado['total'],
            'pagina'              => $paginaAtual,
            'total_paginas'       => $resultado['totalPaginas'],
            'dependencia_options' => $dependencias,
            'filtro_nome'         => $filtros['nome'],
            'filtro_dependencia'  => $filtros['dependencia'],
            'filtro_status'       => $filtros['status'],
            'filtro_codigo'       => $filtros['codigo'],
        ]);
    }

    public function progresso(): void
    {
        $importacaoId = (int) ($_GET['id'] ?? 0);

        if ($importacaoId <= 0) {
            $this->redirecionar('/spreadsheets/import?erro=' . urlencode('ID de importação inválido'));
            return;
        }

        $this->renderizar('spreadsheets/import-progress', [
            'importacao_id' => $importacaoId
        ]);
    }

    public function apiProgresso(): void
    {
        header('Content-Type: application/json');

        $importacaoId = (int) ($_GET['id'] ?? 0);

        if ($importacaoId <= 0) {
            echo json_encode(['erro' => 'ID inválido']);
            exit;
        }

        try {
            $progresso = $this->importacaoService->buscarProgresso($importacaoId);

            if (!$progresso) {
                echo json_encode(['erro' => 'Importação não encontrada']);
                exit;
            }

            echo json_encode([
                'id' => $progresso['id'],
                'status' => $progresso['status'],
                'total_linhas' => $progresso['total_linhas'],
                'linhas_processadas' => $progresso['linhas_processadas'],
                'linhas_sucesso' => $progresso['linhas_sucesso'],
                'linhas_erro' => $progresso['linhas_erro'],
                'porcentagem' => $progresso['porcentagem'],
                'arquivo_nome' => $progresso['arquivo_nome'],
                'mensagem_erro' => $progresso['mensagem_erro'],
                'iniciada_em' => $progresso['iniciada_em'],
                'concluida_em' => $progresso['concluida_em']
            ]);
        } catch (\Exception $e) {
            echo json_encode(['erro' => $e->getMessage()]);
        }

        exit;
    }

    public function processarArquivo(): void
    {
        header('Content-Type: application/json');

        $importacaoId = (int) ($_POST['id'] ?? 0);

        if ($importacaoId <= 0) {
            echo json_encode(['erro' => 'ID inválido']);
            exit;
        }

        try {
            set_time_limit(0);
            ignore_user_abort(true);

            // Verifica se há ações do preview na sessão
            $acoes = $_SESSION['importacao_acoes_' . $importacaoId] ?? null;

            if ($acoes !== null) {
                // Fluxo NOVO: processar com ações selecionadas pelo usuário
                $analise = $this->csvParserService->carregarAnalise($importacaoId);

                if (!$analise) {
                    echo json_encode(['erro' => 'Análise não encontrada. Refaça o upload.']);
                    exit;
                }

                $resultado = $this->importacaoService->processarComAcoes($importacaoId, $acoes, $analise);

                // Limpa dados temporários
                unset($_SESSION['importacao_acoes_' . $importacaoId]);
                $this->csvParserService->limparAnalise($importacaoId);

                echo json_encode([
                    'sucesso' => true,
                    'linhas_sucesso' => $resultado['sucesso'],
                    'linhas_erro' => $resultado['erro'],
                    'linhas_puladas' => $resultado['pulados'] ?? 0,
                    'linhas_excluidas' => $resultado['excluidos'] ?? 0,
                    'erros' => array_slice($resultado['erros'], 0, 10)
                ]);
            } else {
                // Fluxo LEGADO: processar tudo sem preview
                $resultado = $this->importacaoService->processar($importacaoId);

                echo json_encode([
                    'sucesso' => true,
                    'linhas_sucesso' => $resultado['sucesso'],
                    'linhas_erro' => $resultado['erro'],
                    'erros' => array_slice($resultado['erros'], 0, 10)
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode(['erro' => $e->getMessage()]);
        }

        exit;
    }
}

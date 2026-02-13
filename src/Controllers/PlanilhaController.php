<?php

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Core\SessionManager;
use App\Services\ImportacaoService;
use App\Services\CsvParserService;
use PDO;

class PlanilhaController extends BaseController
{
    private PDO $conexao;
    private ImportacaoService $importacaoService;
    private CsvParserService $csvParserService;

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
        $this->importacaoService = new ImportacaoService($this->conexao);
        $this->csvParserService = new CsvParserService($this->conexao);
    }

    public function importar(): void
    {
        if (!SessionManager::isAuthenticated()) {
            $this->redirecionar('/login');
            return;
        }
        $this->renderizar('planilhas/planilha_importar');
    }

    /**
     * PASSO 1: Upload do CSV → salva arquivo → analisa → redireciona para preview.
     */
    public function processarImportacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/planilhas/importar');
            return;
        }

        try {
            // Garante limites adequados para processar CSV grande
            set_time_limit(120);
            ini_set('memory_limit', '128M');

            SessionManager::ensureComumId();
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
            $this->redirecionar('/planilhas/preview?id=' . $importacaoId);
        } catch (\Exception $e) {
            error_log('Erro ao processar importação: ' . $e->getMessage());
            $this->setMensagem('Erro: ' . $e->getMessage(), 'danger');
            $this->redirecionar('/planilhas/importar?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * PASSO 2: Tela de preview — mostra análise com diff e ações por registro.
     */
    public function preview(): void
    {
        $importacaoId = (int) ($_GET['id'] ?? 0);

        if ($importacaoId <= 0) {
            $this->redirecionar('/planilhas/importar?erro=ID inválido');
            return;
        }

        // Carrega dados da importação
        $importacao = $this->importacaoService->buscarProgresso($importacaoId);
        if (!$importacao) {
            $this->redirecionar('/planilhas/importar?erro=Importação não encontrada');
            return;
        }

        // Carrega análise salva
        $analise = $this->csvParserService->carregarAnalise($importacaoId);
        if (!$analise) {
            $this->redirecionar('/planilhas/importar?erro=Análise não encontrada');
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

        $this->renderizar('planilhas/importacao_preview', [
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
            $this->redirecionar('/planilhas/importar');
            return;
        }

        $importacaoId = (int) ($_POST['importacao_id'] ?? 0);

        if ($importacaoId <= 0) {
            $this->redirecionar('/planilhas/importar?erro=ID inválido');
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
        $this->redirecionar('/planilhas/progresso?id=' . $importacaoId);
    }

    public function visualizar(): void
    {
        // Se veio ?comum_id= pela URL (clique na listagem), atualiza a sessão
        $comumIdUrl = (int) ($_GET['comum_id'] ?? 0);
        if ($comumIdUrl > 0) {
            \App\Core\SessionManager::set('comum_id', $comumIdUrl);
        }

        // Garante que comum_id está definida na sessão
        $comumId = \App\Core\SessionManager::ensureComumId();

        if (!$comumId || $comumId <= 0) {
            // Se não há comum disponível, redireciona para comuns
            $this->redirecionar('/comuns?erro=Nenhuma comum disponível');
            return;
        }

        // Buscar dados da comum
        $stmt = $this->conexao->prepare("SELECT * FROM comums WHERE id = :id");
        $stmt->bindValue(':id', $comumId, PDO::PARAM_INT);
        $stmt->execute();
        $planilha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$planilha) {
            $this->redirecionar('/comuns?erro=Comum não encontrada');
            return;
        }

        // Formatar dados da planilha/comum
        $planilha['comum_descricao'] = $planilha['descricao'] ?? 'Comum';

        // Buscar produtos da comum com paginação
        $paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $itensPorPagina = 50;
        $offset = ($paginaAtual - 1) * $itensPorPagina;

        // Filtros
        $filtroNome = $_GET['nome'] ?? '';
        $filtroDependencia = $_GET['dependencia'] ?? '';
        $filtroStatus = $_GET['status'] ?? '';
        $filtroCodigo = $_GET['filtro_codigo'] ?? '';

        // Montar query com filtros
        $where = ['p.comum_id = :comum_id'];
        $params = [':comum_id' => $comumId];

        if ($filtroNome !== '') {
            $where[] = '(p.descricao_completa LIKE :nome OR p.bem LIKE :nome)';
            $params[':nome'] = '%' . $filtroNome . '%';
        }

        if ($filtroDependencia !== '') {
            $where[] = 'p.dependencia_id = :dependencia';
            $params[':dependencia'] = (int)$filtroDependencia;
        }

        if ($filtroCodigo !== '') {
            $where[] = 'p.codigo LIKE :codigo';
            $params[':codigo'] = '%' . $filtroCodigo . '%';
        }

        if ($filtroStatus === 'checado') {
            $where[] = 'p.checado = 1';
        } elseif ($filtroStatus === 'observacao') {
            $where[] = 'p.observacao != ""';
        } elseif ($filtroStatus === 'etiqueta') {
            $where[] = 'p.imprimir_etiqueta = 1';
        } elseif ($filtroStatus === 'pendente') {
            $where[] = 'p.checado = 0';
        } elseif ($filtroStatus === 'editado') {
            $where[] = 'p.editado = 1';
        }

        $whereClause = implode(' AND ', $where);

        // Contar total de produtos
        $stmtCount = $this->conexao->prepare("SELECT COUNT(*) FROM produtos p WHERE $whereClause");
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $totalProdutos = (int)$stmtCount->fetchColumn();
        $totalPaginas = ceil($totalProdutos / $itensPorPagina);

        // Buscar produtos com JOINs para pegar descrições
        $sql = "SELECT p.*, 
                       tb.codigo as tipo_codigo, 
                       tb.descricao as tipo_desc,
                       d.descricao as dependencia_desc,
                       COALESCE(ed.descricao, '') as editado_dependencia_desc
                FROM produtos p
                LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                LEFT JOIN dependencias d ON p.dependencia_id = d.id
                LEFT JOIN dependencias ed ON p.editado_dependencia_id = ed.id
                WHERE $whereClause 
                ORDER BY p.codigo ASC 
                LIMIT :limite OFFSET :offset";
        $stmtProdutos = $this->conexao->prepare($sql);
        foreach ($params as $key => $value) {
            $stmtProdutos->bindValue($key, $value);
        }
        $stmtProdutos->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
        $stmtProdutos->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtProdutos->execute();
        $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

        // Buscar dependências para o filtro
        $stmtDeps = $this->conexao->query("SELECT * FROM dependencias ORDER BY descricao ASC");
        $dependencias = $stmtDeps->fetchAll(PDO::FETCH_ASSOC);

        // Passar dados para a view
        $this->renderizar('planilhas/planilha_visualizar', [
            'comum_id' => $comumId,
            'planilha' => $planilha,
            'produtos' => $produtos,
            'total_registros' => $totalProdutos,
            'pagina' => $paginaAtual,
            'total_paginas' => $totalPaginas,
            'dependencia_options' => $dependencias,
            'filtro_nome' => $filtroNome,
            'filtro_dependencia' => $filtroDependencia,
            'filtro_status' => $filtroStatus,
            'filtro_codigo' => $filtroCodigo,
        ]);
    }

    public function progresso(): void
    {
        $importacaoId = (int) ($_GET['id'] ?? 0);

        if ($importacaoId <= 0) {
            $this->redirecionar('/planilhas/importar?erro=' . urlencode('ID de importação inválido'));
            return;
        }

        $this->renderizar('planilhas/importacao_progresso', [
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

<?php

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Core\SessionManager;
use App\Services\ImportacaoService;
use PDO;

class PlanilhaController extends BaseController
{
    private PDO $conexao;
    private ImportacaoService $importacaoService;

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
        $this->importacaoService = new ImportacaoService($this->conexao);
    }

    public function importar(): void
    {
        $this->renderizar('planilhas/planilha_importar');
    }

    public function processarImportacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/planilhas/importar');
            return;
        }

        try {
            SessionManager::ensureComumId();
            $comumId = SessionManager::getComumId();
            $usuarioId = SessionManager::getUserId();

            if (!$comumId || !$usuarioId) {
                throw new \Exception('Sessão inválida');
            }

            // Valida upload
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('Erro ao fazer upload do arquivo');
            }

            $arquivo = $_FILES['arquivo'];

            // Valida tipo
            $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            if (!in_array($extensao, ['csv', 'txt'])) {
                throw new \Exception('Apenas arquivos CSV são permitidos');
            }

            // Move arquivo para storage/importacao
            $nomeArquivo = 'importacao_' . $comumId . '_' . time() . '.' . $extensao;
            $caminhoDestino = __DIR__ . '/../../storage/importacao/' . $nomeArquivo;

            if (!move_uploaded_file($arquivo['tmp_name'], $caminhoDestino)) {
                throw new \Exception('Erro ao salvar arquivo');
            }

            // Registra importação
            $importacaoId = $this->importacaoService->iniciarImportacao(
                $usuarioId,
                $comumId,
                $arquivo['name'],
                $caminhoDestino
            );

            // Redireciona para tela de progresso
            $this->redirecionar('/planilhas/progresso?id=' . $importacaoId);
        } catch (\Exception $e) {
            error_log('Erro ao processar importação: ' . $e->getMessage());
            $this->setMensagem('Erro: ' . $e->getMessage(), 'danger');
            $this->redirecionar('/planilhas/importar');
        }
    }

    public function visualizar(): void
    {
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
        $itensPorPagina = 20;
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
            // Processa em background (assíncrono)
            set_time_limit(0);
            ignore_user_abort(true);

            $resultado = $this->importacaoService->processar($importacaoId);

            echo json_encode([
                'sucesso' => true,
                'linhas_sucesso' => $resultado['sucesso'],
                'linhas_erro' => $resultado['erro'],
                'erros' => array_slice($resultado['erros'], 0, 10) // Primeiros 10 erros
            ]);
        } catch (\Exception $e) {
            echo json_encode(['erro' => $e->getMessage()]);
        }

        exit;
    }
}

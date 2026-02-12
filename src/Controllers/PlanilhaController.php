<?php

namespace App\Controllers;

use App\Core\ConnectionManager;
use PDO;

class PlanilhaController extends BaseController
{
    private PDO $conexao;

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
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

        // TODO: CRÍTICO - Implementar importação de planilhas
        http_response_code(501);
        die('Funcionalidade de importação em implementação. Controller pendente de migração.');
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
        $where = ['comum_id = :comum_id'];
        $params = [':comum_id' => $comumId];

        if ($filtroNome !== '') {
            $where[] = '(descricao_completa LIKE :nome OR bem LIKE :nome)';
            $params[':nome'] = '%' . $filtroNome . '%';
        }

        if ($filtroDependencia !== '') {
            $where[] = 'dependencia_id = :dependencia';
            $params[':dependencia'] = (int)$filtroDependencia;
        }

        if ($filtroCodigo !== '') {
            $where[] = 'codigo LIKE :codigo';
            $params[':codigo'] = '%' . $filtroCodigo . '%';
        }

        if ($filtroStatus === 'checado') {
            $where[] = 'checado = 1';
        } elseif ($filtroStatus === 'observacao') {
            $where[] = 'observacao != ""';
        } elseif ($filtroStatus === 'etiqueta') {
            $where[] = 'imprimir_etiqueta = 1';
        } elseif ($filtroStatus === 'pendente') {
            $where[] = 'checado = 0';
        } elseif ($filtroStatus === 'editado') {
            $where[] = 'editado = 1';
        }

        $whereClause = implode(' AND ', $where);

        // Contar total de produtos
        $stmtCount = $this->conexao->prepare("SELECT COUNT(*) FROM produtos WHERE $whereClause");
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $totalProdutos = (int)$stmtCount->fetchColumn();
        $totalPaginas = ceil($totalProdutos / $itensPorPagina);

        // Buscar produtos
        $sql = "SELECT * FROM produtos WHERE $whereClause ORDER BY codigo ASC LIMIT :limite OFFSET :offset";
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
            'total_produtos' => $totalProdutos,
            'pagina' => $paginaAtual,
            'total_paginas' => $totalPaginas,
            'dependencias' => $dependencias,
            'filtro_nome' => $filtroNome,
            'filtro_dependencia' => $filtroDependencia,
            'filtro_status' => $filtroStatus,
            'filtro_codigo' => $filtroCodigo,
        ]);
    }

    public function progresso(): void
    {
        $this->renderizar('planilhas/importacao_progresso');
    }
}

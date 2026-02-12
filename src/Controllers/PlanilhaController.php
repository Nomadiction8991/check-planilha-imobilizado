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
        $this->renderizar('planilhas/importacao_progresso');
    }
}

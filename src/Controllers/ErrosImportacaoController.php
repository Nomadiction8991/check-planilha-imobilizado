<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Core\SessionManager;
use PDO;

class ErrosImportacaoController extends BaseController
{
    private PDO $conexao;

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
    }

    // ─────────────────────────────────────────────────────────────────────── //
    //  LISTAGEM                                                                //
    // ─────────────────────────────────────────────────────────────────────── //

    /**
     * Lista erros de importação.
     *
     * Modos:
     *  - ?comum_id=X  → todos os erros pendentes da comum X (modo principal)
     *  - ?importacao_id=X → compatibilidade antiga
     *  - sem parâmetro → todos os erros pendentes de todas as comuns (visão geral)
     */
    public function listar(): void
    {
        if (!SessionManager::isAuthenticated()) {
            $this->redirecionar('/login');
            return;
        }

        $comumId      = (int) ($_GET['comum_id']      ?? 0);
        $importacaoId = (int) ($_GET['importacao_id'] ?? 0);
        $itensPorPagina = 30;
        $paginaAtual    = max(1, (int) ($_GET['pagina'] ?? 1));

        // ── Monta WHERE base ────────────────────────────────────────────────
        if ($importacaoId > 0) {
            // Modo legado: por importação específica
            $whereCount  = 'WHERE ie.importacao_id = :filtro_id';
            $whereSelect = $whereCount;
            $paramFiltro = [':filtro_id' => $importacaoId];
        } elseif ($comumId > 0) {
            // Modo principal: todos os erros não resolvidos da comum
            $whereCount  = 'JOIN importacoes imp ON ie.importacao_id = imp.id
                             WHERE imp.comum_id = :filtro_id AND ie.resolvido = 0';
            $whereSelect = $whereCount;
            $paramFiltro = [':filtro_id' => $comumId];
        } else {
            // Visão geral: todos pendentes
            $whereCount  = 'WHERE ie.resolvido = 0';
            $whereSelect = $whereCount;
            $paramFiltro = [];
        }

        // ── Conta total ─────────────────────────────────────────────────────
        $stmtTotal = $this->conexao->prepare(
            "SELECT COUNT(*) FROM import_erros ie {$whereCount}"
        );
        $stmtTotal->execute($paramFiltro);
        $totalRegistros = (int) $stmtTotal->fetchColumn();

        $totalPaginas = max(1, (int) ceil($totalRegistros / $itensPorPagina));
        $paginaAtual  = min($paginaAtual, $totalPaginas);
        $offset       = ($paginaAtual - 1) * $itensPorPagina;

        // ── Busca erros paginados ────────────────────────────────────────────
        $stmt = $this->conexao->prepare(
            "SELECT ie.id, ie.importacao_id, ie.linha_csv, ie.codigo,
                    ie.localidade, ie.codigo_comum, ie.descricao_csv,
                    ie.bem, ie.complemento, ie.dependencia,
                    ie.mensagem_erro, ie.resolvido, ie.created_at
               FROM import_erros ie
               {$whereSelect}
              ORDER BY ie.id ASC
              LIMIT :limite OFFSET :offset"
        );

        foreach ($paramFiltro as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,          PDO::PARAM_INT);
        $stmt->execute();
        $erros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Dados da importação (modo legado) ────────────────────────────────
        $importacao = [];
        if ($importacaoId > 0) {
            $stmtImp = $this->conexao->prepare(
                'SELECT arquivo_nome, iniciada_em, status FROM importacoes WHERE id = :id'
            );
            $stmtImp->execute([':id' => $importacaoId]);
            $importacao = $stmtImp->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        // ── Dados da comum (modo comum_id) ───────────────────────────────────
        $comum = [];
        if ($comumId > 0) {
            $stmtComum = $this->conexao->prepare(
                "SELECT id, descricao FROM comums WHERE id = :id"
            );
            $stmtComum->execute([':id' => $comumId]);
            $comum = $stmtComum->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        // ── Conta pendentes e resolvidos (para o resumo) ─────────────────────
        $resumo = $this->buscarResumo($comumId, $importacaoId);

        $this->renderizar('spreadsheets/import-errors', [
            'modo'            => $comumId > 0 ? 'comum' : ($importacaoId > 0 ? 'importacao' : 'geral'),
            'comum_id'        => $comumId,
            'importacao_id'   => $importacaoId,
            'importacao'      => $importacao,
            'comum'           => $comum,
            'erros'           => $erros,
            'pagina'          => $paginaAtual,
            'total_paginas'   => $totalPaginas,
            'total_registros' => $totalRegistros,
            'resumo'          => $resumo,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────── //
    //  DOWNLOAD CSV PARA REIMPORTAR                                           //
    // ─────────────────────────────────────────────────────────────────────── //

    /**
     * Gera um arquivo CSV pronto para ser reimportado diretamente no sistema.
     *
     * O formato segue o mesmo layout do CSV gerado pelo imobilizado CCB:
     *  - Col A  (0)  → codigo
     *  - Col D  (3)  → descricao_csv  (nome original, ex: "14 - MESA COLETIVO")
     *  - Col K  (10) → localidade
     *  - Col P  (15) → dependencia
     *
     * Uma linha de cabeçalho com "Codigo" em col A permite que o importador
     * auto-detecte o início dos dados mesmo sem metadados de capa.
     */
    public function downloadCsv(): void
    {
        if (!SessionManager::isAuthenticated()) {
            $this->redirecionar('/login');
            return;
        }

        $comumId      = (int) ($_GET['comum_id']      ?? 0);
        $importacaoId = (int) ($_GET['importacao_id'] ?? 0);

        // ── Monta WHERE ──────────────────────────────────────────────────────
        if ($importacaoId > 0) {
            $where       = 'WHERE ie.importacao_id = :filtro_id AND ie.resolvido = 0';
            $paramFiltro = [':filtro_id' => $importacaoId];
            $sufixoNome  = 'imp_' . $importacaoId;
        } elseif ($comumId > 0) {
            $where       = 'JOIN importacoes imp ON ie.importacao_id = imp.id
                             WHERE imp.comum_id = :filtro_id AND ie.resolvido = 0';
            $paramFiltro = [':filtro_id' => $comumId];
            $sufixoNome  = 'comum_' . $comumId;
        } else {
            http_response_code(400);
            echo 'Informe comum_id ou importacao_id';
            return;
        }

        $stmt = $this->conexao->prepare(
            "SELECT ie.codigo, ie.descricao_csv, ie.localidade,
                    ie.dependencia, ie.bem, ie.complemento
               FROM import_erros ie
               {$where}
              ORDER BY ie.id ASC"
        );
        $stmt->execute($paramFiltro);
        $erros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($erros)) {
            $this->redirecionar(
                '/spreadsheets/import-errors?'
                . ($comumId > 0 ? 'comum_id=' . $comumId : 'importacao_id=' . $importacaoId)
                . '&aviso=Nenhum+erro+pendente+para+baixar'
            );
            return;
        }

        $nomeArquivo = 'correcao_erros_' . $sufixoNome . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');

        // BOM para Excel reconhecer UTF-8
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // ── Cabeçalho no formato esperado pelo parser ──────────────────────
        // O auto-detect procura "Codigo" (sem acento) em col A.
        // Colunas: A=0, B=1, C=2, D=3 (nome), ..., K=10 (localidade), ..., P=15 (dependencia)
        $cabecalho = array_fill(0, 16, '');
        $cabecalho[0]  = 'Codigo';
        $cabecalho[3]  = 'Nome';
        $cabecalho[10] = 'Localidade';
        $cabecalho[15] = 'Dependencia';
        fputcsv($out, $cabecalho, ';');

        // ── Linhas de dados ────────────────────────────────────────────────
        foreach ($erros as $erro) {
            $linha = array_fill(0, 16, '');

            $linha[0]  = $erro['codigo'] ?? '';

            // descricao_csv = nome_original (ex: "14 - MESA COLETIVO")
            // Se não houver (erros antigos), reconstrói de bem + complemento
            $nomeOriginal = trim($erro['descricao_csv'] ?? '');
            if ($nomeOriginal === '') {
                $nomeOriginal = trim(($erro['bem'] ?? '') . ' ' . ($erro['complemento'] ?? ''));
            }
            $linha[3]  = $nomeOriginal;
            $linha[10] = $erro['localidade'] ?? '';
            $linha[15] = $erro['dependencia'] ?? '';

            fputcsv($out, $linha, ';');
        }

        fclose($out);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────── //
    //  AJAX: MARCAR RESOLVIDO                                                 //
    // ─────────────────────────────────────────────────────────────────────── //

    /**
     * AJAX: Marca/desmarca um erro como resolvido.
     */
    public function marcarResolvido(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!SessionManager::isAuthenticated()) {
            echo json_encode(['erro' => 'Não autenticado']);
            exit;
        }

        $dados     = json_decode(file_get_contents('php://input'), true) ?? [];
        $erroId    = (int) ($dados['erro_id']   ?? 0);
        $resolvido = (bool) ($dados['resolvido'] ?? true);

        if ($erroId <= 0) {
            echo json_encode(['erro' => 'ID inválido']);
            exit;
        }

        $stmt = $this->conexao->prepare(
            'UPDATE import_erros SET resolvido = :resolvido WHERE id = :id'
        );
        $stmt->execute([':resolvido' => (int) $resolvido, ':id' => $erroId]);

        // Retorna também o total de pendentes para atualização do contador na UI
        $erroRow = $this->conexao->prepare(
            'SELECT importacao_id FROM import_erros WHERE id = :id'
        );
        $erroRow->execute([':id' => $erroId]);
        $row = $erroRow->fetch(PDO::FETCH_ASSOC);

        $pendentes = 0;
        if ($row) {
            $stmtPend = $this->conexao->prepare(
                'SELECT COUNT(*) FROM import_erros WHERE importacao_id = :imp_id AND resolvido = 0'
            );
            $stmtPend->execute([':imp_id' => $row['importacao_id']]);
            $pendentes = (int) $stmtPend->fetchColumn();
        }

        echo json_encode([
            'sucesso'   => true,
            'resolvido' => $resolvido,
            'pendentes' => $pendentes,
        ]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────── //
    //  HELPERS PRIVADOS                                                        //
    // ─────────────────────────────────────────────────────────────────────── //

    /**
     * Busca resumo de pendentes e resolvidos para o cabeçalho da view.
     */
    private function buscarResumo(int $comumId, int $importacaoId): array
    {
        if ($comumId > 0) {
            $sql = 'SELECT
                        SUM(ie.resolvido = 0) AS pendentes,
                        SUM(ie.resolvido = 1) AS resolvidos
                      FROM import_erros ie
                      JOIN importacoes imp ON ie.importacao_id = imp.id
                     WHERE imp.comum_id = :id';
            $param = [':id' => $comumId];
        } elseif ($importacaoId > 0) {
            $sql = 'SELECT
                        SUM(resolvido = 0) AS pendentes,
                        SUM(resolvido = 1) AS resolvidos
                      FROM import_erros
                     WHERE importacao_id = :id';
            $param = [':id' => $importacaoId];
        } else {
            $sql = 'SELECT
                        SUM(resolvido = 0) AS pendentes,
                        SUM(resolvido = 1) AS resolvidos
                      FROM import_erros';
            $param = [];
        }

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute($param);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'pendentes'  => (int) ($row['pendentes']  ?? 0),
            'resolvidos' => (int) ($row['resolvidos'] ?? 0),
        ];
    }
}


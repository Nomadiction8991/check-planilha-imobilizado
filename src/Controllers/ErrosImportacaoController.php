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

    /**
     * Lista os erros de uma importação específica com paginação.
     */
    public function listar(): void
    {
        if (!SessionManager::isAuthenticated()) {
            $this->redirecionar('/login');
            return;
        }

        $importacaoId = (int) ($_GET['importacao_id'] ?? 0);

        if ($importacaoId <= 0) {
            $this->redirecionar('/spreadsheets/import?erro=ID+de+importação+inválido');
            return;
        }

        $itensPorPagina = 20;
        $paginaAtual    = max(1, (int) ($_GET['pagina'] ?? 1));

        // Total de erros para esta importação
        $stmtTotal = $this->conexao->prepare(
            'SELECT COUNT(*) FROM import_erros WHERE importacao_id = :id'
        );
        $stmtTotal->execute([':id' => $importacaoId]);
        $totalRegistros = (int) $stmtTotal->fetchColumn();

        $totalPaginas = max(1, (int) ceil($totalRegistros / $itensPorPagina));
        $paginaAtual  = min($paginaAtual, $totalPaginas);
        $offset       = ($paginaAtual - 1) * $itensPorPagina;

        // Busca erros paginados
        $stmt = $this->conexao->prepare(
            'SELECT id, linha_csv, codigo, localidade, codigo_comum, descricao_csv,
                    bem, complemento, dependencia, mensagem_erro, resolvido, created_at
               FROM import_erros
              WHERE importacao_id = :id
              ORDER BY id ASC
              LIMIT :limite OFFSET :offset'
        );
        $stmt->bindValue(':id',     $importacaoId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,          PDO::PARAM_INT);
        $stmt->execute();
        $erros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Dados resumidos da importação
        $stmtImp = $this->conexao->prepare(
            'SELECT arquivo_nome, iniciada_em, status FROM importacoes WHERE id = :id'
        );
        $stmtImp->execute([':id' => $importacaoId]);
        $importacao = $stmtImp->fetch(PDO::FETCH_ASSOC) ?: [];

        $this->renderizar('spreadsheets/import-errors', [
            'importacao_id'  => $importacaoId,
            'importacao'     => $importacao,
            'erros'          => $erros,
            'pagina'         => $paginaAtual,
            'total_paginas'  => $totalPaginas,
            'total_registros'=> $totalRegistros,
        ]);
    }

    /**
     * Faz o download dos erros de importação como arquivo CSV.
     */
    public function downloadCsv(): void
    {
        if (!SessionManager::isAuthenticated()) {
            $this->redirecionar('/login');
            return;
        }

        $importacaoId = (int) ($_GET['importacao_id'] ?? 0);

        if ($importacaoId <= 0) {
            http_response_code(400);
            echo 'ID de importação inválido';
            return;
        }

        $stmt = $this->conexao->prepare(
            'SELECT linha_csv, codigo, localidade, codigo_comum, descricao_csv,
                    bem, complemento, dependencia, mensagem_erro, resolvido, created_at
               FROM import_erros
              WHERE importacao_id = :id
              ORDER BY id ASC'
        );
        $stmt->execute([':id' => $importacaoId]);
        $erros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $nomeArquivo = 'erros_importacao_' . $importacaoId . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');

        // BOM UTF-8 para compatibilidade com Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalho
        fputcsv($output, [
            'Linha CSV',
            'Código',
            'Localidade',
            'Código Comum',
            'Descrição CSV',
            'Bem',
            'Complemento',
            'Dependência',
            'Mensagem de Erro',
            'Resolvido',
            'Data/Hora',
        ], ';');

        foreach ($erros as $erro) {
            fputcsv($output, [
                $erro['linha_csv']    ?? '',
                $erro['codigo']       ?? '',
                $erro['localidade']   ?? '',
                $erro['codigo_comum'] ?? '',
                $erro['descricao_csv']?? '',
                $erro['bem']          ?? '',
                $erro['complemento']  ?? '',
                $erro['dependencia']  ?? '',
                $erro['mensagem_erro']?? '',
                ($erro['resolvido'] ?? 0) ? 'Sim' : 'Não',
                $erro['created_at']   ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

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

        $dados      = json_decode(file_get_contents('php://input'), true) ?? [];
        $erroId     = (int) ($dados['erro_id'] ?? 0);
        $resolvido  = (bool) ($dados['resolvido'] ?? true);

        if ($erroId <= 0) {
            echo json_encode(['erro' => 'ID inválido']);
            exit;
        }

        $stmt = $this->conexao->prepare(
            'UPDATE import_erros SET resolvido = :resolvido WHERE id = :id'
        );
        $stmt->execute([':resolvido' => (int) $resolvido, ':id' => $erroId]);

        echo json_encode(['sucesso' => true, 'resolvido' => $resolvido]);
        exit;
    }
}

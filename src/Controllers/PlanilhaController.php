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

        $this->renderizar('planilhas/planilha_visualizar', ['comum_id' => $comumId]);
    }

    public function progresso(): void
    {
        $this->renderizar('planilhas/importacao_progresso');
    }
}

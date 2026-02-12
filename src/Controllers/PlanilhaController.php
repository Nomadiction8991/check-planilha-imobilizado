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

        $this->redirecionar('/planilhas/importar?success=1');
    }

    public function visualizar(): void
    {
        $comumId = (int) ($_GET['comum_id'] ?? 0);
        if ($comumId <= 0) {
            $this->redirecionar('/comuns?erro=ID inválido');
            return;
        }

        $this->renderizar('planilhas/planilha_visualizar', ['comum_id' => $comumId]);
    }

    public function progresso(): void
    {
        $this->renderizar('planilhas/importacao_progresso');
    }
}

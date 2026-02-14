<?php

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Core\SessionManager;
use PDO;

class RelatorioController extends BaseController
{
    private PDO $conexao;

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
    }

    public function relatorio141(): void
    {
        $comumId = SessionManager::ensureComumId();
        $idPlanilha = $_GET['id'] ?? $comumId;

        $this->renderizar('reports/report-141', [
            'id_planilha' => $idPlanilha,
            'comum_id' => $comumId,
        ]);
    }

    public function visualizar(): void
    {
        $formulario = $_GET['formulario'] ?? '';
        if (empty($formulario)) {
            $this->redirecionar('/churches?erro=Formulário não especificado');
            return;
        }

        $comumId = SessionManager::ensureComumId();

        $this->renderizar('reports/view', [
            'formulario' => $formulario,
            'id_planilha' => $_GET['id'] ?? $comumId,
            'comum_id' => $comumId,
        ]);
    }

    public function assinatura(): void
    {
        $comumId = SessionManager::ensureComumId();

        $this->renderizar('reports/signature', [
            'id_planilha' => $_GET['id'] ?? $comumId,
            'comum_id' => $comumId,
        ]);
    }
}

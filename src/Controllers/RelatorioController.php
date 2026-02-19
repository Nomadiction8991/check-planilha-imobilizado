<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Core\SessionManager;
use PDO;

class RelatorioController extends BaseController
{
    public function __construct(?PDO $conexao = null)
    {
        // Dependencies handled by repositories if needed in the future
    }

    public function relatorio141(): void
    {
        $comumId = SessionManager::getComumId();
        $idPlanilha = $this->query('id', $comumId);

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

        $comumId = SessionManager::getComumId();

        $this->renderizar('reports/view', [
            'formulario'  => $formulario,
            'id_planilha' => $this->query('id', $comumId),
            'comum_id'    => $comumId,
        ]);
    }

    public function assinatura(): void
    {
        $comumId = SessionManager::getComumId();

        $this->renderizar('reports/signature', [
            'id_planilha' => $this->query('id', $comumId),
            'comum_id'    => $comumId,
        ]);
    }

    /**
     * Abre a página de "Relatório de Alterações" (impressão).
     * Rendeiriza a view existente em `spreadsheets/report-print-changes.php`.
     */
    public function alteracoes(): void
    {
        $comumId = SessionManager::getComumId();
        $idPlanilha = $this->query('id', $comumId);

        if (!$idPlanilha || $idPlanilha <= 0) {
            // mantém comportamento consistente: direciona para seleção de comum
            $this->redirecionar('/churches?erro=Selecione+uma+comum+para+ver+o+relat%C3%B3rio');
            return;
        }

        $this->renderizar('spreadsheets/report-print-changes', [
            'id_planilha' => $idPlanilha,
            'comum_id'    => $comumId,
        ]);
    }
}

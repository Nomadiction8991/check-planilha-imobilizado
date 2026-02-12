<?php

namespace App\Controllers;

use App\Core\ConnectionManager;
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
        $this->renderizar('planilhas/relatorio141_view');
    }

    public function visualizar(): void
    {
        $formulario = $_GET['formulario'] ?? '';
        if (empty($formulario)) {
            $this->redirecionar('/comuns?erro=Formulário não especificado');
            return;
        }

        $this->renderizar('planilhas/relatorio_visualizar', ['formulario' => $formulario]);
    }

    public function assinatura(): void
    {
        $this->renderizar('planilhas/relatorio_assinatura');
    }
}

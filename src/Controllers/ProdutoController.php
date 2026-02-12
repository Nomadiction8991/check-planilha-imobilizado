<?php

namespace App\Controllers;

use App\Core\ConnectionManager;
use PDO;

class ProdutoController extends BaseController
{
    private PDO $conexao;

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
    }

    public function index(): void
    {
        $this->renderizar('produtos/produtos_listar');
    }

    public function create(): void
    {
        $this->renderizar('produtos/produto_criar');
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/produtos');
            return;
        }

        // TODO: Implementar lógica de criação
        http_response_code(501);
        die('Funcionalidade em implementação. Controller pendente de migração.');
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirecionar('/produtos?erro=ID inválido');
            return;
        }

        $this->renderizar('produtos/produto_editar', ['id' => $id]);
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/produtos');
            return;
        }

        // TODO: Implementar lógica de atualização
        http_response_code(501);
        die('Funcionalidade em implementação. Controller pendente de migração.');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        // TODO: Implementar lógica de exclusão
        $this->jsonErro('Funcionalidade em implementação. Controller pendente de migração.', 501);
    }

    public function observacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        // TODO: Implementar lógica de observação
        $this->jsonErro('Funcionalidade em implementação. Controller pendente de migração.', 501);
    }

    public function check(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/produtos');
            return;
        }

        // TODO: Implementar lógica de check
        http_response_code(501);
        die('Funcionalidade em implementação. Controller pendente de migração.');
    }

    public function etiqueta(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/produtos');
            return;
        }

        // TODO: Implementar lógica de etiqueta
        http_response_code(501);
        die('Funcionalidade em implementação. Controller pendente de migração.');
    }

    public function assinar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        // TODO: Implementar lógica de assinatura
        $this->jsonErro('Funcionalidade em implementação. Controller pendente de migração.', 501);
    }
}

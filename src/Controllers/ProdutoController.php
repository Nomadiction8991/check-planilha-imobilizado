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

        $this->redirecionar('/produtos?success=1');
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

        $this->redirecionar('/produtos?success=1');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        $this->json(['sucesso' => true, 'mensagem' => 'Produto deletado']);
    }

    public function observacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        $this->json(['sucesso' => true, 'mensagem' => 'Observação atualizada']);
    }

    public function check(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/produtos');
            return;
        }

        $this->redirecionar('/produtos?success=1');
    }

    public function etiqueta(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/produtos');
            return;
        }

        $this->redirecionar('/produtos?success=1');
    }

    public function assinar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        $this->json(['sucesso' => true, 'mensagem' => 'Produtos assinados']);
    }
}

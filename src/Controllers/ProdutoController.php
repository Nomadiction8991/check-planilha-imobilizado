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
        $comumId = (int) ($_GET['comum_id'] ?? 0);

        // Se não tiver comum_id, redireciona para página de comuns
        if ($comumId <= 0) {
            $this->redirecionar('/comuns?mensagem=' . urlencode('Selecione um Comum para ver os produtos'));
            return;
        }

        // Parâmetros de paginação e filtros
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $limite = 10;

        // Filtros
        $filtroComplemento = trim($_GET['filtro_complemento'] ?? '');
        $pesquisaId = trim($_GET['pesquisa_id'] ?? '');
        $filtroTipoBen = trim($_GET['filtro_tipo_ben'] ?? '');
        $filtroBem = trim($_GET['filtro_bem'] ?? '');
        $filtroDependencia = trim($_GET['filtro_dependencia'] ?? '');
        $filtroStatus = trim($_GET['filtro_STATUS'] ?? '');

        // TODO: Implementar busca real de produtos com filtros e paginação
        $produtos = [];
        $totalRegistros = 0;
        $totalPaginas = 1;

        // TODO: Implementar busca de tipos de bens, códigos de bens e dependências
        $tiposBens = [];
        $bemCodigos = [];
        $dependencias = [];

        $this->renderizar('produtos/produtos_listar', [
            'comum_id' => $comumId,
            'produtos' => $produtos,
            'pagina' => $pagina,
            'total_paginas' => $totalPaginas,
            'total_registros' => $totalRegistros,
            'filtro_complemento' => $filtroComplemento,
            'pesquisa_id' => $pesquisaId,
            'filtro_tipo_ben' => $filtroTipoBen,
            'filtro_bem' => $filtroBem,
            'filtro_dependencia' => $filtroDependencia,
            'filtro_STATUS' => $filtroStatus,
            'tipos_bens' => $tiposBens,
            'bem_codigos' => $bemCodigos,
            'dependencias' => $dependencias
        ]);
    }

    public function create(): void
    {
        $comumId = (int) ($_GET['comum_id'] ?? 0);

        // Se não tiver comum_id, redireciona para página de comuns
        if ($comumId <= 0) {
            $this->redirecionar('/comuns?mensagem=' . urlencode('Selecione um Comum para criar um produto'));
            return;
        }

        $this->renderizar('produtos/produto_criar', ['comum_id' => $comumId]);
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
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Renderiza a view de copiar etiquetas
            require_once __DIR__ . '/../Views/planilhas/produto_copiar_etiquetas.php';
            return;
        }

        // TODO: Implementar lógica de etiqueta POST
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

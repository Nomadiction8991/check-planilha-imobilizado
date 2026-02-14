<?php

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Services\TipoBemService;
use App\Repositories\TipoBemRepository;
use PDO;

class ProdutoController extends BaseController
{
    private PDO $conexao;
    private TipoBemService $tipoBemService;

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();

        // Inicializa TipoBemService
        $tipoBemRepo = new TipoBemRepository($this->conexao);
        $this->tipoBemService = new TipoBemService($tipoBemRepo);
    }

    public function index(): void
    {
        // Usa comum_id da sessão
        $comumId = \App\Core\SessionManager::ensureComumId();

        // Se não tiver comum_id, redireciona para página de comuns
        if (!$comumId || $comumId <= 0) {
            $this->redirecionar('/churches?mensagem=' . urlencode('Selecione um Comum para ver os produtos'));
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

        // Busca tipos de bens, códigos de bens e dependências
        try {
            $tiposBens = $this->tipoBemService->buscarTodos();
        } catch (\Throwable $e) {
            error_log('Erro ao buscar tipos_bens: ' . $e->getMessage());
            $tiposBens = [];
        }

        $bemCodigos = [];
        $dependencias = [];

        $this->renderizar('products/index', [
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
        // Usa comum_id da sessão
        $comumId = \App\Core\SessionManager::ensureComumId();

        // Se não tiver comum_id, redireciona para página de comuns
        if (!$comumId || $comumId <= 0) {
            $this->redirecionar('/churches?mensagem=' . urlencode('Selecione um Comum para criar um produto'));
            return;
        }

        // Carrega tipos de bens
        try {
            $tiposBens = $this->tipoBemService->buscarTodos();
        } catch (\Throwable $e) {
            error_log('Erro ao buscar tipos_bens: ' . $e->getMessage());
            $tiposBens = [];
        }

        $this->renderizar('products/create', [
            'comum_id' => $comumId,
            'tipos_bens' => $tiposBens
        ]);
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/products');
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
            $this->redirecionar('/products?erro=ID inválido');
            return;
        }

        // Carrega tipos de bens
        try {
            $tiposBens = $this->tipoBemService->buscarTodos();
        } catch (\Throwable $e) {
            error_log('Erro ao buscar tipos_bens: ' . $e->getMessage());
            $tiposBens = [];
        }

        $this->renderizar('products/edit', [
            'id' => $id,
            'tipos_bens' => $tiposBens
        ]);
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/products');
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

        $comumId = \App\Core\SessionManager::ensureComumId();
        if (!$comumId) {
            $this->jsonErro('Comum não selecionada', 400);
            return;
        }

        $produtoId = (int) ($_POST['produto_id'] ?? 0);
        $observacao = trim($_POST['observacao'] ?? '');

        if ($produtoId <= 0) {
            $this->jsonErro('Produto inválido', 400);
            return;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE produtos SET observacao = :obs WHERE id_produto = :id AND comum_id = :comum_id"
            );
            $stmt->bindValue(':obs', $observacao, PDO::PARAM_STR);
            $stmt->bindValue(':id', $produtoId, PDO::PARAM_INT);
            $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
            $stmt->execute();

            $this->json(['sucesso' => true, 'mensagem' => 'Observação salva com sucesso']);
        } catch (\Exception $e) {
            error_log('Erro ao salvar observação: ' . $e->getMessage());
            $this->jsonErro('Erro ao salvar observação', 500);
        }
    }

    public function check(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/spreadsheets/view');
            return;
        }

        $comumId = \App\Core\SessionManager::ensureComumId();
        if (!$comumId) {
            $this->redirecionar('/spreadsheets/view?erro=Comum não selecionada');
            return;
        }

        $produtoId = (int) ($_POST['produto_id'] ?? 0);
        $checado = (int) ($_POST['checado'] ?? 0);

        if ($produtoId <= 0) {
            $this->redirecionar('/spreadsheets/view?erro=Produto inválido');
            return;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE produtos SET checado = :checado WHERE id_produto = :id AND comum_id = :comum_id"
            );
            $stmt->bindValue(':checado', $checado, PDO::PARAM_INT);
            $stmt->bindValue(':id', $produtoId, PDO::PARAM_INT);
            $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
            $stmt->execute();

            $this->redirecionar('/spreadsheets/view?sucesso=Produto atualizado');
        } catch (\Exception $e) {
            error_log('Erro ao atualizar check: ' . $e->getMessage());
            $this->redirecionar('/spreadsheets/view?erro=Erro ao atualizar produto');
        }
    }

    public function etiqueta(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Garante que id_planilha esteja disponível na view
            $id_planilha = $_GET['id'] ?? \App\Core\SessionManager::ensureComumId();
            $conexao = $this->conexao;
            require_once __DIR__ . '/../Views/spreadsheets/copy-labels.php';
            return;
        }

        // POST - marcar/desmarcar para impressão de etiqueta
        $comumId = \App\Core\SessionManager::ensureComumId();
        if (!$comumId) {
            $this->redirecionar('/spreadsheets/view?erro=Comum não selecionada');
            return;
        }

        $produtoId = (int) ($_POST['produto_id'] ?? 0);
        $imprimir = (int) ($_POST['imprimir'] ?? 0);

        if ($produtoId <= 0) {
            $this->redirecionar('/spreadsheets/view?erro=Produto inválido');
            return;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE produtos SET imprimir_etiqueta = :imprimir WHERE id_produto = :id AND comum_id = :comum_id"
            );
            $stmt->bindValue(':imprimir', $imprimir, PDO::PARAM_INT);
            $stmt->bindValue(':id', $produtoId, PDO::PARAM_INT);
            $stmt->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
            $stmt->execute();

            $this->redirecionar('/spreadsheets/view?sucesso=Etiqueta atualizada');
        } catch (\Exception $e) {
            error_log('Erro ao atualizar etiqueta: ' . $e->getMessage());
            $this->redirecionar('/spreadsheets/view?erro=Erro ao atualizar etiqueta');
        }
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

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
        // Usa comum_id da sessão
        $comumId = \App\Core\SessionManager::ensureComumId();

        // Se não tiver comum_id, redireciona para página de comuns
        if (!$comumId || $comumId <= 0) {
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
        // Usa comum_id da sessão
        $comumId = \App\Core\SessionManager::ensureComumId();

        // Se não tiver comum_id, redireciona para página de comuns
        if (!$comumId || $comumId <= 0) {
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
            $this->redirecionar('/planilhas/visualizar');
            return;
        }

        $comumId = \App\Core\SessionManager::ensureComumId();
        if (!$comumId) {
            $this->redirecionar('/planilhas/visualizar?erro=Comum não selecionada');
            return;
        }

        $produtoId = (int) ($_POST['produto_id'] ?? 0);
        $checado = (int) ($_POST['checado'] ?? 0);

        if ($produtoId <= 0) {
            $this->redirecionar('/planilhas/visualizar?erro=Produto inválido');
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

            $this->redirecionar('/planilhas/visualizar?sucesso=Produto atualizado');
        } catch (\Exception $e) {
            error_log('Erro ao atualizar check: ' . $e->getMessage());
            $this->redirecionar('/planilhas/visualizar?erro=Erro ao atualizar produto');
        }
    }

    public function etiqueta(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Renderiza a view de copiar etiquetas
            require_once __DIR__ . '/../Views/planilhas/produto_copiar_etiquetas.php';
            return;
        }

        // POST - marcar/desmarcar para impressão de etiqueta
        $comumId = \App\Core\SessionManager::ensureComumId();
        if (!$comumId) {
            $this->redirecionar('/planilhas/visualizar?erro=Comum não selecionada');
            return;
        }

        $produtoId = (int) ($_POST['produto_id'] ?? 0);
        $imprimir = (int) ($_POST['imprimir'] ?? 0);

        if ($produtoId <= 0) {
            $this->redirecionar('/planilhas/visualizar?erro=Produto inválido');
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

            $this->redirecionar('/planilhas/visualizar?sucesso=Etiqueta atualizada');
        } catch (\Exception $e) {
            error_log('Erro ao atualizar etiqueta: ' . $e->getMessage());
            $this->redirecionar('/planilhas/visualizar?erro=Erro ao atualizar etiqueta');
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

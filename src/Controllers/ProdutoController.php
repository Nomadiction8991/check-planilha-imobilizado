<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Core\SessionManager;
use App\Repositories\DependenciaRepository;
use App\Repositories\ProdutoRepository;
use App\Repositories\TipoBemRepository;
use App\Services\TipoBemService;
use PDO;

class ProdutoController extends BaseController
{
    private ProdutoRepository $produtoRepository;
    private TipoBemService $tipoBemService;
    private DependenciaRepository $dependenciaRepository;
    private PDO $conexao;

    public function __construct(?PDO $conexao = null)
    {
        $conexao = $conexao ?? ConnectionManager::getConnection();
        $this->conexao = $conexao;

        $this->produtoRepository = new ProdutoRepository($conexao);
        $this->dependenciaRepository = new DependenciaRepository($conexao);

        $tipoBemRepo = new TipoBemRepository($conexao);
        $this->tipoBemService = new TipoBemService($tipoBemRepo);
    }

    // ─── helpers privados ───────────────────────────────────────────

    /**
     * Obtém ID da comum da sessão ou redireciona com mensagem de erro.
     * SEGURANÇA: usa construirUrl para evitar XSS em query parameters
     */
    private function obterComumIdOuRedirecionar(string $mensagem = 'Selecione uma igreja'): ?int
    {
        $comumId = SessionManager::getComumId();
        if (!$comumId || $comumId <= 0) {
            $url = $this->construirUrl('/churches', ['mensagem' => $mensagem]);
            $this->redirecionar($url);
            return null;
        }
        return $comumId;
    }

    private function obterTiposBens(): array
    {
        try {
            return $this->tipoBemService->buscarTodos();
        } catch (\Throwable $e) {
            error_log('Erro ao buscar tipos_bens: ' . $e->getMessage());
            return [];
        }
    }

    private function obterDependencias(int $comumId): array
    {
        try {
            return $this->dependenciaRepository->buscarPaginadoPorComum($comumId, '', 1000, 0);
        } catch (\Throwable $e) {
            error_log('Erro ao buscar dependências: ' . $e->getMessage());
            return [];
        }
    }

    private function buscarProdutoDaComum(int $idProduto, int $comumId): ?array
    {
        return $this->produtoRepository->buscarPorIdEComum($idProduto, $comumId);
    }

    /**
     * Extrai os parâmetros de filtro da query string (usados para voltar à listagem).
     */
    private function extrairFiltros(): array
    {
        return [
            'pagina'             => max(1, (int) $this->query('pagina', 1)),
            'filtro_nome'        => trim($this->query('nome', '')),
            'filtro_dependencia' => trim($this->query('dependencia', '')),
            'filtro_codigo'      => trim($this->query('filtro_codigo', '')),
            'filtro_status'      => trim($this->getStringParam('status', '', 'filtro_status', 'STATUS')),
        ];
    }

    /**
     * Monta a URL de retorno para /products/view preservando filtros.
     * SEGURANÇA: usa construirUrl para escapar parâmetros
     */
    private function urlRetorno(?int $comumId = null): string
    {
        $params = [];
        if ($comumId) {
            $params['comum_id'] = $comumId;
        }
        $filtros = $this->extrairFiltros();
        if ($filtros['pagina'] > 1)             $params['pagina']           = $filtros['pagina'];
        if ($filtros['filtro_nome'] !== '')      $params['nome']             = $filtros['filtro_nome'];
        if ($filtros['filtro_dependencia'] !== '') $params['dependencia']    = $filtros['filtro_dependencia'];
        if ($filtros['filtro_codigo'] !== '')    $params['filtro_codigo']    = $filtros['filtro_codigo'];
        if ($filtros['filtro_status'] !== '')    $params['status']           = $filtros['filtro_status'];

        return $this->construirUrl('/products/view', $params);
    }

    // ─── CRUD ───────────────────────────────────────────────────────

    public function create(): void
    {
        $comumId = $this->obterComumIdOuRedirecionar('Selecione uma igreja para criar um produto');
        if ($comumId === null) return;

        $this->renderizar('products/create', [
            'comum_id'     => $comumId,
            'tipos_bens'   => $this->obterTiposBens(),
            'dependencias' => $this->obterDependencias($comumId),
            'erros'        => [],
        ]);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirecionar('/products/create');
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->redirecionar('/products/create?erro=' . urlencode('Token de segurança inválido.'));
            return;
        }

        $comumId = $this->obterComumIdOuRedirecionar();
        if ($comumId === null) return;

        // Coletar dados do formulário
        $codigo         = trim($this->post('codigo', ''));
        $idTipoBem      = (int) $this->post('id_tipo_ben', 0);
        $bem            = mb_strtoupper(trim($this->post('tipo_ben', '')), 'UTF-8');
        $complemento    = mb_strtoupper(trim($this->post('complemento', '')), 'UTF-8');
        $dependenciaId  = (int) $this->post('id_dependencia', 0);
        $multiplicador  = max(1, (int) $this->post('multiplicador', 1));
        $imprimir141    = (int) $this->post('imprimir_14_1', 0);
        $condicao141    = mb_strtoupper(trim($this->post('condicao_14_1', '')), 'UTF-8');

        // Normalizar condicao_14_1: aceitar apenas '1','2','3'. Se vazio/inválido,
        // usar '2' como padrão (opção do meio) independentemente do checkbox.
        if (!in_array($condicao141, ['1', '2', '3'], true)) {
            $condicao141 = '2';
        }
        $notaNumero     = $this->post('nota_numero', '');
        $notaData       = $this->post('nota_data', '');
        $notaValor      = trim($this->post('nota_valor', ''));
        $notaFornecedor = mb_strtoupper(trim($this->post('nota_fornecedor', '')), 'UTF-8');

        // Validação
        $erros = [];
        if ($idTipoBem <= 0)     $erros[] = 'Selecione um Tipo de Bem.';
        if ($bem === '')         $erros[] = 'Selecione um Bem.';
        if ($complemento === '') $erros[] = 'Informe o complemento.';
        if ($dependenciaId <= 0) $erros[] = 'Selecione uma Dependência.';

        if (!empty($erros)) {
            $this->renderizar('products/create', [
                'comum_id'     => $comumId,
                'tipos_bens'   => $this->obterTiposBens(),
                'dependencias' => $this->obterDependencias($comumId),
                'erros'        => $erros,
            ]);
            return;
        }

        try {
            $this->conexao->beginTransaction();

            try {
                for ($i = 0; $i < $multiplicador; $i++) {
                    $this->produtoRepository->criar([
                        'comum_id'           => $comumId,
                        'codigo'             => $codigo !== '' ? $codigo : null,
                        'tipo_bem_id'        => $idTipoBem,
                        'bem'                => $bem,
                        'complemento'        => $complemento,
                        'dependencia_id'     => $dependenciaId,
                        'novo'               => 1,
                        'importado'          => 0,
                        'checado'            => 0,
                        'editado'            => 0,
                        'imprimir_etiqueta'  => 0,
                        'imprimir_14_1'      => $imprimir141,
                        'condicao_14_1'      => $condicao141 !== '' ? $condicao141 : null,
                        'nota_numero'        => $notaNumero !== '' ? (int)$notaNumero : null,
                        'nota_data'          => $notaData !== '' ? $notaData : null,
                        'nota_valor'         => $notaValor !== '' ? $notaValor : null,
                        'nota_fornecedor'    => $notaFornecedor !== '' ? $notaFornecedor : null,
                        'observacao'         => '',
                        'ativo'              => 1,
                    ]);
                }

                $this->conexao->commit();
            } catch (\Exception $e) {
                $this->conexao->rollBack();
                throw $e;
            }

            $msg = $multiplicador > 1
                ? "$multiplicador produtos cadastrados com sucesso"
                : 'Produto cadastrado com sucesso';
            $url = $this->construirUrl('/products/view', ['comum_id' => $comumId, 'sucesso' => $msg]);
            $this->redirecionar($url);
        } catch (\Exception $e) {
            error_log('Erro ao cadastrar produto: ' . $e->getMessage());
            $this->renderizar('products/create', [
                'comum_id'     => $comumId,
                'tipos_bens'   => $this->obterTiposBens(),
                'dependencias' => $this->obterDependencias($comumId),
                'erros'        => ['Erro ao salvar produto: ' . $e->getMessage()],
            ]);
        }
    }

    public function edit(): void
    {
        $comumId = $this->obterComumIdOuRedirecionar();
        if ($comumId === null) return;

        $idProduto = $this->getIntParam('id_produto', 'id', 'id_PRODUTO');
        if ($idProduto <= 0) {
            $url = $this->construirUrl('/products/view', ['erro' => 'ID do produto inválido']);
            $this->redirecionar($url);
            return;
        }

        $produto = $this->buscarProdutoDaComum($idProduto, $comumId);
        if (!$produto) {
            $url = $this->construirUrl('/products/view', ['comum_id' => $comumId, 'erro' => 'Produto não encontrado para a igreja selecionada']);
            $this->redirecionar($url);
            return;
        }

        $filtros = $this->extrairFiltros();

        $this->renderizar('products/edit', [
            'id_produto'         => $idProduto,
            'produto'            => $produto,
            'comum_id'           => $comumId,
            'tipos_bens'         => $this->obterTiposBens(),
            'dependencias'       => $this->obterDependencias($comumId),
            'pagina'             => $filtros['pagina'],
            'filtro_nome'        => $filtros['filtro_nome'],
            'filtro_dependencia' => $filtros['filtro_dependencia'],
            'filtro_codigo'      => $filtros['filtro_codigo'],
            'filtro_status'      => $filtros['filtro_status'],
            'filtro_STATUS'      => $filtros['filtro_status'],
            // Valores editados (já salvos) para pré-preencher o form
            'novo_tipo_bem_id'   => $produto['editado_tipo_bem_id'] ?: null,
            'novo_bem'           => !empty($produto['editado_bem']) ? $produto['editado_bem'] : $produto['bem'],
            'novo_complemento'   => !empty($produto['editado_complemento']) ? $produto['editado_complemento'] : ($produto['complemento'] ?? ''),
            'nova_dependencia_id' => $produto['editado_dependencia_id'] ?: null,
            'mensagem'           => '',
            'tipo_mensagem'      => '',
        ]);
    }

    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirecionar('/products/view');
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->redirecionar('/products/view?erro=' . urlencode('Token de segurança inválido.'));
            return;
        }

        $comumId = $this->obterComumIdOuRedirecionar();
        if ($comumId === null) return;

        $idProduto = $this->getIntParam('id_produto', 'id', 'id_PRODUTO');
        if ($idProduto <= 0) {
            $url = $this->construirUrl('/products/view', ['erro' => 'Produto inválido']);
            $this->redirecionar($url);
            return;
        }

        $produto = $this->buscarProdutoDaComum($idProduto, $comumId);
        if (!$produto) {
            $url = $this->construirUrl('/products/view', ['comum_id' => $comumId, 'erro' => 'Produto não encontrado para a igreja selecionada']);
            $this->redirecionar($url);
            return;
        }

        // Coletar dados editados do formulário
        $novoTipoBemId    = $this->post('novo_tipo_bem_id', '');
        $novoBem          = mb_strtoupper(trim($this->post('novo_bem', '')), 'UTF-8');
        $novoComplemento  = mb_strtoupper(trim($this->post('novo_complemento', '')), 'UTF-8');
        $novaDependencia  = $this->post('nova_dependencia_id', '');

        // Campos diretos (não editado_)
        $imprimir141    = $this->post('imprimir_14_1', null);
        $condicao141    = trim($this->post('condicao_14_1', ''));

        // Normalizar condicao_14_1: aceitar apenas '1','2','3'. Se vazio/inválido,
        // usar '2' como padrão (opção do meio) independentemente do checkbox.
        if (!in_array($condicao141, ['1', '2', '3'], true)) {
            $condicao141 = '2';
        }
        $notaNumero     = $this->post('nota_numero', '');
        $notaData       = $this->post('nota_data', '');
        $notaValor      = trim($this->post('nota_valor', ''));
        $notaFornecedor = mb_strtoupper(trim($this->post('nota_fornecedor', '')), 'UTF-8');

        // Montar dados para atualização (campos editados)
        $dadosUpdate = [
            'editado' => 0, // será marcado como 1 se algo mudar
        ];

        $temEdicao = false;

        if ($novoTipoBemId !== '' && (int)$novoTipoBemId > 0) {
            $dadosUpdate['editado_tipo_bem_id'] = (int)$novoTipoBemId;
            $temEdicao = true;
        }

        if ($novoBem !== '') {
            $dadosUpdate['editado_bem'] = $novoBem;
            $temEdicao = true;
        }

        if ($novoComplemento !== '') {
            $dadosUpdate['editado_complemento'] = $novoComplemento;
            $temEdicao = true;
        }

        if ($novaDependencia !== '' && (int)$novaDependencia > 0) {
            $dadosUpdate['editado_dependencia_id'] = (int)$novaDependencia;
            $temEdicao = true;
        }

        // Campos diretos: imprimir 14.1, condição, nota fiscal
        $dadosUpdate['imprimir_14_1'] = $imprimir141 !== null ? 1 : 0;
        if ($condicao141 !== '') {
            $dadosUpdate['condicao_14_1'] = mb_strtoupper($condicao141, 'UTF-8');
        } else {
            $dadosUpdate['condicao_14_1'] = '';
        }
        $dadosUpdate['nota_numero']     = $notaNumero !== '' ? (int)$notaNumero : null;
        $dadosUpdate['nota_data']       = $notaData !== '' ? $notaData : null;
        $dadosUpdate['nota_valor']      = $notaValor !== '' ? $notaValor : null;
        $dadosUpdate['nota_fornecedor'] = $notaFornecedor !== '' ? $notaFornecedor : null;

        if ($temEdicao) {
            $dadosUpdate['editado'] = 1;
        }

        try {
            $this->produtoRepository->atualizarPorIdEComum($idProduto, $comumId, $dadosUpdate);

            // Recuperar filtros do POST (enviados como hidden fields)
            $pagina     = $this->post('pagina', '1');
            $nome       = $this->post('nome', '');
            $dep        = $this->post('dependencia', '');
            $codigo     = $this->post('filtro_codigo', '');
            $status     = $this->getStringParam('status', '', 'STATUS');

            $params = ['comum_id' => $comumId, 'sucesso' => 'Produto atualizado com sucesso'];
            if ($pagina > 1)    $params['pagina']       = $pagina;
            if ($nome !== '')   $params['nome']         = $nome;
            if ($dep !== '')    $params['dependencia']   = $dep;
            if ($codigo !== '') $params['filtro_codigo'] = $codigo;
            if ($status !== '') $params['status']        = $status;

            $this->redirecionar($this->construirUrl('/products/view', $params));
        } catch (\Exception $e) {
            error_log('Erro ao atualizar produto: ' . $e->getMessage());
            $filtros = $this->extrairFiltros();
            $this->renderizar('products/edit', [
                'id_produto'         => $idProduto,
                'produto'            => $produto,
                'comum_id'           => $comumId,
                'tipos_bens'         => $this->obterTiposBens(),
                'dependencias'       => $this->obterDependencias($comumId),
                'pagina'             => $filtros['pagina'],
                'filtro_nome'        => $filtros['filtro_nome'],
                'filtro_dependencia' => $filtros['filtro_dependencia'],
                'filtro_codigo'      => $filtros['filtro_codigo'],
                'filtro_status'      => $filtros['filtro_status'],
                'filtro_STATUS'      => $filtros['filtro_status'],
                'novo_tipo_bem_id'   => $novoTipoBemId,
                'novo_bem'           => $novoBem,
                'novo_complemento'   => $novoComplemento,
                'nova_dependencia_id' => $novaDependencia,
                'mensagem'           => 'Erro ao atualizar: ' . $e->getMessage(),
                'tipo_mensagem'      => 'danger',
            ]);
        }
    }

    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->jsonErro('Token de segurança inválido', 403);
            return;
        }

        $comumId = $this->obterComumIdOuRedirecionar();
        if ($comumId === null) return;

        $produtoId = (int) ($this->post('produto_id', 0));
        $idsProdutos = $this->post('ids_PRODUTOS', []);

        if (is_array($idsProdutos) && !empty($idsProdutos)) {
            try {
                $total = $this->produtoRepository->desativarEmLotePorComum($idsProdutos, $comumId);

                if ($this->isAjax()) {
                    $this->json([
                        'success' => true,
                        'message' => $total > 0
                            ? "{$total} produto(s) removido(s) com sucesso"
                            : 'Nenhum produto elegível foi removido'
                    ]);
                }

                $this->redirecionar($this->construirUrl('/products/view', [
                    'comum_id' => $comumId,
                    'sucesso' => $total > 0
                        ? "{$total} produto(s) removido(s) com sucesso"
                        : 'Nenhum produto elegível foi removido'
                ]));
                return;
            } catch (\Exception $e) {
                error_log('Erro ao deletar produtos em lote: ' . $e->getMessage());

                if ($this->isAjax()) {
                    $this->jsonErro('Erro ao remover produtos', 500);
                }

                $this->redirecionar($this->construirUrl('/products/view', [
                    'comum_id' => $comumId,
                    'erro' => 'Erro ao remover produtos'
                ]));
                return;
            }
        }

        if ($produtoId <= 0) {
            if ($this->isAjax()) {
                $this->jsonErro('Produto inválido', 400);
            }

            $this->redirecionar($this->construirUrl('/products/view', [
                'comum_id' => $comumId,
                'erro' => 'Produto inválido'
            ]));
            return;
        }

        try {
            // Soft delete: marca como inativo
            if (!$this->buscarProdutoDaComum($produtoId, $comumId)) {
                if ($this->isAjax()) {
                    $this->jsonErro('Produto não encontrado para a igreja selecionada', 404);
                }

                $this->redirecionar($this->construirUrl('/products/view', [
                    'comum_id' => $comumId,
                    'erro' => 'Produto não encontrado para a igreja selecionada'
                ]));
                return;
            }

            $this->produtoRepository->desativarPorIdEComum($produtoId, $comumId);
            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Produto removido com sucesso',
                    'sucesso' => true,
                    'mensagem' => 'Produto removido com sucesso'
                ]);
            }

            $this->redirecionar($this->construirUrl('/products/view', [
                'comum_id' => $comumId,
                'sucesso' => 'Produto removido com sucesso'
            ]));
        } catch (\Exception $e) {
            error_log('Erro ao deletar produto: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonErro('Erro ao remover produto', 500);
            }

            $this->redirecionar($this->construirUrl('/products/view', [
                'comum_id' => $comumId,
                'erro' => 'Erro ao remover produto'
            ]));
        }
    }

    // ─── Observação ─────────────────────────────────────────────────

    /**
     * GET /products/observation — renderiza o formulário de observação.
     */
    public function observacaoForm(): void
    {
        $comumId = $this->obterComumIdOuRedirecionar();
        if ($comumId === null) return;

        $idProduto = (int) ($this->query('id_produto', 0));
        if ($idProduto <= 0) {
            $this->redirecionar('/products/view?erro=' . urlencode('Produto inválido'));
            return;
        }

        $produto = $this->buscarProdutoDaComum($idProduto, $comumId);
        if (!$produto) {
            $this->redirecionar('/products/view?comum_id=' . urlencode((string) $comumId) . '&erro=' . urlencode('Produto não encontrado para a igreja selecionada'));
            return;
        }

        $filtros = $this->extrairFiltros();

        $this->renderizar('products/observation', [
            'id_produto'         => $idProduto,
            'produto'            => $produto,
            'comum_id'           => $comumId,
            'pagina'             => $filtros['pagina'],
            'filtro_nome'        => $filtros['filtro_nome'],
            'filtro_dependencia' => $filtros['filtro_dependencia'],
            'filtro_codigo'      => $filtros['filtro_codigo'],
            'filtro_status'      => $filtros['filtro_status'],
            'filtro_STATUS'      => $filtros['filtro_status'],
            'check'              => ['observacoes' => $produto['observacao'] ?? ''],
            'mensagem'           => '',
            'tipo_mensagem'      => '',
        ]);
    }

    /**
     * POST /products/observation — salva a observação (form submit ou AJAX).
     */
    public function observacao(): void
    {
        if (!$this->isPost()) {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        if (!$this->validateCsrfToken()) {
            if ($this->isAjax()) {
                $this->jsonErro('Token de segurança inválido', 403);
            } else {
                $this->redirecionar('/products/view?erro=' . urlencode('Token de segurança inválido.'));
            }
            return;
        }

        $comumId = SessionManager::getComumId();
        if (!$comumId) {
            if ($this->isAjax()) {
                $this->jsonErro('Comum não selecionada', 400);
            } else {
                $this->redirecionar('/products/view?erro=' . urlencode('Comum não selecionada'));
            }
            return;
        }

        $produtoId  = (int) ($this->post('produto_id', $this->query('id_produto', 0)));
        $observacao = mb_strtoupper(trim($this->post('observacoes', $this->post('observacao', ''))), 'UTF-8');

        if ($produtoId <= 0) {
            if ($this->isAjax()) {
                $this->jsonErro('Produto inválido', 400);
            } else {
                $this->redirecionar('/products/view?erro=' . urlencode('Produto inválido'));
            }
            return;
        }

        try {
            $this->produtoRepository->atualizarObservacao($produtoId, $comumId, $observacao);

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Observação salva com sucesso',
                    'sucesso' => true,
                    'mensagem' => 'Observação salva com sucesso'
                ]);
            } else {
                // Redirecionar de volta para /products/view com filtros
                $params = ['comum_id' => $comumId, 'sucesso' => urlencode('Observação salva com sucesso')];
                $pagina = $this->post('pagina', '');
                $nome   = $this->post('nome', '');
                $dep    = $this->post('dependencia', '');
                $codigo = $this->post('filtro_codigo', '');
                $status = $this->getStringParam('status', '', 'STATUS');
                if ($pagina !== '' && (int)$pagina > 1) $params['pagina']       = $pagina;
                if ($nome !== '')   $params['nome']         = $nome;
                if ($dep !== '')    $params['dependencia']   = $dep;
                if ($codigo !== '') $params['filtro_codigo'] = $codigo;
                if ($status !== '') $params['status']        = $status;
                $this->redirecionar($this->construirUrl('/products/view', $params));
            }
        } catch (\Exception $e) {
            error_log('Erro ao salvar observação: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonErro('Erro ao salvar observação', 500);
            } else {
                $this->redirecionar('/products/view?erro=' . urlencode('Erro ao salvar observação'));
            }
        }
    }

    // ─── Check / Etiqueta / Assinatura ──────────────────────────────

    public function check(): void
    {
        if (!$this->isPost()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            }
            $this->redirecionar('/products/view');
            return;
        }

        if (!$this->validateCsrfToken()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            }
            $this->redirecionar('/products/view?erro=Token+de+seguran%C3%A7a+inv%C3%A1lido');
            return;
        }

        $comumId = SessionManager::getComumId();
        if (!$comumId) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Comum não selecionada'], 400);
            }
            $this->redirecionar('/products/view?erro=Comum não selecionada');
            return;
        }

        $produtoId = (int) ($this->post('produto_id', 0));
        $checado = (int) ($this->post('checado', 0));

        if ($produtoId <= 0) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Produto inválido'], 400);
            }
            $this->redirecionar('/products/view?erro=Produto inválido');
            return;
        }

        try {
            $this->produtoRepository->atualizarChecado($produtoId, $comumId, $checado);
            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Status atualizado com sucesso',
                    'sucesso' => true,
                    'mensagem' => 'Status atualizado com sucesso'
                ]);
            }
            $this->redirecionar('/products/view?sucesso=Produto atualizado');
        } catch (\Exception $e) {
            error_log('Erro ao atualizar check: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Erro ao atualizar produto'], 500);
            }
            $this->redirecionar('/products/view?erro=Erro ao atualizar produto');
        }
    }

    public function etiqueta(): void
    {
        if ($this->isGet()) {
            $comumId = SessionManager::getComumId();
            if (!$comumId) {
                $this->redirecionar('/churches?erro=Selecione+uma+igreja+para+copiar+etiquetas');
                return;
            }
            $this->renderizar('spreadsheets/copy-labels', [
                'id_planilha' => $comumId,
                'comum_id'    => $comumId,
                'conexao'     => $this->conexao,
            ]);
            return;
        }

        if (!$this->validateCsrfToken()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Token de segurança inválido'], 403);
            }
            $this->redirecionar('/products/view?erro=Token+de+seguran%C3%A7a+inv%C3%A1lido');
            return;
        }

        $comumId = SessionManager::getComumId();
        if (!$comumId) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Comum não selecionada'], 400);
            }
            $this->redirecionar('/products/view?erro=Comum não selecionada');
            return;
        }

        $produtoId = (int) ($this->post('produto_id', 0));
        $imprimir = (int) ($this->post('imprimir', 0));

        if ($produtoId <= 0) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Produto inválido'], 400);
            }
            $this->redirecionar('/products/view?erro=Produto inválido');
            return;
        }

        try {
            $this->produtoRepository->atualizarEtiqueta($produtoId, $comumId, $imprimir);
            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Etiqueta atualizada com sucesso',
                    'sucesso' => true,
                    'mensagem' => 'Etiqueta atualizada com sucesso'
                ]);
            }
            $this->redirecionar('/products/view?sucesso=Etiqueta atualizada');
        } catch (\Exception $e) {
            error_log('Erro ao atualizar etiqueta: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Erro ao atualizar etiqueta'], 500);
            }
            $this->redirecionar('/products/view?erro=Erro ao atualizar etiqueta');
        }
    }

    public function assinar(): void
    {
        if (!$this->isPost()) {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->json(['success' => false, 'message' => 'Token de segurança inválido.'], 403);
            return;
        }

        $comumId = SessionManager::getComumId() ?? 0;
        $usuarioId = SessionManager::getUserId() ?? 0;
        $acao = trim((string) $this->post('acao', ''));
        $produtos = $this->post('PRODUTOS', []);

        if ($comumId <= 0 || $usuarioId <= 0) {
            $this->json(['success' => false, 'message' => 'Sessão inválida. Selecione uma igreja e tente novamente.'], 400);
            return;
        }

        if (!in_array($acao, ['assinar', 'desassinar'], true)) {
            $this->json(['success' => false, 'message' => 'Ação inválida.'], 400);
            return;
        }

        if (!is_array($produtos) || empty($produtos)) {
            $this->json(['success' => false, 'message' => 'Selecione ao menos um produto.'], 400);
            return;
        }

        try {
            $atualizados = $this->produtoRepository->atualizarAdministradorAcessorEmLote(
                $produtos,
                $comumId,
                $acao === 'assinar' ? $usuarioId : null
            );

            $mensagem = $acao === 'assinar'
                ? 'Assinatura aplicada com sucesso.'
                : 'Assinatura removida com sucesso.';

            if ($atualizados === 0) {
                $mensagem = 'Nenhum produto elegível foi atualizado para a igreja selecionada.';
            }

            $this->json([
                'success' => true,
                'message' => $mensagem,
                'updated' => $atualizados,
            ]);
        } catch (\Throwable $e) {
            error_log('Erro ao assinar produtos: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao processar assinatura.'], 500);
        }
    }

    /**
     * POST /products/clear-edits
     * Limpa os campos editados de um produto e redireciona de volta para a listagem.
     */
    public function clearEdits(): void
    {
        if (!$this->isPost()) {
            $this->redirecionar('/products/view?erro=Método+não+permitido');
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->redirecionar('/products/view?erro=Token+de+seguran%C3%A7a+inv%C3%A1lido');
            return;
        }

        $idProduto = (int) $this->post('id_PRODUTO', $this->post('id_produto', $this->post('id', 0)));
        $comumId = SessionManager::getComumId() ?? 0;

        $pagina = $this->post('pagina', 1);
        $nome = $this->post('nome', '');
        $dependencia = $this->post('dependencia', '');
        $filtro_codigo = $this->post('filtro_codigo', '');
        $status = (string) $this->post('status', $this->post('STATUS', ''));

        $params = ['id' => $comumId, 'comum_id' => $comumId, 'pagina' => $pagina, 'nome' => $nome, 'dependencia' => $dependencia, 'filtro_codigo' => $filtro_codigo, 'status' => $status];

        if ($idProduto <= 0 || $comumId <= 0) {
            $params['erro'] = 'Parâmetros inválidos';
            $this->redirecionar($this->construirUrl('/products/view', $params));
            return;
        }

        try {
            if (!$this->buscarProdutoDaComum($idProduto, $comumId)) {
                $params['erro'] = 'Produto não encontrado para a igreja selecionada';
                $this->redirecionar($this->construirUrl('/products/view', $params));
                return;
            }

            $this->produtoRepository->limparEdicoes($idProduto, $comumId);

            $params['sucesso'] = 'Edições limpas com sucesso!';
            $this->redirecionar($this->construirUrl('/products/view', $params));
            return;
        } catch (\Exception $e) {
            error_log('Erro clearEdits: ' . $e->getMessage());
            $params['erro'] = 'Erro ao limpar edições: ' . $e->getMessage();
            $this->redirecionar($this->construirUrl('/products/view', $params));
            return;
        }
    }
}

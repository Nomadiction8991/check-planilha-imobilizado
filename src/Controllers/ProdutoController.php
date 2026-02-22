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

    private function obterComumIdOuRedirecionar(string $mensagem = 'Selecione uma igreja'): ?int
    {
        $comumId = SessionManager::getComumId();
        if (!$comumId || $comumId <= 0) {
            $this->redirecionar('/churches?mensagem=' . urlencode($mensagem));
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

    private function obterDependencias(): array
    {
        try {
            return $this->dependenciaRepository->buscarTodos();
        } catch (\Throwable $e) {
            error_log('Erro ao buscar dependências: ' . $e->getMessage());
            return [];
        }
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
            'filtro_STATUS'      => trim($this->query('status', $this->query('filtro_STATUS', ''))),
        ];
    }

    /**
     * Monta a URL de retorno para /products/view preservando filtros.
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
        if ($filtros['filtro_STATUS'] !== '')    $params['status']           = $filtros['filtro_STATUS'];

        $qs = http_build_query($params);
        return '/products/view' . ($qs ? '?' . $qs : '');
    }

    // ─── CRUD ───────────────────────────────────────────────────────

    public function create(): void
    {
        $comumId = $this->obterComumIdOuRedirecionar('Selecione uma igreja para criar um produto');
        if ($comumId === null) return;

        $this->renderizar('products/create', [
            'comum_id'     => $comumId,
            'tipos_bens'   => $this->obterTiposBens(),
            'dependencias' => $this->obterDependencias(),
            'erros'        => [],
        ]);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirecionar('/products/create');
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
                'dependencias' => $this->obterDependencias(),
                'erros'        => $erros,
            ]);
            return;
        }

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

            $msg = $multiplicador > 1
                ? urlencode("$multiplicador produtos cadastrados com sucesso")
                : urlencode('Produto cadastrado com sucesso');
            $this->redirecionar('/products/view?comum_id=' . $comumId . '&sucesso=' . $msg);
        } catch (\Exception $e) {
            error_log('Erro ao cadastrar produto: ' . $e->getMessage());
            $this->renderizar('products/create', [
                'comum_id'     => $comumId,
                'tipos_bens'   => $this->obterTiposBens(),
                'dependencias' => $this->obterDependencias(),
                'erros'        => ['Erro ao salvar produto: ' . $e->getMessage()],
            ]);
        }
    }

    public function edit(): void
    {
        $comumId = $this->obterComumIdOuRedirecionar();
        if ($comumId === null) return;

        $idProduto = (int) ($this->query('id_produto', $this->query('id', 0)));
        if ($idProduto <= 0) {
            $this->redirecionar('/products/view?erro=' . urlencode('ID do produto inválido'));
            return;
        }

        $produto = $this->produtoRepository->buscarPorId($idProduto);
        if (!$produto) {
            $this->redirecionar('/products/view?erro=' . urlencode('Produto não encontrado'));
            return;
        }

        $filtros = $this->extrairFiltros();

        $this->renderizar('products/edit', [
            'id_produto'         => $idProduto,
            'produto'            => $produto,
            'comum_id'           => $comumId,
            'tipos_bens'         => $this->obterTiposBens(),
            'dependencias'       => $this->obterDependencias(),
            'pagina'             => $filtros['pagina'],
            'filtro_nome'        => $filtros['filtro_nome'],
            'filtro_dependencia' => $filtros['filtro_dependencia'],
            'filtro_codigo'      => $filtros['filtro_codigo'],
            'filtro_STATUS'      => $filtros['filtro_STATUS'],
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

        $comumId = $this->obterComumIdOuRedirecionar();
        if ($comumId === null) return;

        $idProduto = (int) ($this->query('id_produto', $this->query('id', 0)));
        if ($idProduto <= 0) {
            $this->redirecionar('/products/view?erro=' . urlencode('Produto inválido'));
            return;
        }

        $produto = $this->produtoRepository->buscarPorId($idProduto);
        if (!$produto) {
            $this->redirecionar('/products/view?erro=' . urlencode('Produto não encontrado'));
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
            $this->produtoRepository->atualizar($idProduto, $dadosUpdate);

            // Recuperar filtros do POST (enviados como hidden fields)
            $pagina     = $this->post('pagina', '1');
            $nome       = $this->post('nome', '');
            $dep        = $this->post('dependencia', '');
            $codigo     = $this->post('filtro_codigo', '');
            $status     = $this->post('STATUS', '');

            $params = ['comum_id' => $comumId, 'sucesso' => 'Produto atualizado com sucesso'];
            if ($pagina > 1)    $params['pagina']       = $pagina;
            if ($nome !== '')   $params['nome']         = $nome;
            if ($dep !== '')    $params['dependencia']   = $dep;
            if ($codigo !== '') $params['filtro_codigo'] = $codigo;
            if ($status !== '') $params['status']        = $status;

            $this->redirecionar('/products/view?' . http_build_query($params));
        } catch (\Exception $e) {
            error_log('Erro ao atualizar produto: ' . $e->getMessage());
            $filtros = $this->extrairFiltros();
            $this->renderizar('products/edit', [
                'id_produto'         => $idProduto,
                'produto'            => $produto,
                'comum_id'           => $comumId,
                'tipos_bens'         => $this->obterTiposBens(),
                'dependencias'       => $this->obterDependencias(),
                'pagina'             => $filtros['pagina'],
                'filtro_nome'        => $filtros['filtro_nome'],
                'filtro_dependencia' => $filtros['filtro_dependencia'],
                'filtro_codigo'      => $filtros['filtro_codigo'],
                'filtro_STATUS'      => $filtros['filtro_STATUS'],
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

        $comumId = $this->obterComumIdOuRedirecionar();
        if ($comumId === null) return;

        $produtoId = (int) ($this->post('produto_id', 0));
        if ($produtoId <= 0) {
            $this->jsonErro('Produto inválido', 400);
            return;
        }

        try {
            // Soft delete: marca como inativo
            $this->produtoRepository->atualizar($produtoId, ['ativo' => 0]);
            $this->json(['sucesso' => true, 'mensagem' => 'Produto removido com sucesso']);
        } catch (\Exception $e) {
            error_log('Erro ao deletar produto: ' . $e->getMessage());
            $this->jsonErro('Erro ao remover produto', 500);
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

        $produto = $this->produtoRepository->buscarPorId($idProduto);
        if (!$produto) {
            $this->redirecionar('/products/view?erro=' . urlencode('Produto não encontrado'));
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
            'filtro_STATUS'      => $filtros['filtro_STATUS'],
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
                $this->json(['sucesso' => true, 'mensagem' => 'Observação salva com sucesso']);
            } else {
                // Redirecionar de volta para /products/view com filtros
                $params = ['comum_id' => $comumId, 'sucesso' => urlencode('Observação salva com sucesso')];
                $pagina = $this->post('pagina', '');
                $nome   = $this->post('nome', '');
                $dep    = $this->post('dependencia', '');
                $codigo = $this->post('filtro_codigo', '');
                $status = $this->post('status', '');
                if ($pagina !== '' && (int)$pagina > 1) $params['pagina']       = $pagina;
                if ($nome !== '')   $params['nome']         = $nome;
                if ($dep !== '')    $params['dependencia']   = $dep;
                if ($codigo !== '') $params['filtro_codigo'] = $codigo;
                if ($status !== '') $params['status']        = $status;
                $this->redirecionar('/products/view?' . http_build_query($params));
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
            $this->redirecionar('/products/view');
            return;
        }

        $comumId = SessionManager::getComumId();
        if (!$comumId) {
            $this->redirecionar('/products/view?erro=Comum não selecionada');
            return;
        }

        $produtoId = (int) ($this->post('produto_id', 0));
        $checado = (int) ($this->post('checado', 0));

        if ($produtoId <= 0) {
            $this->redirecionar('/products/view?erro=Produto inválido');
            return;
        }

        try {
            $this->produtoRepository->atualizarChecado($produtoId, $comumId, $checado);
            $this->redirecionar('/products/view?sucesso=Produto atualizado');
        } catch (\Exception $e) {
            error_log('Erro ao atualizar check: ' . $e->getMessage());
            $this->redirecionar('/products/view?erro=Erro ao atualizar produto');
        }
    }

    public function etiqueta(): void
    {
        if ($this->isGet()) {
            $comumId = SessionManager::getComumId();
            $this->renderizar('spreadsheets/copy-labels', [
                'id_planilha' => $this->query('id', $comumId),
                'comum_id'    => $comumId,
                'conexao'     => $this->conexao,
            ]);
            return;
        }

        $comumId = SessionManager::getComumId();
        if (!$comumId) {
            $this->redirecionar('/products/view?erro=Comum não selecionada');
            return;
        }

        $produtoId = (int) ($this->post('produto_id', 0));
        $imprimir = (int) ($this->post('imprimir', 0));

        if ($produtoId <= 0) {
            $this->redirecionar('/products/view?erro=Produto inválido');
            return;
        }

        try {
            $this->produtoRepository->atualizarEtiqueta($produtoId, $comumId, $imprimir);
            $this->redirecionar('/products/view?sucesso=Etiqueta atualizada');
        } catch (\Exception $e) {
            error_log('Erro ao atualizar etiqueta: ' . $e->getMessage());
            $this->redirecionar('/products/view?erro=Erro ao atualizar etiqueta');
        }
    }

    public function assinar(): void
    {
        if (!$this->isPost()) {
            $this->jsonErro('Método não permitido', 405);
            return;
        }

        $this->jsonErro('Funcionalidade em implementação.', 501);
    }

    /**
     * GET /products/clear-edits
     * Limpa os campos editados de um produto e redireciona de volta para a listagem.
     */
    public function clearEdits(): void
    {
        $idProduto = (int) ($this->query('id_PRODUTO', $this->query('id_produto', $this->query('id', 0))));
        $comumId = (int) ($this->query('comum_id', $this->query('id', 0)));

        $pagina = $this->query('pagina', 1);
        $nome = $this->query('nome', '');
        $dependencia = $this->query('dependencia', '');
        $filtro_codigo = $this->query('filtro_codigo', '');
        $status = $this->query('STATUS', $this->query('status', ''));

        $params = ['id' => $comumId, 'comum_id' => $comumId, 'pagina' => $pagina, 'nome' => $nome, 'dependencia' => $dependencia, 'filtro_codigo' => $filtro_codigo, 'status' => $status, 'STATUS' => $status];

        if ($idProduto <= 0 || $comumId <= 0) {
            $params['erro'] = 'Parâmetros inválidos';
            $this->redirecionar('/products/view?' . http_build_query($params));
            return;
        }

        try {
                        $sql = "UPDATE produtos 
                                     SET editado_tipo_bem_id = 0,
                                             editado_bem = '',
                                             editado_complemento = '',
                                             editado_dependencia_id = 0,

                                             imprimir_etiqueta = 0,
                                             checado = 0,
                                             imprimir_14_1 = 0,
                                             condicao_14_1 = '',
                                             nota_numero = NULL,
                                             nota_data = NULL,
                                             nota_valor = NULL,
                                             nota_fornecedor = '',
                                             editado = 0
                                     WHERE id_produto = :id_produto 
                                         AND comum_id = :comum_id";

            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_produto', $idProduto, \PDO::PARAM_INT);
            $stmt->bindValue(':comum_id', $comumId, \PDO::PARAM_INT);
            $stmt->execute();

            $params['sucesso'] = 'Edições limpas com sucesso!';
            $this->redirecionar('/products/view?' . http_build_query($params));
            return;
        } catch (\Exception $e) {
            error_log('Erro clearEdits: ' . $e->getMessage());
            $params['erro'] = 'Erro ao limpar edições: ' . $e->getMessage();
            $this->redirecionar('/products/view?' . http_build_query($params));
            return;
        }
    }
}

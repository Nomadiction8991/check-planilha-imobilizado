<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Core\SessionManager;
use App\Repositories\ComumRepository;
use App\Repositories\DependenciaRepository;
use App\Repositories\ProdutoRepository;
use App\Services\ImportacaoService;
use App\Services\CsvParserService;
use PDO;

class PlanilhaController extends BaseController
{
    private ImportacaoService $importacaoService;
    private CsvParserService $csvParserService;
    private ComumRepository $comumRepository;
    private ProdutoRepository $produtoRepository;
    private DependenciaRepository $dependenciaRepository;

    public function __construct(?PDO $conexao = null)
    {
        $conexao = $conexao ?? ConnectionManager::getConnection();

        $this->importacaoService = new ImportacaoService($conexao);
        $this->csvParserService = new CsvParserService($conexao);
        $this->comumRepository = new ComumRepository($conexao);
        $this->produtoRepository = new ProdutoRepository($conexao);
        $this->dependenciaRepository = new DependenciaRepository($conexao);
    }

    public function importar(): void
    {
        if (!SessionManager::isAuthenticated()) {
            $this->redirecionar('/login');
            return;
        }
        $this->renderizar('spreadsheets/import');
    }

    /**
     * PASSO 1: Upload do CSV → salva arquivo → analisa → redireciona para preview.
     */
    public function processarImportacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/spreadsheets/import');
            return;
        }

        try {
            // Garante limites adequados para processar CSV grande
            set_time_limit(120);
            ini_set('memory_limit', '128M');

            SessionManager::start();
            $comumId = SessionManager::getComumId() ?? 0;
            $usuarioId = SessionManager::getUserId();

            if (!$usuarioId) {
                $this->redirecionar('/login');
                return;
            }

            // comumId é opcional — o CSV pode conter múltiplas igrejas (localidades)
            // que serão detectadas automaticamente durante a análise

            // Valida upload — aceita 'arquivo_csv' e 'arquivo' (compatibilidade)
            $arquivo = null;
            if (isset($_FILES['arquivo_csv']) && $_FILES['arquivo_csv']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['arquivo_csv'];
            } elseif (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['arquivo'];
            }

            if (!$arquivo) {
                throw new \Exception('Erro ao fazer upload do arquivo');
            }

            // Valida tipo
            $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            if (!in_array($extensao, ['csv', 'txt'])) {
                throw new \Exception('Apenas arquivos CSV são permitidos');
            }

            // Move arquivo para storage/importacao
            $dirImportacao = __DIR__ . '/../../storage/importacao';
            if (!is_dir($dirImportacao)) {
                mkdir($dirImportacao, 0777, true);
            }

            $nomeArquivo = 'importacao_' . ($comumId ?: 'multi') . '_' . time() . '.' . $extensao;
            $caminhoDestino = $dirImportacao . '/' . $nomeArquivo;

            if (!move_uploaded_file($arquivo['tmp_name'], $caminhoDestino)) {
                throw new \Exception('Erro ao salvar arquivo');
            }

            // Registra importação no banco (comum_id pode ser NULL para multi-igreja)
            $importacaoId = $this->importacaoService->iniciarImportacao(
                $usuarioId,
                $comumId ?: null,
                $arquivo['name'],
                $caminhoDestino
            );

            // Analisa CSV vs banco de dados (detecta igrejas automaticamente)
            $analise = $this->csvParserService->analisar($caminhoDestino, $comumId);

            // Salva análise em JSON para a tela de preview
            $this->csvParserService->salvarAnalise($importacaoId, $analise);

            // Redireciona para tela de preview (conferência)
            $this->redirecionar('/spreadsheets/preview?id=' . $importacaoId);
        } catch (\Exception $e) {
            error_log('Erro ao processar importação: ' . $e->getMessage());
            $this->setMensagem('Erro: ' . $e->getMessage(), 'danger');
            $this->redirecionar('/spreadsheets/import?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * PASSO 2: Tela de preview — mostra análise com diff e ações por registro.
     */
    public function preview(): void
    {
        $importacaoId = (int) ($_GET['id'] ?? 0);

        if ($importacaoId <= 0) {
            $this->redirecionar('/spreadsheets/import?erro=ID inválido');
            return;
        }

        // Carrega dados da importação
        $importacao = $this->importacaoService->buscarProgresso($importacaoId);
        if (!$importacao) {
            $this->redirecionar('/spreadsheets/import?erro=Importação não encontrada');
            return;
        }

        // Carrega análise salva
        $analise = $this->csvParserService->carregarAnalise($importacaoId);
        if (!$analise) {
            $this->redirecionar('/spreadsheets/import?erro=Análise não encontrada');
            return;
        }

        // Carrega sessão
        $acoesSalvas   = $_SESSION['preview_acoes_' . $importacaoId]   ?? [];
        $igrejasSalvas = $_SESSION['preview_igrejas_' . $importacaoId] ?? [];

        // Status por comune para a tabela de igrejas (novo > atualizar > iguais)
        $statusPorComum = [];
        foreach ($analise['registros'] as $reg) {
            $codigoComum = $reg['dados_csv']['codigo_comum'] ?? '';
            $status      = $reg['status'] ?? '';
            if ($codigoComum === '') continue;
            if (!isset($statusPorComum[$codigoComum])) {
                $statusPorComum[$codigoComum] = 'iguais';
            }
            if ($status === 'novo') {
                $statusPorComum[$codigoComum] = 'novo';
            } elseif ($status === 'atualizar' && $statusPorComum[$codigoComum] !== 'novo') {
                $statusPorComum[$codigoComum] = 'atualizar';
            }
        }

        $this->renderizar('spreadsheets/import-preview', [
            'importacao_id'    => $importacaoId,
            'importacao'       => $importacao,
            'resumo'           => $analise['resumo'],
            'registros'        => [],
            'pagina'           => 1,
            'total_paginas'    => 1,
            'total_registros'  => 0,
            'itens_por_pagina' => 20,
            'acoes_salvas'     => $acoesSalvas,
            'comuns_detectadas'=> $analise['comuns_detectadas'] ?? [],
            'igrejas_salvas'   => $igrejasSalvas,
            'status_por_comum' => $statusPorComum,
        ]);
    }

    /**
     * AJAX: Salva ações selecionadas da página atual na sessão.
     */
    public function salvarAcoesPreview(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['erro' => 'Método não permitido']);
            exit;
        }

        $dados = json_decode(file_get_contents('php://input'), true);
        $importacaoId = (int) ($dados['importacao_id'] ?? 0);
        $acoes = $dados['acoes'] ?? [];
        $igrejas = $dados['igrejas'] ?? [];

        if ($importacaoId <= 0) {
            echo json_encode(['erro' => 'ID inválido']);
            exit;
        }

        // Mescla com ações já salvas na sessão (linhas)
        if (!isset($_SESSION['preview_acoes_' . $importacaoId])) {
            $_SESSION['preview_acoes_' . $importacaoId] = [];
        }

        foreach ($acoes as $linhaCsv => $acao) {
            $_SESSION['preview_acoes_' . $importacaoId][$linhaCsv] = $acao;
        }

        // Salva escolhas por igreja (se houver)
        if (!isset($_SESSION['preview_igrejas_' . $importacaoId])) {
            $_SESSION['preview_igrejas_' . $importacaoId] = [];
        }
        foreach ($igrejas as $codigo => $acaoIgreja) {
            // aceitar apenas valores permitidos
            if (in_array($acaoIgreja, ['', 'importar', 'pular', 'personalizado'], true)) {
                $_SESSION['preview_igrejas_' . $importacaoId][(string)$codigo] = $acaoIgreja;
            }
        }

        // Guarda contadores antes de fechar a sessão
        $totalAcoes   = count($_SESSION['preview_acoes_' . $importacaoId]);
        $totalIgrejas = count($_SESSION['preview_igrejas_' . $importacaoId]);

        // Garante que a sessão é escrita antes da resposta (crucial para reload AJAX)
        session_write_close();

        echo json_encode([
            'sucesso' => true,
            'total_salvas' => $totalAcoes,
            'igrejas_salvas' => $totalIgrejas
        ]);
        exit;
    }

    /**
     * AJAX: Aplica ação em massa a TODOS os registros (todas as páginas).
     */
    public function acaoMassaPreview(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['erro' => 'Método não permitido']);
            exit;
        }

        $dados = json_decode(file_get_contents('php://input'), true);
        $importacaoId = (int) ($dados['importacao_id'] ?? 0);
        $acao = $dados['acao'] ?? '';

        if ($importacaoId <= 0 || !in_array($acao, ['importar', 'pular'])) {
            echo json_encode(['erro' => 'Parâmetros inválidos']);
            exit;
        }

        // Carrega análise para obter todas as linhas
        $analise = $this->csvParserService->carregarAnalise($importacaoId);
        if (!$analise) {
            echo json_encode(['erro' => 'Análise não encontrada']);
            exit;
        }

        // Aplica a ação a todos os registros (exceto erros)
        $acoes = [];
        foreach ($analise['registros'] as $reg) {
            $linhaCsv = (string) ($reg['linha_csv'] ?? '');
            $status = $reg['status'] ?? 'erro';
            if ($linhaCsv !== '' && $status !== 'erro') {
                $acoes[$linhaCsv] = $acao;
            }
        }

        $_SESSION['preview_acoes_' . $importacaoId] = $acoes;

        echo json_encode([
            'sucesso' => true,
            'acao' => $acao,
            'total_aplicadas' => count($acoes)
        ]);
        exit;
    }

    /**
     * PASSO 3: Confirma importação — recebe ações do usuário e processa diretamente.
     */
    public function confirmarImportacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/spreadsheets/import');
            return;
        }

        $importacaoId = (int) ($_POST['importacao_id'] ?? 0);

        if ($importacaoId <= 0) {
            $this->redirecionar('/spreadsheets/import?erro=ID inválido');
            return;
        }

        try {
            set_time_limit(120);
            ini_set('memory_limit', '128M');

            // Mescla ações salvas via AJAX (páginas anteriores) com ações do formulário (página atual)
            $acoesSalvas = $_SESSION['preview_acoes_' . $importacaoId] ?? [];
            $acoesFormulario = $_POST['acao'] ?? [];
            $acoes = array_merge($acoesSalvas, $acoesFormulario);

            // Flag de importação total (ignora seleções manuais)
            $importarTudo = !empty($_POST['importar_tudo']);

            // Para registros sem ação definida, carrega a ação sugerida da análise
            $analise = $this->csvParserService->carregarAnalise($importacaoId);
            if ($analise) {
                if ($importarTudo) {
                    // Sobrescreve TUDO: importar exceto status=excluir → excluir
                    $acoes = [];
                    foreach ($analise['registros'] as $reg) {
                        $linhaCsv = (string) ($reg['linha_csv'] ?? '');
                        if ($linhaCsv === '') continue;
                        $status = $reg['status'] ?? '';
                        $acoes[$linhaCsv] = ($status === CsvParserService::STATUS_EXCLUIR) ? 'excluir' : 'importar';
                    }
                } else {
                    foreach ($analise['registros'] as $reg) {
                        $linhaCsv = (string) ($reg['linha_csv'] ?? '');
                        if ($linhaCsv !== '' && !isset($acoes[$linhaCsv])) {
                            $acoes[$linhaCsv] = $reg['acao_sugerida'] ?? 'pular';
                        }
                    }
                }
            }

            // Aplicar escolhas por igreja (POST tem precedência sobre sessão)
            $igrejasFormulario = $_POST['igrejas'] ?? [];
            $igrejasSessao = $_SESSION['preview_igrejas_' . $importacaoId] ?? [];
            $igrejasEscolhas = array_merge($igrejasSessao, $igrejasFormulario);

            if ($analise && !empty($igrejasEscolhas) && !$importarTudo) {
                foreach ($analise['registros'] as $reg) {
                    $linhaCsv = (string) ($reg['linha_csv'] ?? '');
                    $codigoComum = $reg['dados_csv']['codigo_comum'] ?? '';
                    if ($codigoComum !== '' && isset($igrejasEscolhas[$codigoComum])) {
                        $acaoIgreja = $igrejasEscolhas[$codigoComum];
                        if (in_array($acaoIgreja, ['importar', 'pular'], true) && $linhaCsv !== '') {
                            $acoes[$linhaCsv] = $acaoIgreja;
                        }
                    }
                }
            }

            // Processar importação diretamente
            if ($analise) {
                $resultado = $this->importacaoService->processarComAcoes($importacaoId, $acoes, $analise);
            } else {
                $resultado = $this->importacaoService->processar($importacaoId);
            }

            // Limpa dados temporários
            unset(
                $_SESSION['preview_acoes_' . $importacaoId],
                $_SESSION['importacao_acoes_' . $importacaoId]
            );
            $this->csvParserService->limparAnalise($importacaoId);

            $sucesso = $resultado['sucesso'] ?? 0;
            $erros = $resultado['erro'] ?? 0;
            $msg = "{$sucesso} linha(s) importada(s) com sucesso.";
            if ($erros > 0) {
                $msg .= " {$erros} linha(s) com erro.";
            }

            $this->setMensagem($msg, $erros > 0 ? 'warning' : 'success');
            $this->redirecionar('/products/view');
        } catch (\Exception $e) {
            error_log('Erro ao confirmar importação: ' . $e->getMessage());
            $this->setMensagem('Erro ao importar: ' . $e->getMessage(), 'danger');
            $this->redirecionar('/spreadsheets/import');
        }
    }

    public function visualizar(): void
    {
        // Se veio ?comum_id= pela URL (clique na listagem), atualiza a sessão
        $comumIdUrl = (int) ($this->query('comum_id', 0));
        if ($comumIdUrl > 0) {
            SessionManager::set('comum_id', $comumIdUrl);
        }

        $comumId = SessionManager::getComumId();

        if (!$comumId || $comumId <= 0) {
            $this->redirecionar('/churches?erro=Nenhuma comum disponível');
            return;
        }

        // Buscar dados da comum
        $planilha = $this->comumRepository->buscarPorId($comumId);

        if (!$planilha) {
            $this->redirecionar('/churches?erro=Comum não encontrada');
            return;
        }

        $planilha['comum_descricao'] = $planilha['descricao'] ?? 'Comum';

        // Filtros
        $paginaAtual = max(1, (int) ($this->query('pagina', 1)));
        $itensPorPagina = 50;

        $filtros = [
            'nome'        => $this->query('nome', ''),
            'dependencia' => $this->query('dependencia', ''),
            'status'      => $this->query('status', ''),
            'codigo'      => $this->query('filtro_codigo', ''),
        ];

        // Buscar produtos via repository
        $resultado = $this->produtoRepository->buscarParaPlanilha($comumId, $paginaAtual, $itensPorPagina, $filtros);

        // Buscar dependências para o filtro
        $dependencias = $this->dependenciaRepository->buscarTodos();

        $this->renderizar('spreadsheets/view', [
            'comum_id'            => $comumId,
            'planilha'            => $planilha,
            'produtos'            => $resultado['dados'],
            'total_registros'     => $resultado['total'],
            'pagina'              => $paginaAtual,
            'total_paginas'       => $resultado['totalPaginas'],
            'dependencia_options' => $dependencias,
            'filtro_nome'         => $filtros['nome'],
            'filtro_dependencia'  => $filtros['dependencia'],
            'filtro_status'       => $filtros['status'],
            'filtro_codigo'       => $filtros['codigo'],
        ]);
    }
}

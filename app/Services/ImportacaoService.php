<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ImportacaoRepository;
use App\ValueObjects\ProcessingResult;
use App\Core\ConnectionManager;
use Illuminate\Support\Facades\Schema;
use PDO;
use Exception;

/**
 * ImportacaoService — Serviço de importação modernizado.
 *
 * Novo fluxo:
 *  1. Upload → iniciarImportacao() registra no banco
 *  2. Análise → CsvParserService::analisar() compara CSV vs DB
 *  3. Preview → Usuário vê diff e escolhe ações por registro
 *  4. Confirmar → processarComAcoes() executa apenas o que o usuário selecionou
 *
 * Mantém:
 *  - Processamento em lotes com transação
 *  - Atualização de progresso em tempo real
 *  - buscarOuCriarTipoBem / buscarOuCriarDependencia
 *  - Lógica de campos editado_* para novos produtos
 *  - descricao_velha
 */
class ImportacaoService
{
    private ImportacaoRepository $importacaoRepo;
    private PDO $conexao;
    private const LOTE_SIZE = 100;

    // Caches em memória para evitar N+1 queries
    private array $cacheTiposBens = [];
    private array $cacheDependencias = []; // [comum_id => [descricao_upper => id]]
    private array $cacheComuns = []; // [codigo => id]

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
        $this->importacaoRepo = new ImportacaoRepository($this->conexao);
    }

    /**
     * Retorna diretórios base autorizados para arquivos de importação.
     *
     * @return string[]
     */
    private function getDiretoriosImportacaoPermitidos(): array
    {
        $candidatos = [
            __DIR__ . '/../../storage/importacao',
            rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/check-planilha-imobilizado/importacao',
        ];

        $bases = [];
        foreach ($candidatos as $candidato) {
            $real = realpath($candidato);
            if ($real !== false) {
                $bases[] = rtrim($real, DIRECTORY_SEPARATOR);
            }
        }

        return $bases;
    }

    /**
     * Valida se caminho está em um diretório de importação permitido.
     */
    private function validarCaminhoImportacao(string $caminho): string
    {
        $caminhoReal = realpath($caminho);
        if ($caminhoReal === false) {
            throw new Exception('Arquivo de importação não encontrado');
        }

        $basesPermitidas = $this->getDiretoriosImportacaoPermitidos();
        if (empty($basesPermitidas)) {
            throw new Exception('Diretório de importação não está disponível');
        }

        $caminhoNormalizado = rtrim($caminhoReal, DIRECTORY_SEPARATOR);
        foreach ($basesPermitidas as $base) {
            if ($caminhoNormalizado === $base || str_starts_with($caminhoNormalizado, $base . DIRECTORY_SEPARATOR)) {
                return $caminhoReal;
            }
        }

        throw new Exception('Acesso a arquivo não permitido');
    }

    /**
     * PASSO 1: Registra a importação no banco de dados.
     * O comumId pode ser NULL para importações multi-igreja (detectadas pelo CSV).
     */
    public function iniciarImportacao(
        int $usuarioId,
        ?int $comumId,
        ?int $administracaoId,
        string $arquivoNome,
        string $arquivoCaminho,
    ): int
    {
        $totalLinhas = $this->contarLinhasArquivo($arquivoCaminho);

        $dados = [
            'usuario_id' => $usuarioId,
            'comum_id' => $comumId,
            'administracao_id' => $administracaoId,
            'arquivo_nome' => $arquivoNome,
            'arquivo_caminho' => $arquivoCaminho,
            'total_linhas' => $totalLinhas,
            'status' => 'aguardando'
        ];

        return $this->importacaoRepo->criar($dados);
    }

    /**
     * PASSO 4: Processa APENAS os registros selecionados pelo usuário.
     *
     * @param int   $importacaoId ID da importação
     * @param array $acoes        Mapa: [linha_csv => acao] onde acao = importar|pular|excluir
     * @param array $analise      Dados da análise (do CsvParserService)
     * @return array Resultado do processamento
     */
    public function processarComAcoes(int $importacaoId, array $acoes, array $analise): array
    {
        $importacao = $this->importacaoRepo->buscarPorId($importacaoId);

        if (!$importacao) {
            throw new Exception('Importação não encontrada');
        }

        // Garantir que o processo não seja interrompido por timeout
        @set_time_limit(0);
        @ignore_user_abort(true);

        error_log("Iniciando processamento da importação #{$importacaoId} com " . count($acoes) . " ações.");

        // Limpar erros anterior desta importação para registrar apenas os novos
        $stmtDeleteErros = $this->conexao->prepare('DELETE FROM import_erros WHERE importacao_id = :id');
        $stmtDeleteErros->execute([':id' => $importacaoId]);

        $this->importacaoRepo->atualizar($importacaoId, [
            'status' => 'processando',
            'iniciada_em' => date('Y-m-d H:i:s')
        ]);

        $administracaoId = (int) ($importacao['administracao_id'] ?? 0);

        // Pre-load caches para evitar N+1
        $this->preCarregarTiposBens($administracaoId > 0 ? $administracaoId : null);
        $this->preCarregarComuns();

        $resultado = ProcessingResult::criar();

        // comumId da importação é fallback para linhas sem localidade
        // certificar que esteja em formato inteiro, pois vindo do banco pode ser string
        $comumIdFallback = (int) ($importacao['comum_id'] ?? 0);

        $registros = $analise['registros'] ?? [];

        // Filtra apenas os que têm ação definida
        $registrosParaProcessar = [];
        foreach ($registros as $registro) {
            $linhaCsv = $registro['linha_csv'];
            $acao = $acoes[$linhaCsv] ?? CsvParserService::ACAO_PULAR;

            if ($acao === CsvParserService::ACAO_PULAR) {
                $resultado->adicionarPulados();
                continue;
            }

            $registrosParaProcessar[] = [
                'registro' => $registro,
                'acao' => $acao,
            ];
        }

        // Recalcula total para barra de progresso
        $totalParaProcessar = count($registrosParaProcessar);
        $this->importacaoRepo->atualizar($importacaoId, [
            'total_linhas' => $totalParaProcessar
        ]);

        try {
            $lote = [];
            $processados = 0;

            foreach ($registrosParaProcessar as $item) {
                $lote[] = $item;

                if (count($lote) >= self::LOTE_SIZE) {
                    $resultadoLote = $this->processarLoteComAcoes($lote, $comumIdFallback, $importacaoId);
                    $resultado->mesclar($resultadoLote);

                    $processados += count($lote);
                    $porcentagem = $totalParaProcessar > 0
                        ? ($processados / $totalParaProcessar) * 100
                        : 100;

                    $this->importacaoRepo->atualizar($importacaoId, [
                        'linhas_processadas' => $processados,
                        'linhas_sucesso' => $resultado->getSucesso(),
                        'linhas_erro' => $resultado->getErro(),
                        'porcentagem' => round($porcentagem, 2)
                    ]);

                    $lote = [];
                    gc_collect_cycles();
                }
            }

            // Lote restante
            if (!empty($lote)) {
                $resultadoLote = $this->processarLoteComAcoes($lote, $comumIdFallback, $importacaoId);
                $resultado->mesclar($resultadoLote);
                $processados += count($lote);
            }

            $this->importacaoRepo->atualizar($importacaoId, [
                'linhas_processadas' => $processados,
                'linhas_sucesso' => $resultado->getSucesso(),
                'linhas_erro' => $resultado->getErro(),
                'porcentagem' => 100,
                'status' => 'concluida',
                'concluida_em' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("ERRO FATAL na importação #{$importacaoId}: " . $e->getMessage());
            $this->importacaoRepo->atualizar($importacaoId, [
                'status' => 'erro',
                'mensagem_erro' => $e->getMessage(),
                'concluida_em' => date('Y-m-d H:i:s')
            ]);
            throw $e;
        }

        error_log("Importação #{$importacaoId} concluída. Sucesso: " . $resultado->getSucesso() . ", Erros: " . $resultado->getErro());

        return $resultado->toArray();
    }

    /**
     * Processa um lote de registros com ações definidas pelo usuário.
     * Cada registro resolve sua própria comum via localidade do CSV.
     */
    // O tipo de $comumIdFallback é declarado como int para evitar o erro de
    // TypeError observado em ambientes de produção quando a coluna retornava
    // valor string. A chamada acima garante conversão.
    private function processarLoteComAcoes(array $lote, int $comumIdFallback, int $importacaoId = 0): ProcessingResult
    {
        $resultado = ProcessingResult::criar();
        $importacao = $importacaoId > 0 ? $this->importacaoRepo->buscarPorId($importacaoId) : null;
        $administracaoId = (int) ($importacao['administracao_id'] ?? 0);

        $this->conexao->beginTransaction();

        try {
            foreach ($lote as $item) {
                $registro = $item['registro'];
                $acao = $item['acao'];

                try {
                    if ($acao === CsvParserService::ACAO_EXCLUIR) {
                        if (!empty($registro['id_produto'])) {
                            $this->desativarProduto($registro['id_produto']);
                            $resultado->adicionarExcluidos();
                        }
                        $resultado->adicionarSucesso();
                    } elseif ($acao === CsvParserService::ACAO_IMPORTAR) {
                        $this->processarRegistro($registro, $comumIdFallback, $administracaoId > 0 ? $administracaoId : null);
                        $resultado->adicionarSucesso();
                    }
                } catch (Exception $e) {
                    $resultado->adicionarErro();
                    $resultado->adicionarErroMsg((int) ($registro['linha_csv'] ?? 0), $e->getMessage());
                    // Persiste o erro na tabela import_erros
                    if ($importacaoId > 0) {
                        $this->salvarErroImportacao($importacaoId, $registro, $e->getMessage());
                    }
                }
            }

            $this->conexao->commit();
        } catch (Exception $e) {
            $this->conexao->rollBack();
            throw $e;
        }

        return $resultado;
    }

    /**
     * Persiste um registro de erro na tabela import_erros.
     */
    private function salvarErroImportacao(int $importacaoId, array $registro, string $mensagem): void
    {
        try {
            $dadosCsv = $registro['dados_csv'] ?? [];
            $stmt = $this->conexao->prepare(
                "INSERT INTO import_erros
                    (importacao_id, linha_csv, codigo, localidade, codigo_comum,
                     descricao_csv, bem, complemento, dependencia, mensagem_erro)
                 VALUES
                    (:importacao_id, :linha_csv, :codigo, :localidade, :codigo_comum,
                     :descricao_csv, :bem, :complemento, :dependencia, :mensagem_erro)"
            );
            $stmt->execute([
                ':importacao_id' => $importacaoId,
                ':linha_csv'     => (string) ($registro['linha_csv'] ?? ''),
                ':codigo'        => $dadosCsv['codigo'] ?? '',
                ':localidade'    => $dadosCsv['localidade'] ?? '',
                ':codigo_comum'  => $dadosCsv['codigo_comum'] ?? '',
                ':descricao_csv' => $dadosCsv['nome_original'] ?? trim(($dadosCsv['bem'] ?? '') . ' ' . ($dadosCsv['complemento'] ?? '')),
                ':bem'           => $dadosCsv['bem'] ?? '',
                ':complemento'   => $dadosCsv['complemento'] ?? '',
                ':dependencia'   => $dadosCsv['dependencia_descricao'] ?? '',
                ':mensagem_erro' => $mensagem,
            ]);
        } catch (Exception $ex) {
            error_log('Falha ao salvar erro de importação: ' . $ex->getMessage());
        }
    }

    /**
     * Processa um registro individual (NOVO ou ATUALIZAR).
     * Resolve a comum automaticamente pela localidade do CSV.
     * Se a comum não existir, cria automaticamente.
     */
    private function processarRegistro(array $registro, int $comumIdFallback, ?int $administracaoId = null): void
    {
        $dadosCsv = $registro['dados_csv'];

        $codigo = $dadosCsv['codigo'] ?? '';
        $tipoBemCodigo = $dadosCsv['tipo_bem_codigo'] ?? '';
        $bem = $dadosCsv['bem'] ?? '';
        $complemento = $dadosCsv['complemento'] ?? '';
        $dependenciaDescricao = $dadosCsv['dependencia_descricao'] ?? '';

        // ── Resolver comumId pela localidade do CSV ──
        $codigoComum = $dadosCsv['codigo_comum'] ?? '';
        if (!empty($codigoComum)) {
            $comumId = $this->buscarOuCriarComum($codigoComum, $administracaoId);
        } else {
            $comumId = $comumIdFallback;
        }

        // ── Fallback: extrair código da comum do próprio código do produto ──
        // Formato: "09-0565 / 001495" → código da comum = "09-0565"
        // Ocorre quando a coluna de localidade vem vazia no CSV.
        if ($comumId <= 0 && !empty($codigo)) {
            $codigoComumDoCodigo = $this->extrairCodigoComumDoCodigo($codigo);
            if (!empty($codigoComumDoCodigo)) {
                $comumId = $this->buscarOuCriarComum($codigoComumDoCodigo, $administracaoId);
            }
        }

        if ($comumId <= 0) {
            throw new Exception('Não foi possível determinar a igreja (comum) para o produto: ' . $codigo);
        }

        // Usa o código do tipo_bem extraído do prefixo numérico; se não houver,
        // usa 99 (DIVERSOS) para não quebrar a constraint NOT NULL de tipo_bem_id.
        $tipoBemCodigoFinal = !empty($tipoBemCodigo) ? $tipoBemCodigo : '99';
        $tipoBemId = $this->cacheTiposBens[$tipoBemCodigoFinal] ?? $this->buscarOuCriarTipoBem($tipoBemCodigoFinal, $administracaoId);

        $dependenciaId = $this->buscarOuCriarDependenciaCached($dependenciaDescricao, $comumId);

        if ($registro['status'] === CsvParserService::STATUS_ATUALIZAR && !empty($registro['id_produto'])) {
            // id_produto pode ser string vindo do CSV/DB; converter para int
            $this->atualizarProduto((int) $registro['id_produto'], [
                'tipo_bem_id'    => $tipoBemId,
                'bem'            => $bem,
                'complemento'    => $complemento,
                'dependencia_id' => $dependenciaId,
                'importado'      => 1,
                'ativo'          => 1, // reativa produto caso estivesse inativo
            ]);
        } else {
            // Guarda de segurança: verificar em tempo real se já existe produto com
            // este código antes de inserir. Protege contra:
            //  1. Linhas duplicadas no mesmo CSV
            //  2. Cache de análise desatualizado entre o preview e o processamento
            //  3. Produto desativado que não appareceu na análise
            $existente = $this->buscarProdutoPorCodigo($codigo, $comumId);

            if ($existente) {
                // Produto já existe (ativo ou não) → atualizar em vez de duplicar
                $this->atualizarProduto((int) $existente['id_produto'], [
                    'tipo_bem_id'    => $tipoBemId,
                    'bem'            => $bem,
                    'complemento'    => $complemento,
                    'dependencia_id' => $dependenciaId,
                    'importado'      => 1,
                    'ativo'          => 1, // reativa se estava inativo
                ]);
            } else {
                $this->criarProduto([
                    'comum_id'          => $comumId,
                    'codigo'            => $codigo,
                    'tipo_bem_id'       => $tipoBemId,
                    'bem'               => $bem,
                    'complemento'       => $complemento,
                    'dependencia_id'    => $dependenciaId,
                    'novo'              => 0,
                    'importado'         => 1,
                    'checado'           => 0,
                    'editado'           => 0,
                    'imprimir_etiqueta' => 0,
                    'imprimir_14_1'     => 0,
                    'observacao'        => '',
                    'ativo'             => 1,
                ]);
            }
        }
    }

    private function preCarregarTiposBens(?int $administracaoId = null): void
    {
        $supportsAdministrationScope = $this->supportsAdministrationScope();

        if ($supportsAdministrationScope && $administracaoId !== null && $administracaoId > 0) {
            $stmt = $this->conexao->prepare(
                "SELECT id, codigo
                 FROM tipos_bens
                 WHERE administracao_id = :administracao_id OR administracao_id IS NULL
                 ORDER BY administracao_id IS NULL ASC, id ASC"
            );
            $stmt->execute([':administracao_id' => $administracaoId]);
        } else {
            $stmt = $this->conexao->query("SELECT id, codigo FROM tipos_bens ORDER BY id ASC");
        }
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $codigo = (string) $row['codigo'];
            if (!isset($this->cacheTiposBens[$codigo])) {
                $this->cacheTiposBens[$codigo] = (int) $row['id'];
            }
        }
    }

    private function preCarregarComuns(): void
    {
        $stmt = $this->conexao->query("SELECT id, codigo FROM comums");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->cacheComuns[strtoupper(trim((string) $row['codigo']))] = (int) $row['id'];
        }
    }

    private function buscarOuCriarDependenciaCached(string $descricao, int $comumId): int
    {
        $descricao = trim(strtoupper($descricao));
        if (empty($descricao)) {
            $descricao = 'SEM DEPENDÊNCIA';
        }

        // Se cache da comum não existe, carrega
        if (!isset($this->cacheDependencias[$comumId])) {
            $this->cacheDependencias[$comumId] = [];
            $stmt = $this->conexao->prepare("SELECT id, descricao FROM dependencias WHERE comum_id = :comum_id");
            $stmt->execute([':comum_id' => $comumId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->cacheDependencias[$comumId][strtoupper(trim((string) $row['descricao']))] = (int) $row['id'];
            }
        }

        if (isset($this->cacheDependencias[$comumId][$descricao])) {
            return $this->cacheDependencias[$comumId][$descricao];
        }

        $id = $this->buscarOuCriarDependencia($descricao, $comumId);
        $this->cacheDependencias[$comumId][$descricao] = $id;

        return $id;
    }

    /**
     * Desativa um produto (soft delete — seta ativo = 0).
     */
    private function desativarProduto(int $idProduto): void
    {
        $stmt = $this->conexao->prepare("UPDATE produtos SET ativo = 0 WHERE id_produto = :id");
        $stmt->execute([':id' => $idProduto]);
    }

    // ─── Métodos auxiliares (preservados da lógica original) ───

    private function buscarOuCriarTipoBem(string $codigo, ?int $administracaoId = null): int
    {
        $supportsAdministrationScope = $this->supportsAdministrationScope();

        if ($supportsAdministrationScope && $administracaoId !== null && $administracaoId > 0) {
            $stmt = $this->conexao->prepare(
                "SELECT id
                 FROM tipos_bens
                 WHERE codigo = :codigo
                   AND (administracao_id = :administracao_id OR administracao_id IS NULL)
                 ORDER BY administracao_id IS NULL ASC
                 LIMIT 1"
            );
            $stmt->execute([
                ':codigo' => $codigo,
                ':administracao_id' => $administracaoId,
            ]);
        } else {
            $stmt = $this->conexao->prepare("SELECT id FROM tipos_bens WHERE codigo = :codigo LIMIT 1");
            $stmt->execute([':codigo' => $codigo]);
        }
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            return (int) $resultado['id'];
        }

        if ($supportsAdministrationScope) {
            $stmt = $this->conexao->prepare(
                "INSERT INTO tipos_bens (codigo, descricao, administracao_id)
                 VALUES (:codigo, :descricao, :administracao_id)"
            );
            $stmt->execute([
                ':codigo' => $codigo,
                ':descricao' => 'Tipo ' . $codigo,
                ':administracao_id' => $administracaoId,
            ]);
        } else {
            $stmt = $this->conexao->prepare(
                "INSERT INTO tipos_bens (codigo, descricao)
                 VALUES (:codigo, :descricao)"
            );
            $stmt->execute([
                ':codigo' => $codigo,
                ':descricao' => 'Tipo ' . $codigo,
            ]);
        }

        return (int) $this->conexao->lastInsertId();
    }

    /**
     * Extrai o código da comum a partir do código do produto.
     *
     * Formato esperado: "09-0565 / 001495"
     * Retorna "09-0565" (tudo antes do " / ").
     * Funciona também sem espaços: "09-0565/001495" → "09-0565".
     */
    private function extrairCodigoComumDoCodigo(string $codigo): string
    {
        // Divide pelo separador "/" com ou sem espaços ao redor
        if (strpos($codigo, '/') !== false) {
            $partes = explode('/', $codigo, 2);
            return trim($partes[0]);
        }

        return '';
    }

    /**
     * Busca ou cria uma comum pelo código extraído da localidade.
     * Código extraído: "BR 09-0038" → "09-0038"
     */
    private function buscarOuCriarComum(string $codigoComum, ?int $administracaoId = null): int
    {
        if (empty($codigoComum)) {
            throw new Exception('Código da comum vazio — não é possível identificar a comum');
        }

        $codigoUpper = strtoupper(trim($codigoComum));

        if (isset($this->cacheComuns[$codigoUpper])) {
            $comumId = $this->cacheComuns[$codigoUpper];
            
            // Garantir administração (lógica original)
            if ($administracaoId !== null) {
                $stmt = $this->conexao->prepare("SELECT administracao_id FROM comums WHERE id = :id");
                $stmt->execute([':id' => $comumId]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($res && (int) ($res['administracao_id'] ?? 0) !== $administracaoId) {
                    $stmtUpdate = $this->conexao->prepare("UPDATE comums SET administracao_id = :adm WHERE id = :id");
                    $stmtUpdate->execute([':adm' => $administracaoId, ':id' => $comumId]);
                }
            }
            
            return $comumId;
        }

        // Buscar comum pelo código (fallback caso não esteja no cache inicial)
        $stmt = $this->conexao->prepare("SELECT id, administracao_id FROM comums WHERE codigo = :codigo LIMIT 1");
        $stmt->execute([':codigo' => $codigoComum]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            if ($administracaoId !== null && (int) ($resultado['administracao_id'] ?? 0) !== $administracaoId) {
                $stmtUpdate = $this->conexao->prepare(
                    "UPDATE comums SET administracao_id = :administracao_id WHERE id = :id"
                );
                $stmtUpdate->execute([
                    ':administracao_id' => $administracaoId,
                    ':id' => (int) $resultado['id'],
                ]);
            }

            return (int) $resultado['id'];
        }

        // Comum não encontrada → criar automaticamente
        $descricaoComum = 'Comum ' . $codigoComum;

        $stmt = $this->conexao->prepare(
            "INSERT INTO comums (codigo, descricao, administracao_id) VALUES (:codigo, :descricao, :administracao_id)"
        );
        $stmt->execute([
            ':codigo' => $codigoComum,
            ':descricao' => $descricaoComum,
            ':administracao_id' => $administracaoId,
        ]);

        return (int) $this->conexao->lastInsertId();
    }

    private function buscarOuCriarDependencia(string $descricao, int $comumId): int
    {
        $descricao = trim(strtoupper($descricao));

        if (empty($descricao)) {
            $descricao = 'SEM DEPENDÊNCIA';
        }

        $stmt = $this->conexao->prepare("SELECT id FROM dependencias WHERE descricao = :descricao AND comum_id = :comum_id");
        $stmt->execute([
            ':descricao' => $descricao,
            ':comum_id' => $comumId
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            return (int) $resultado['id'];
        }

        $stmt = $this->conexao->prepare("INSERT INTO dependencias (comum_id, descricao) VALUES (:comum_id, :descricao)");
        $stmt->execute([
            ':comum_id' => $comumId,
            ':descricao' => $descricao
        ]);

        return (int) $this->conexao->lastInsertId();
    }

    private function supportsAdministrationScope(): bool
    {
        return Schema::hasColumn('tipos_bens', 'administracao_id');
    }

    /**
     * Busca um produto pelo código (case-insensitive).
     * Retorna o registro completo ou NULL se não encontrado.
     * Inclui produtos inativos para evitar duplicatas durante a importação.
     */
    private function buscarProdutoPorCodigo(string $codigo, int $comumId): ?array
    {
        $stmt = $this->conexao->prepare(
            "SELECT id_produto, ativo
               FROM produtos
              WHERE UPPER(codigo) = UPPER(:codigo)
                AND comum_id = :comum_id
              LIMIT 1"
        );
        $stmt->execute([
            ':codigo' => $codigo,
            ':comum_id' => $comumId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function atualizarProduto(int $id, array $dados): void
    {
        $sets = [];
        $params = [':id' => $id];

        foreach ($dados as $campo => $valor) {
            $sets[] = "$campo = :$campo";
            $params[":$campo"] = $valor;
        }

        $sql = "UPDATE produtos SET " . implode(', ', $sets) . " WHERE id_produto = :id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute($params);
    }

    private function criarProduto(array $dados): int
    {
        $campos = array_keys($dados);
        $placeholders = array_map(fn($c) => ":$c", $campos);

        $sql = "INSERT INTO produtos (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->conexao->prepare($sql);

        foreach ($dados as $campo => $valor) {
            $stmt->bindValue(":$campo", $valor);
        }

        $stmt->execute();
        return (int) $this->conexao->lastInsertId();
    }

    /**
     * Conta linhas do arquivo com validação de segurança.
     * VALIDAÇÃO: caminho deve estar dentro de storage/importacao
     */
    private function contarLinhasArquivo(string $caminho): int
    {
        // Validar Path Traversal: caminho deve estar em diretório de importação permitido
        $caminhoReal = $this->validarCaminhoImportacao($caminho);

        if (!is_readable($caminhoReal)) {
            throw new Exception('Arquivo não legível');
        }

        $arquivo = fopen($caminhoReal, 'r');
        if (!$arquivo) {
            throw new Exception('Não foi possível abrir o arquivo');
        }

        $linhas = 0;
        fgets($arquivo); // Pular primeira linha

        while (!feof($arquivo)) {
            if (fgets($arquivo)) {
                $linhas++;
            }
        }

        fclose($arquivo);
        return $linhas;
    }

    // ─── Métodos de consulta ───

    public function buscarProgresso(int $importacaoId): ?array
    {
        return $this->importacaoRepo->buscarPorId($importacaoId);
    }

    public function limparArquivo(int $importacaoId): void
    {
        $importacao = $this->importacaoRepo->buscarPorId($importacaoId);

        if ($importacao && file_exists($importacao['arquivo_caminho'])) {
            unlink($importacao['arquivo_caminho']);
        }
    }


    // ─── Método de compatibilidade (processar sem preview) ───

    /**
     * Processa todas as linhas do CSV diretamente (sem preview).
     * Mantido para compatibilidade, mas o fluxo recomendado é:
     *   CsvParserService::analisar() → preview → processarComAcoes()
     */
    public function processar(int $importacaoId): array
    {
        $importacao = $this->importacaoRepo->buscarPorId($importacaoId);

        if (!$importacao) {
            throw new Exception('Importação não encontrada');
        }

        if (!file_exists($importacao['arquivo_caminho'])) {
            throw new Exception('Arquivo não encontrado');
        }

        // Limpar erros anteriores desta importação para registrar apenas os novos
        $stmtDeleteErros = $this->conexao->prepare('DELETE FROM import_erros WHERE importacao_id = :id');
        $stmtDeleteErros->execute([':id' => $importacaoId]);

        $this->importacaoRepo->atualizar($importacaoId, [
            'status' => 'processando',
            'iniciada_em' => date('Y-m-d H:i:s')
        ]);

        $resultado = ProcessingResult::criar();

        try {
            $arquivo = fopen($importacao['arquivo_caminho'], 'r');

            if (!$arquivo) {
                throw new Exception('Não foi possível abrir o arquivo');
            }

            $cabecalho = fgetcsv($arquivo, 0, ',');

            $linhaAtual = 0;
            $lote = [];
            $totalLinhas = $importacao['total_linhas'];

            while (($linha = fgetcsv($arquivo, 0, ',')) !== false) {
                $linhaAtual++;

                $lote[] = [
                    'numero' => $linhaAtual,
                    'dados' => $linha
                ];

                if (count($lote) >= self::LOTE_SIZE) {
                    $resultadoLote = $this->processarLoteLegado($lote, $cabecalho, $importacao['comum_id']);
                    $resultado->mesclar($resultadoLote);

                    $porcentagem = ($linhaAtual / $totalLinhas) * 100;
                    $this->importacaoRepo->atualizar($importacaoId, [
                        'linhas_processadas' => $linhaAtual,
                        'linhas_sucesso' => $resultado->getSucesso(),
                        'linhas_erro' => $resultado->getErro(),
                        'porcentagem' => round($porcentagem, 2)
                    ]);

                    $lote = [];
                    gc_collect_cycles();
                }
            }

            if (!empty($lote)) {
                $resultadoLote = $this->processarLoteLegado($lote, $cabecalho, $importacao['comum_id']);
                $resultado->mesclar($resultadoLote);

                $this->importacaoRepo->atualizar($importacaoId, [
                    'linhas_processadas' => $linhaAtual,
                    'linhas_sucesso' => $resultado->getSucesso(),
                    'linhas_erro' => $resultado->getErro(),
                    'porcentagem' => 100
                ]);
            }

            fclose($arquivo);

            $this->importacaoRepo->atualizar($importacaoId, [
                'status' => 'concluida',
                'concluida_em' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $this->importacaoRepo->atualizar($importacaoId, [
                'status' => 'erro',
                'mensagem_erro' => $e->getMessage(),
                'concluida_em' => date('Y-m-d H:i:s')
            ]);
            throw $e;
        }

        return $resultado->toArray();
    }

    private function processarLoteLegado(array $lote, array $cabecalho, int $comumId): ProcessingResult
    {
        $resultado = ProcessingResult::criar();

        $this->conexao->beginTransaction();

        try {
            foreach ($lote as $item) {
                try {
                    $this->processarLinhaLegada($item['dados'], $cabecalho, $comumId);
                    $resultado->adicionarSucesso();
                } catch (Exception $e) {
                    $resultado->adicionarErro();
                    $resultado->adicionarErroMsg($item['numero'], $e->getMessage());
                }
            }
            $this->conexao->commit();
        } catch (Exception $e) {
            $this->conexao->rollBack();
            throw $e;
        }

        return $resultado;
    }

    private function processarLinhaLegada(array $dados, array $cabecalho, int $comumId): void
    {
        $mapa = array_flip($cabecalho);

        $codigo = $dados[$mapa['codigo']] ?? '';
        $tipoBemCodigo = $dados[$mapa['tipo_bem']] ?? '';
        $bem = $dados[$mapa['bem']] ?? '';
        $complemento = $dados[$mapa['complemento']] ?? '';
        $dependenciaDescricao = $dados[$mapa['dependencia']] ?? '';

        $tipoBemId = $this->buscarOuCriarTipoBem($tipoBemCodigo, $administracaoId);
        $dependenciaId = $this->buscarOuCriarDependencia($dependenciaDescricao, $comumId);

        $stmt = $this->conexao->prepare("SELECT * FROM produtos WHERE codigo = :codigo AND comum_id = :comum_id");
        $stmt->execute([':codigo' => $codigo, ':comum_id' => $comumId]);
        $produtoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produtoExistente) {
            $this->atualizarProduto($produtoExistente['id_produto'], [
                'tipo_bem_id'    => $tipoBemId,
                'bem'            => $bem,
                'complemento'    => $complemento,
                'dependencia_id' => $dependenciaId,
                'importado'      => 1,
            ]);
        } else {
            $this->criarProduto([
                'comum_id'       => $comumId,
                'codigo'         => $codigo,
                'tipo_bem_id'    => $tipoBemId,
                'bem'            => $bem,
                'complemento'    => $complemento,
                'dependencia_id' => $dependenciaId,
                'novo'           => 0,
                'importado'      => 1,
                'checado'        => 0,
                'editado'        => 0,
                'imprimir_etiqueta' => 0,
                'imprimir_14_1'  => 0,
                'observacao'     => '',
                'ativo'          => 1,
            ]);
        }
    }
}

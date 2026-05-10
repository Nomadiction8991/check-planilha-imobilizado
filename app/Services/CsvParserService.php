<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ConnectionManager;
use App\Core\QueryCache;
use League\Csv\Reader;
use League\Csv\CharsetConverter;
use Illuminate\Support\Facades\Schema;
use PDO;
use Exception;

/**
 * CsvParserService — Serviço moderno para parsing e análise de CSV.
 *
 * O CSV é um relatório do sistema de imobilizado CCB com formato:
 *  - ~25 linhas de metadados (nome da congregação, CNPJ, filtros)
 *  - Linha de cabeçalho: Código,,,Nome,,,Fornecedor,,,,Localidade,...,Dependência,...
 *  - Dados em colunas esparsas (muitas células vazias entre campos)
 *  - Linhas vazias intercaladas entre registros
 *  - Campo "Nome" contém: "TIPO_CODE - DESCRICAO BEM COMPLEMENTO"
 *
 * Configuração lida da tabela `configuracoes`:
 *  - pulo_linhas: quantas linhas pular antes do cabeçalho
 *  - mapeamento_colunas: codigo=A;complemento=D;dependencia=P;localidade=K
 */
class CsvParserService
{
    /** @var PDO Conexão com banco de dados */
    private PDO $conexao;

    /** Administração atual usada para resolver tipos de bens */
    private ?int $administrationId;

    /** @var QueryCache Cache em memória para queries frequentes (Melhoria 11) */
    private QueryCache $cache;

    /** Status possíveis para cada registro */
    public const STATUS_NOVO = 'novo';
    public const STATUS_ATUALIZAR = 'atualizar';
    public const STATUS_SEM_ALTERACAO = 'sem_alteracao';
    public const STATUS_EXCLUIR = 'excluir';

    /** Ações disponíveis para o usuário */
    public const ACAO_IMPORTAR = 'importar';
    public const ACAO_PULAR = 'pular';
    public const ACAO_EXCLUIR = 'excluir';

    /** Mapeamento padrão de colunas (letra → índice) */
    private const MAPEAMENTO_PADRAO = [
        'codigo' => 0,       // Coluna A
        'complemento' => 3,  // Coluna D (campo "Nome" no relatório)
        'dependencia' => 15, // Coluna P
        'localidade' => 10,  // Coluna K
    ];

    /** Linhas de metadados a pular por padrão */
    private const PULO_LINHAS_PADRAO = 25;

    /** @var array Cache para tipos de bens já consultados */
    private array $cacheBensDoTipo = [];

    /** @var array Cache para comuns consultadas */
    private array $cacheComuns = [];

    public function __construct(?PDO $conexao = null, ?int $administrationId = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
        $this->administrationId = $administrationId !== null && $administrationId > 0 ? $administrationId : null;
        $this->cache = new QueryCache();
    }

    /**
     * Retorna diretórios base autorizados para arquivos de importação.
     * Inclui fallback em /tmp para ambientes com storage sem escrita.
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
     * Valida caminho do arquivo importado contra diretórios permitidos.
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

    // ─── MÉTODO PRINCIPAL ───

    /**
     * Analisa um CSV e retorna resultado completo de análise.
     * Detecta automaticamente as localidades (igrejas) no CSV.
     * Cada localidade vira uma comum separada. Compara cada linha contra
     * os produtos da respectiva comum.
     *
     * @param string $caminhoArquivo Caminho do arquivo CSV
     * @param int    $comumIdFallback Comum padrão para linhas sem localidade (0 = nenhum)
     */
    public function analisar(string $caminhoArquivo, int $comumIdFallback = 0): array
    {
        // Garantir tempo para processar arquivos grandes
        @set_time_limit(0);

        if (!file_exists($caminhoArquivo)) {
            throw new Exception('Arquivo não encontrado: ' . $caminhoArquivo);
        }

        // Carrega configuração do banco
        $config = $this->carregarConfiguracoes();
        $mapeamento = $this->parsearMapeamento($config['mapeamento_colunas'] ?? '');
        $puloLinhas = (int) ($config['pulo_linhas'] ?? self::PULO_LINHAS_PADRAO);

        // Lê CSV com league/csv
        $linhas = $this->lerCsv($caminhoArquivo, $mapeamento, $puloLinhas);

        if (empty($linhas)) {
            throw new Exception('Arquivo CSV vazio ou sem dados válidos após pular ' . $puloLinhas . ' linhas de cabeçalho');
        }

        // Detectar localidades e preparar mapa de comuns
        $mapaCodigoParaComumId = $this->detectarComunsNoCsv($linhas);
        $comunsDetectadas = $this->gerarComunsDetectadas($mapaCodigoParaComumId);

        // Pré-carregar produtos e dependências
        $produtosPorComum = $this->carregarTodosProdutos($mapaCodigoParaComumId, $comumIdFallback);
        $dependenciasPorComum = $this->carregarTodasDependencias($mapaCodigoParaComumId, $comumIdFallback);
        $tiposBens = $this->carregarTiposBens();

        // Analisar linhas
        $resultado = $this->analisarTodasAsLinhas($linhas, $mapaCodigoParaComumId, $comumIdFallback, $produtosPorComum, $dependenciasPorComum, $tiposBens);

        // Detectar exclusões
        $exclusoes = $this->detectarExclusoes(
            $mapaCodigoParaComumId,
            $produtosPorComum,
            $resultado['codigosPorComum'],
            $resultado['depsPorComum'] ?? []
        );
        $resultado['registros'] = array_merge($resultado['registros'], $exclusoes['registros']);
        $resultado['resumo']['exclusoes'] = $exclusoes['total'];

        return [
            'resumo' => $resultado['resumo'],
            'registros' => $resultado['registros'],
            'comuns_detectadas' => $comunsDetectadas,
        ];
    }

    /**
     * Detecta automaticamente as comuns presentes no CSV.
     */
    private function detectarComunsNoCsv(array $linhas): array
    {
        $codigosComuns = [];
        foreach ($linhas as $linha) {
            $cod = $linha['codigo_comum'] ?? '';
            if ($cod !== '' && !isset($codigosComuns[$cod])) {
                $codigosComuns[$cod] = true;
            }
        }

        $codigos = array_keys($codigosComuns);
        if (empty($codigos)) {
            return [];
        }

        // Busca todas as comuns de uma vez usando Eloquent/Query Builder se disponível
        // para aproveitar o ConnectionManager do Laravel
        $placeholders = implode(',', array_fill(0, count($codigos), '?'));
        $stmt = $this->conexao->prepare("SELECT id, codigo FROM comums WHERE codigo IN ($placeholders)");
        $stmt->execute($codigos);
        $comunsDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapa = array_fill_keys($codigos, 0);
        foreach ($comunsDb as $comum) {
            $mapa[(string) $comum['codigo']] = (int) $comum['id'];
        }

        return $mapa;
    }

    /**
     * Gera lista de comuns detectadas para exibição no preview.
     */
    private function gerarComunsDetectadas(array $mapaCodigoParaComumId): array
    {
        $comunsDetectadas = [];
        foreach ($mapaCodigoParaComumId as $codigoComum => $comumId) {
            $comunsDetectadas[] = [
                'codigo' => $codigoComum,
                'localidade' => $codigoComum,
                'comum_id' => $comumId,
                'existe' => $comumId > 0,
            ];
        }
        return $comunsDetectadas;
    }

    /**
     * Carrega todos os produtos dos comuns.
     */
    private function carregarTodosProdutos(array $mapaCodigoParaComumId, int $comumIdFallback): array
    {
        $comumIds = array_filter(array_values($mapaCodigoParaComumId), fn($id) => $id > 0);
        if ($comumIdFallback > 0) {
            $comumIds[] = $comumIdFallback;
        }
        $comumIds = array_unique($comumIds);

        if (empty($comumIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($comumIds), '?'));
        $sql = "SELECT p.id_produto, p.codigo, p.bem, p.complemento, p.dependencia_id, p.comum_id, p.tipo_bem_id, p.ativo,
                       tb.codigo AS tipo_bem_codigo, tb.descricao AS tipo_bem_descricao,
                       d.descricao AS dependencia_descricao
                FROM produtos p
                LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                LEFT JOIN dependencias d ON p.dependencia_id = d.id
                WHERE p.comum_id IN ($placeholders)";

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute(array_values($comumIds));
        $todosProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $produtosPorComum = [];
        foreach ($todosProdutos as $produto) {
            $chave = strtoupper(trim((string) $produto['codigo']));
            $produtosPorComum[$produto['comum_id']][$chave] = $produto;
        }

        return $produtosPorComum;
    }

    /**
     * Carrega todas as dependências dos comuns.
     */
    private function carregarTodasDependencias(array $mapaCodigoParaComumId, int $comumIdFallback): array
    {
        $comumIds = array_filter(array_values($mapaCodigoParaComumId), fn($id) => $id > 0);
        if ($comumIdFallback > 0) {
            $comumIds[] = $comumIdFallback;
        }
        $comumIds = array_unique($comumIds);

        if (empty($comumIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($comumIds), '?'));
        $stmt = $this->conexao->prepare("SELECT id, descricao, comum_id FROM dependencias WHERE comum_id IN ($placeholders)");
        $stmt->execute(array_values($comumIds));
        $todasDeps = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $depsPorComum = [];
        foreach ($todasDeps as $dep) {
            $chave = strtoupper(trim($dep['descricao']));
            $depsPorComum[$dep['comum_id']][$chave] = (int) $dep['id'];
        }

        return $depsPorComum;
    }

    /**
     * Analisa todas as linhas do CSV.
     */
    private function analisarTodasAsLinhas(
        array $linhas,
        array $mapaCodigoParaComumId,
        int $comumIdFallback,
        array $produtosPorComum,
        array $dependenciasPorComum,
        array $tiposBens
    ): array {
        $registros = [];
        $codigosPorComum = [];
        $depsPorComum = [];
        $resumo = [
            'total' => 0,
            'novos' => 0,
            'atualizar' => 0,
            'sem_alteracao' => 0,
            'erros' => 0,
            'exclusoes' => 0,
        ];

        foreach ($linhas as $idx => $linha) {
            $resumo['total']++;

            try {
                $codigoComum = $linha['codigo_comum'] ?? '';
                $comumIdLinha = $mapaCodigoParaComumId[$codigoComum] ?? $comumIdFallback;

                // Rastrear códigos e dependências por comum
                $codigoRastr = strtoupper(trim($linha['codigo'] ?? ''));
                $depRastr = strtoupper(trim((string) ($linha['dependencia_descricao'] ?? $linha['dependencia'] ?? '')));

                if ($comumIdLinha > 0) {
                    if ($codigoRastr !== '') {
                        $codigosPorComum[$comumIdLinha][$codigoRastr] = true;
                    }
                    if ($depRastr !== '') {
                        $depsPorComum[$comumIdLinha][$depRastr] = true;
                    }
                }

                $produtosExistentes = $produtosPorComum[$comumIdLinha] ?? [];
                $dependencias = $dependenciasPorComum[$comumIdLinha] ?? [];

                $registro = $this->analisarLinha($linha, $produtosExistentes, $tiposBens, $dependencias, $comumIdLinha);
                $registro['linha_csv'] = $linha['_linha_original'] ?? ($idx + 1);

                $this->atualizarResumo($resumo, $registro);
                $registros[] = $registro;
            } catch (Exception $e) {
                $resumo['erros']++;
                $registros[] = $this->criarRegistroErro($linha, $idx, $e);
            }
        }

        return [
            'registros' => $registros,
            'resumo' => $resumo,
            'codigosPorComum' => $codigosPorComum,
            'depsPorComum' => $depsPorComum,
        ];
    }

    /**
     * Atualiza o resumo baseado no status do registro.
     */
    private function atualizarResumo(array &$resumo, array &$registro): void
    {
        switch ($registro['status']) {
            case self::STATUS_NOVO:
                $registro['acao_sugerida'] = self::ACAO_IMPORTAR;
                $resumo['novos']++;
                break;
            case self::STATUS_ATUALIZAR:
                $registro['acao_sugerida'] = self::ACAO_IMPORTAR;
                $resumo['atualizar']++;
                break;
            case self::STATUS_SEM_ALTERACAO:
                $registro['acao_sugerida'] = self::ACAO_PULAR;
                $resumo['sem_alteracao']++;
                break;
        }
    }

    /**
     * Cria registro de erro.
     */
    private function criarRegistroErro(array $linha, int $idx, Exception $e): array
    {
        return [
            'linha_csv' => $linha['_linha_original'] ?? ($idx + 1),
            'status' => 'erro',
            'acao_sugerida' => self::ACAO_PULAR,
            'erro' => $e->getMessage(),
            'dados_csv' => $linha,
            'dados_db' => null,
            'diferencas' => [],
        ];
    }

    /**
     * Detecta produtos para exclusão.
     */
    private function detectarExclusoes(array $mapaCodigoParaComumId, array $produtosPorComum, array $codigosPorComum, array $depsPorComum): array
    {
        $registros = [];
        $total = 0;

        foreach ($mapaCodigoParaComumId as $codigoComumExcl => $comumIdExcl) {
            if ($comumIdExcl <= 0) continue;

            $produtosDb = $produtosPorComum[$comumIdExcl] ?? [];
            $codigosNoCSV = $codigosPorComum[$comumIdExcl] ?? [];
            $depsNoCSV = $depsPorComum[$comumIdExcl] ?? [];

            foreach ($produtosDb as $codigoUpper => $produto) {
                if ($codigoUpper === '' || isset($codigosNoCSV[$codigoUpper]) || ($produto['ativo'] ?? 1) == 0) {
                    continue;
                }

                // Se o CSV contém informações de dependência, só sugerimos exclusão
                // se a dependência do produto no DB estiver presente no CSV.
                // Se a dependência não estiver no CSV, assumimos que o arquivo é parcial para aquele setor.
                $depProdutoDb = strtoupper(trim((string) ($produto['dependencia_descricao'] ?? '')));
                
                if (!empty($depsNoCSV) && $depProdutoDb !== '' && !isset($depsNoCSV[$depProdutoDb])) {
                    continue;
                }

                $total++;
                $registros[] = $this->criarRegistroExclusao($produto, $codigoComumExcl);
            }
        }

        return ['registros' => $registros, 'total' => $total];
    }

    /**
     * Cria registro de exclusão sugerida.
     */
    private function criarRegistroExclusao(array $produto, string $codigoComumExcl): array
    {
        return [
            'linha_csv' => 'ex' . $produto['id_produto'],
            'status' => self::STATUS_EXCLUIR,
            'acao_sugerida' => self::ACAO_EXCLUIR,
            'dados_csv' => [
                'codigo' => $produto['codigo'],
                'tipo_bem_codigo' => $produto['tipo_bem_codigo'] ?? '',
                'tipo_bem_descricao' => $produto['tipo_bem_descricao'] ?? '',
                'bem' => $produto['bem'] ?? '',
                'complemento' => $produto['complemento'] ?? '',
                'dependencia_descricao' => $produto['dependencia_descricao'] ?? '',
                'codigo_comum' => $codigoComumExcl,
                'localidade' => '',
                'nome_original' => trim(($produto['bem'] ?? '') . ' ' . ($produto['complemento'] ?? '')),
            ],
            'dados_db' => [
                'id_produto' => $produto['id_produto'],
                'codigo' => $produto['codigo'],
                'bem' => $produto['bem'] ?? '',
                'complemento' => $produto['complemento'] ?? '',
                'dependencia' => $produto['dependencia_descricao'] ?? '',
            ],
            'diferencas' => [],
            'id_produto' => $produto['id_produto'],
        ];
    }

    // ─── LEITURA DO CSV ───

    /**
     * Lê o CSV usando league/csv com detecção automática de encoding e delimitador.
     * Aplica o mapeamento de colunas por posição (A, D, P, K etc).
     * VALIDAÇÃO SEGURA: previne Path Traversal
     */
    private function lerCsv(string $caminho, array $mapeamento, int $puloLinhas): array
    {
        // Validar Path Traversal: caminho deve estar em um diretório de importação permitido
        $caminhoReal = $this->validarCaminhoImportacao($caminho);

        if (!is_readable($caminhoReal)) {
            throw new Exception('Arquivo não legível');
        }

        // Detectar encoding
        $amostra = (string) file_get_contents($caminhoReal, false, null, 0, 8192);
        $encoding = mb_detect_encoding($amostra, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        // Detectar delimitador
        $delimitador = $this->detectarDelimitador($amostra);

        // Criar reader league/csv com caminho validado
        $csv = Reader::createFromPath($caminhoReal, 'r');
        $csv->setDelimiter($delimitador);
        $csv->setEnclosure('"');

        // Converter encoding para UTF-8 se necessário
        if ($encoding && $encoding !== 'UTF-8') {
            CharsetConverter::addTo($csv, $encoding, 'UTF-8');
        }

        // Encontrar início dos dados sem carregar o arquivo todo
        $headSample = [];
        $records = $csv->getRecords();
        foreach ($records as $index => $row) {
            $headSample[] = $row;
            if ($index >= $puloLinhas + 50) { // Amostra generosa para o cabeçalho
                break;
            }
        }

        $inicioLeitura = $this->encontrarInicioDados($headSample, $puloLinhas, $mapeamento);

        // Extrair dados iterando sobre o arquivo
        $linhas = [];
        $ultimaLocalidade  = '';  // carry-forward: localidade do último registro válido
        $ultimoCodigoComum = '';  // carry-forward: codigo_comum do último registro válido
        
        $records = $csv->getRecords();
        foreach ($records as $index => $row) {
            if ($index < $inicioLeitura) {
                continue;
            }

            // Extrair código da coluna mapeada
            $colCodigo = $mapeamento['codigo'] ?? self::MAPEAMENTO_PADRAO['codigo'];
            $codigo = trim((string) ($row[$colCodigo] ?? ''));

            // Pula linhas sem código (vazias ou subtotais)
            if ($codigo === '' || $this->isLinhaMetadados($codigo)) {
                continue;
            }

            // Extrair campos pelo mapeamento de colunas
            $colNome = $mapeamento['complemento'] ?? self::MAPEAMENTO_PADRAO['complemento'];
            $colDependencia = $mapeamento['dependencia'] ?? self::MAPEAMENTO_PADRAO['dependencia'];
            $colLocalidade = $mapeamento['localidade'] ?? self::MAPEAMENTO_PADRAO['localidade'];

            $nomeCompleto    = trim((string) ($row[$colNome]        ?? ''));
            $dependencia    = trim((string) ($row[$colDependencia]  ?? ''));
            $localidade     = trim((string) ($row[$colLocalidade]   ?? ''));

            // ── Carry-forward de localidade ──────────────────────────────────────────
            // O CSV do imobilizado CCB exibe a localidade apenas na 1ª linha de cada
            // grupo; as linhas seguintes deixam essa coluna em branco.  Quando isso
            // ocorre, reutiliza a última localidade válida vista.
            if (empty($localidade) && !empty($ultimaLocalidade)) {
                $localidade    = $ultimaLocalidade;
                $codigoComum   = $ultimoCodigoComum;
            } else {
                $codigoComum = $this->extrairCodigoComum($localidade);
                if (!empty($localidade)) {
                    $ultimaLocalidade  = $localidade;
                    $ultimoCodigoComum = $codigoComum;
                }
            }

            // ── Fallback: extrair código da comum do código do produto ──────────────
            // Quando a coluna de localidade está vazia e não há carry-forward
            // (ex: CSV sem coluna de localidade), o código do produto traz o código
            // da comum antes do "/": "09-0565 / 001495" → comum = "09-0565".
            if (empty($codigoComum) && strpos($codigo, '/') !== false) {
                $partes      = explode('/', $codigo, 2);
                $codigoComum = trim($partes[0]);
            }

            // ── Parse completo: extrai tipo_bem, bem e complemento do nome do CSV ──
            // parsearNome() separa "N - BEM COMPLEMENTO" em suas partes estruturadas.
            $parsed       = $this->parsearNome($nomeCompleto);
            $tipoBemCodigo = $parsed['tipo_bem_codigo'] ?: '';
            $bem           = $parsed['bem'] ?: $nomeCompleto;    // fallback: nome completo
            $complemento   = $parsed['complemento'] ?: '';

            // Dependência vem direto da coluna da planilha (sem parse inline)
            $dependenciaFinal = strtoupper($dependencia);

            $linhas[] = [
                'codigo'            => $codigo,
                'tipo_bem_codigo'   => $tipoBemCodigo,
                'bem'               => $bem,
                'complemento'       => $complemento,
                'dependencia'       => $dependenciaFinal,
                'localidade'        => $localidade,
                'codigo_comum'      => $codigoComum,
                'nome_original'     => $nomeCompleto,
                'quantidade'        => 1,
                '_linha_original'   => $index + 1,
            ];
        }

        return $linhas;
    }

    /**
     * Detecta o delimitador mais provável do CSV.
     */
    private function detectarDelimitador(string $amostra): string
    {
        $contVirgula = substr_count($amostra, ',');
        $contPontoVirgula = substr_count($amostra, ';');
        $contTab = substr_count($amostra, "\t");

        if ($contPontoVirgula > $contVirgula && $contPontoVirgula > $contTab) {
            return ';';
        }
        if ($contTab > $contVirgula && $contTab > $contPontoVirgula) {
            return "\t";
        }

        return ',';
    }

    /**
     * Encontra o índice da primeira linha de DADOS (logo após o cabeçalho).
     * Usa pulo_linhas como ponto de partida, mas tenta auto-detectar se possível.
     */
    private function encontrarInicioDados(array $linhas, int $puloLinhas, array $mapeamento): int
    {
        $totalLinhas = count($linhas);

        // Tenta encontrar a linha de cabeçalho automaticamente
        // Procura por uma linha cujo primeiro campo não-vazio contenha "Código" ou "codigo"
        for ($i = 0; $i < min($totalLinhas, $puloLinhas + 10); $i++) {
            $primeiraCol = trim((string) ($linhas[$i][0] ?? ''));
            $primeiraColNorm = $this->removerAcentos(strtolower($primeiraCol));

            if ($primeiraColNorm === 'codigo') {
                // Encontrou a linha de cabeçalho → dados começam na próxima
                return $i + 1;
            }
        }

        // Fallback: pular as linhas configuradas + 1 (para o cabeçalho)
        $inicio = $puloLinhas + 1;
        return min($inicio, $totalLinhas);
    }

    /**
     * Verifica se uma linha é metadado/subtotal (não é dado de produto).
     */
    private function isLinhaMetadados(string $codigo): bool
    {
        // Linhas de subtotal, totais, nomes de grupo etc.
        $codigoNorm = strtolower(trim($codigo));

        $palavrasMetadados = [
            'total',
            'subtotal',
            'soma',
            'resumo',
            'grupo',
            'relatório',
            'relatorio',
            'congregação',
            'congregacao',
            'dependência',
            'dependencia',
            'página',
            'pagina',
            'folha',
        ];

        foreach ($palavrasMetadados as $palavra) {
            if (str_contains($codigoNorm, $palavra)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parseia o campo "Nome" do relatório para extrair tipo_bem, bem e complemento.
     * 
     * Formato do CSV: "4 - CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO"
     * Onde:
     *   4 = código do tipo_bem
     *   Texto após "-" = BEM + COMPLEMENTO (separação inteligente usando lista de bens do tipo)
     * 
     * O tipo_bem tem descricao como "CADEIRA" ou "CADEIRA / MESA". 
     * Precisamos identificar qual opção aparece no texto para separar BEM do COMPLEMENTO.
     * 
     * Também suporta formato personalizado: "1x - Banco 2,50m [Banheiro]"
     */
    private function parsearNome(string $nome): array
    {
        $resultado = [
            'tipo_bem_codigo' => '',
            'descricao_apos_tipo' => $nome,
            'bem' => $nome,
            'complemento' => '',
            'quantidade' => 1,
            'dependencia_inline' => '',
            'nome_original' => $nome,
        ];

        if (empty($nome)) {
            return $resultado;
        }

        // FORMATO PERSONALIZADO: "1x - Banco 2,50m [Banheiro]"
        if (preg_match('/^(\d+)x\s*\-\s*(.+)$/ui', $nome, $m)) {
            $quantidade = (int) $m[1];
            $restoNome = trim($m[2]);

            // Extrair dependência entre colchetes
            $dependenciaInline = '';
            if (preg_match('/^(.+?)\s*\[([^\]]+)\]\s*$/u', $restoNome, $depMatch)) {
                $restoNome = trim($depMatch[1]);
                $dependenciaInline = trim($depMatch[2]);
            }

            // Separar bem e complemento
            if (preg_match('/^([A-ZÀ-Ú\s]+?)\s+((?:\d|[A-ZÀ-Ú]+\s+\d).+)$/ui', $restoNome, $bemMatch)) {
                $bem = mb_strtoupper(trim($bemMatch[1]), 'UTF-8');
                $complemento = mb_strtoupper(trim($bemMatch[2]), 'UTF-8');
            } else {
                $bem = mb_strtoupper($restoNome, 'UTF-8');
                $complemento = '';
            }

            $resultado['quantidade'] = $quantidade;
            $resultado['bem'] = $bem;
            $resultado['complemento'] = $complemento;
            $resultado['dependencia_inline'] = mb_strtoupper($dependenciaInline, 'UTF-8');
            $resultado['descricao_apos_tipo'] = $restoNome;

            return $resultado;
        }

        // FORMATO CSV: "4 - CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO"
        if (preg_match('/^\s*(\d{1,3})(?:[\.,]\d+)?\s*[\-–—]\s*(.+)$/u', $nome, $m)) {
            $resultado['tipo_bem_codigo'] = $m[1];
            $textoAposCodigo = trim($m[2]);
            $textoAposCodigoUpper = mb_strtoupper($textoAposCodigo, 'UTF-8');
            $resultado['descricao_apos_tipo'] = $textoAposCodigo;
            $resultado['complemento'] = $textoAposCodigoUpper;

            // Buscar as opções de bens do tipo_bem no banco
            $bensDoTipo = $this->obterBensDoTipo($m[1]);

            if (!empty($bensDoTipo)) {
                // ──────────────────────────────────────────────────────────
                // O formato CSV é: [ECO_TIPO_DESC] [BEM_SELECIONADO] [COMPLEMENTO]
                // Ex: "CADEIRA CADEIRA TRIBUNA ALMOFADADA PULPITO"
                //      ^^^^^^^ eco     ^^^^^^^ BEM  ^^^^^^^^^^^^^^^^^^^^^^^^ complemento
                // Ex: "BANCO DE MADEIRA / GENUFLEXÓRIO BANCOS DE MADEIRA 2,50 M"
                //      ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ eco  ^^^^^^^^^^^^^^^^^^ BEM+compl
                //
                // PASSO 1: Remover o "eco" da descrição do tipo_bem do início do texto
                // PASSO 2: Identificar o BEM no texto restante
                // ──────────────────────────────────────────────────────────

                usort($bensDoTipo, fn($a, $b) => mb_strlen($b) - mb_strlen($a));

                $textoUpper = mb_strtoupper($textoAposCodigo, 'UTF-8');
                $textoNorm = $this->removerAcentos($textoUpper);
                $bemEncontrado = false;

                // ── PASSO 1: Remover eco da descrição do tipo apenas para localizar o bem ──
                // O CSV ecoa todas as opções do tipo separadas por " / ".
                // A remoção é usada só para identificar o bem correto; o complemento
                // preserva o texto original.
                $textoRestante = $textoUpper;
                $textoRestanteNorm = $textoNorm;

                foreach ($bensDoTipo as $opcao) {
                    $opcaoUpper = mb_strtoupper(trim($opcao), 'UTF-8');
                    $opcaoNorm = $this->removerAcentos($opcaoUpper);

                    if (strpos($textoRestanteNorm, $opcaoNorm) === 0) {
                        // Remover esta opção do início
                        $textoRestante = mb_substr($textoRestante, mb_strlen($opcaoUpper));
                        $textoRestanteNorm = substr($textoRestanteNorm, strlen($opcaoNorm));
                        // Remover separadores residuais (espaço, /, -)
                        $textoRestante = preg_replace('/^\s*[\/\-\|]+\s*/', ' ', $textoRestante);
                        $textoRestante = trim($textoRestante);
                        $textoRestanteNorm = preg_replace('/^\s*[\/\-\|]+\s*/', ' ', $textoRestanteNorm);
                        $textoRestanteNorm = trim($textoRestanteNorm);
                    }
                }

                // ── PASSO 2: Identificar BEM no texto restante ──
                if (!empty($textoRestante)) {
                    foreach ($bensDoTipo as $bemOpcao) {
                        $bemOpcaoUpper = mb_strtoupper(trim($bemOpcao), 'UTF-8');
                        $bemOpcaoNorm = $this->removerAcentos($bemOpcaoUpper);

                        if (strpos($textoRestanteNorm, $bemOpcaoNorm) === 0) {
                            $resultado['bem'] = $bemOpcaoUpper;
                            $bemEncontrado = true;
                            break;
                        }
                    }

                    if (!$bemEncontrado) {
                        // BEM não correspondeu exatamente → usar primeira opção do tipo
                        // e manter o complemento original informado no CSV
                        $resultado['bem'] = mb_strtoupper(trim($bensDoTipo[0]), 'UTF-8');
                        $bemEncontrado = true;
                    }
                }

                if (!$bemEncontrado) {
                    // Nenhum eco removido → fallback: match direto no texto completo
                    foreach ($bensDoTipo as $bemOpcao) {
                        $bemOpcaoNorm = $this->removerAcentos(mb_strtoupper(trim($bemOpcao), 'UTF-8'));
                        if (strpos($textoNorm, $bemOpcaoNorm) === 0) {
                            $resultado['bem'] = mb_strtoupper(trim($bemOpcao), 'UTF-8');
                            $bemEncontrado = true;
                            break;
                        }
                    }
                }

                if (!$bemEncontrado) {
                    $resultado['bem'] = $textoUpper;
                }
            } else {
                // Sem dados do tipo_bem → fallback: tenta separar por " - "
                if (preg_match('/^(.+?)\s+\-\s+(.+)$/u', $textoAposCodigo, $parts)) {
                    $resultado['bem'] = trim($parts[1]);
                    $resultado['complemento'] = trim($parts[2]);
                } else {
                    $resultado['bem'] = $textoAposCodigo;
                    $resultado['complemento'] = '';
                }
            }
        }

        return $resultado;
    }

    /**
     * Busca as opções de bens de um tipo_bem pelo código.
     * A descricao do tipo_bem contém as opções separadas por "/".
     * Exemplo: tipo 4 → descricao = "CADEIRA" → retorna ["CADEIRA"]
     * Exemplo: tipo 1 → descricao = "BANCO DE MADEIRA/GENUFLEXORIO" → retorna ["BANCO DE MADEIRA", "GENUFLEXORIO"]
     * 
     * @return string[] Lista de opções de bens
     */
    private function obterBensDoTipo(string $tipoBemCodigo): array
    {
        if (empty($tipoBemCodigo)) {
            return [];
        }

        if (isset($this->cacheBensDoTipo[$tipoBemCodigo])) {
            return $this->cacheBensDoTipo[$tipoBemCodigo];
        }

        try {
            $supportsAdministrationScope = Schema::hasColumn('tipos_bens', 'administracao_id');

            if ($supportsAdministrationScope && $this->administrationId !== null) {
                $stmt = $this->conexao->prepare(
                    "SELECT descricao
                     FROM tipos_bens
                     WHERE codigo = :codigo
                       AND (administracao_id = :administracao_id OR administracao_id IS NULL)
                     ORDER BY administracao_id IS NULL ASC
                     LIMIT 1"
                );
                $stmt->execute([
                    ':codigo' => $tipoBemCodigo,
                    ':administracao_id' => $this->administrationId,
                ]);
            } else {
                $stmt = $this->conexao->prepare("SELECT descricao FROM tipos_bens WHERE codigo = :codigo LIMIT 1");
                $stmt->execute([':codigo' => $tipoBemCodigo]);
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || empty($row['descricao'])) {
                $this->cacheBensDoTipo[$tipoBemCodigo] = [];
                return [];
            }

            $resultado = array_map('trim', explode('/', $row['descricao']));
            $this->cacheBensDoTipo[$tipoBemCodigo] = $resultado;
            return $resultado;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Extrai o código da comum da coluna "Localidade".
     * Formato: "BR 09-0038" → retorna "09-0038"
     * Extrai apenas os números mantendo hífens e zeros à esquerda.
     */
    private function extrairCodigoComum(string $localidade): string
    {
        if (empty($localidade)) {
            return '';
        }

        // Remove letras e espaços, mantém apenas números e hífen
        // "BR 09-0038" → "09-0038"
        $codigo = preg_replace('/[^0-9\-]/', '', $localidade);

        return trim($codigo);
    }

    /**
     * Valida se o "bem" extraído existe na lista de bens do tipo_bem correspondente.
     * Retorna true se identificado, false se não encontrado na lista.
     */
    private function validarBemIdentificado(string $bem, string $tipoBemCodigo): bool
    {
        if (empty($bem) || empty($tipoBemCodigo)) {
            return false;
        }

        $bensDisponiveis = $this->obterBensDoTipo($tipoBemCodigo);

        if (empty($bensDisponiveis)) {
            return false;
        }

        $bemNormalizado = mb_strtoupper(trim($bem), 'UTF-8');

        foreach ($bensDisponiveis as $bemDisponivel) {
            $bemDisponivelUpper = mb_strtoupper(trim($bemDisponivel), 'UTF-8');
            if ($bemDisponivelUpper === $bemNormalizado) {
                return true;
            }
            if (str_contains($bemDisponivelUpper, $bemNormalizado) || str_contains($bemNormalizado, $bemDisponivelUpper)) {
                return true;
            }
        }

        return false;
    }

    // ─── CONFIGURAÇÕES ───

    /**
     * Carrega configurações da tabela `configuracoes`.
     */
    private function carregarConfiguracoes(): array
    {
        try {
            $stmt = $this->conexao->query("SELECT * FROM configuracoes LIMIT 1");
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            return $config ?: [
                'pulo_linhas' => (string) self::PULO_LINHAS_PADRAO,
                'mapeamento_colunas' => 'codigo=A;complemento=D;dependencia=P;localidade=K',
            ];
        } catch (Exception $e) {
            return [
                'pulo_linhas' => (string) self::PULO_LINHAS_PADRAO,
                'mapeamento_colunas' => 'codigo=A;complemento=D;dependencia=P;localidade=K',
            ];
        }
    }

    /**
     * Parseia o mapeamento de colunas "codigo=A;complemento=D;dependencia=P;localidade=K"
     * e converte letras em índices numéricos (A=0, B=1, ..., P=15).
     * VALIDAÇÃO SEGURA: aceita apenas campos e letras conhecidos
     */
    private function parsearMapeamento(string $mapeamentoStr): array
    {
        $mapeamento = self::MAPEAMENTO_PADRAO;

        if (empty($mapeamentoStr)) {
            return $mapeamento;
        }

        // Campos permitidos para evitar injeção
        $camposPermitidos = ['codigo', 'complemento', 'dependencia', 'localidade'];

        $pares = explode(';', $mapeamentoStr);
        foreach ($pares as $par) {
            $partes = explode('=', $par, 2);
            if (count($partes) !== 2) continue;

            $campo = trim(strtolower($partes[0]));
            $letra = trim(strtoupper($partes[1]));

            // Valida que o campo está na lista permitida
            if (!in_array($campo, $camposPermitidos, true)) {
                continue;
            }

            // Valida que a letra contém apenas A-Z
            if (!preg_match('/^[A-Z]+$/', $letra)) {
                continue;
            }

            // Limite de colunas (máximo AA = coluna 26)
            if (strlen($letra) > 2 || ($letra === 'AA' && strlen($letra) > 2)) {
                continue;
            }

            $indice = $this->letraParaIndice($letra);
            if ($indice >= 0 && $indice < 256) { // Limite razoável de colunas
                $mapeamento[$campo] = $indice;
            }
        }

        return $mapeamento;
    }

    /**
     * Converte letra de coluna Excel em índice numérico (A=0, B=1, ... Z=25, AA=26).
     * VALIDADO: apenas aceita letras maiúsculas A-Z
     */
    private function letraParaIndice(string $letra): int
    {
        $letra = strtoupper($letra);

        // Validação: apenas A-Z permitido
        if (!preg_match('/^[A-Z]+$/', $letra)) {
            return -1;
        }

        $indice = 0;

        for ($i = 0; $i < strlen($letra); $i++) {
            $indice = $indice * 26 + (ord($letra[$i]) - ord('A') + 1);
        }

        return $indice - 1;
    }

    // ─── ANÁLISE E DIFF ───

    /**
     * Analisa uma linha individual do CSV vs banco de dados.
     */
    private function analisarLinha(
        array $dadosCsv,
        array $produtosExistentes,
        array $tiposBens,
        array $dependencias,
        int $comumId
    ): array {
        $codigo = $dadosCsv['codigo'] ?? '';
        $tipoBemCodigo = $dadosCsv['tipo_bem_codigo'] ?? '';
        $bem = $dadosCsv['bem'] ?? '';
        $complemento = $dadosCsv['complemento'] ?? '';
        $dependenciaDescricao = $dadosCsv['dependencia'] ?? '';

        if (empty($codigo)) {
            throw new Exception('Código vazio na linha');
        }

        // Resolve tipo_bem e dependência para exibição
        $tipoBemDesc = $tiposBens[$tipoBemCodigo] ?? ($tipoBemCodigo !== '' ? 'Tipo ' . $tipoBemCodigo : '');
        $depDescNorm = trim(strtoupper($dependenciaDescricao));

        // Dados normalizados do CSV
        $dadosNormalizados = [
            'codigo' => $codigo,
            'tipo_bem_codigo' => $tipoBemCodigo,
            'tipo_bem_descricao' => $tipoBemDesc,
            'bem' => $bem,
            'complemento' => $complemento,
            'dependencia_descricao' => $depDescNorm ?: $dependenciaDescricao,
            'codigo_comum' => $dadosCsv['codigo_comum'] ?? '',
            'localidade' => $dadosCsv['localidade'] ?? '',
            'nome_original' => $dadosCsv['nome_original'] ?? $bem,
        ];

        // Verifica se produto existe no banco (busca por código uppercase)
        $chave = strtoupper(trim($codigo));
        $produtoDb = $produtosExistentes[$chave] ?? null;

        if (!$produtoDb) {
            return [
                'status' => self::STATUS_NOVO,
                'dados_csv' => $dadosNormalizados,
                'dados_db' => null,
                'diferencas' => [],
                'id_produto' => null,
            ];
        }

        // Calcula diferenças campo a campo
        $diferencas = [];

        // Produto estava desativado → reativação é considerada uma alteração
        if (($produtoDb['ativo'] ?? 1) == 0) {
            $diferencas['ativo'] = [
                'antes' => 'inativo',
                'depois' => 'ativo',
            ];
        }

        if (trim($produtoDb['bem'] ?? '') !== trim($bem)) {
            $diferencas['bem'] = [
                'antes' => $produtoDb['bem'],
                'depois' => $bem,
            ];
        }

        if (trim($produtoDb['complemento'] ?? '') !== trim($complemento)) {
            $diferencas['complemento'] = [
                'antes' => $produtoDb['complemento'],
                'depois' => $complemento,
            ];
        }

        $depDbDesc = trim(strtoupper($produtoDb['dependencia_descricao'] ?? ''));
        if ($depDbDesc !== $depDescNorm) {
            $diferencas['dependencia'] = [
                'antes' => $produtoDb['dependencia_descricao'] ?? '',
                'depois' => $dependenciaDescricao,
            ];
        }

        $status = empty($diferencas) ? self::STATUS_SEM_ALTERACAO : self::STATUS_ATUALIZAR;

        return [
            'status' => $status,
            'dados_csv' => $dadosNormalizados,
            'dados_db' => [
                'id_produto'  => $produtoDb['id_produto'],
                'codigo'      => $produtoDb['codigo'],
                'bem'         => $produtoDb['bem'] ?? '',
                'complemento' => $produtoDb['complemento'] ?? '',
                'tipo_bem'    => ($produtoDb['tipo_bem_codigo'] ?? '') . ' - ' . ($produtoDb['tipo_bem_descricao'] ?? ''),
                'dependencia' => $produtoDb['dependencia_descricao'] ?? '',
                'ativo'       => (int) ($produtoDb['ativo'] ?? 1),
            ],
            'diferencas' => $diferencas,
            'id_produto' => $produtoDb['id_produto'],
        ];
    }

    // ─── CARREGAMENTO DO BANCO (Identity Map) ───

    /**
     * Busca uma comum pelo código (sem criar).
     * Retorna comumId ou 0 se não encontrada.
     */
    private function buscarComumPorCodigo(string $codigoComum): int
    {
        if (empty($codigoComum)) {
            return 0;
        }

        $stmt = $this->conexao->prepare("SELECT id FROM comums WHERE codigo = :codigo LIMIT 1");
        $stmt->execute([':codigo' => $codigoComum]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['id'] : 0;
    }

    /**
     * Pré-carrega TODOS os produtos do comum indexados por código uppercase.
     * Inclui produtos inativos (ativo=0) para evitar que sejam re-inseridos
     * como duplicatas durante a importação — eles serão reativados se aparecerem
     * de volta no CSV.
     */
    private function carregarProdutosDoComum(int $comumId): array
    {
        $sql = "SELECT p.id_produto, p.codigo, p.bem, p.complemento, p.ativo,
                       tb.codigo AS tipo_bem_codigo, tb.descricao AS tipo_bem_descricao,
                       d.descricao AS dependencia_descricao
                FROM produtos p
                LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                LEFT JOIN dependencias d ON p.dependencia_id = d.id
                WHERE p.comum_id = :comum_id";

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute([':comum_id' => $comumId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapa = [];
        foreach ($rows as $row) {
            $chave = strtoupper(trim((string) ($row['codigo'] ?? '')));
            $mapa[$chave] = $row;
        }

        return $mapa;
    }

    /**
     * Carrega tipos_bens indexados por código.
     */
    private function carregarTiposBens(): array
    {
        $supportsAdministrationScope = Schema::hasColumn('tipos_bens', 'administracao_id');

        if ($supportsAdministrationScope && $this->administrationId !== null) {
            $stmt = $this->conexao->prepare(
                "SELECT codigo, descricao
                 FROM tipos_bens
                 WHERE administracao_id = :administracao_id OR administracao_id IS NULL
                 ORDER BY administracao_id IS NULL ASC, id ASC"
            );
            $stmt->execute([':administracao_id' => $this->administrationId]);
        } else {
            $stmt = $this->conexao->query("SELECT codigo, descricao FROM tipos_bens ORDER BY id ASC");
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapa = [];
        foreach ($rows as $row) {
            $codigo = (string) $row['codigo'];
            if (!isset($mapa[$codigo])) {
                $mapa[$codigo] = $row['descricao'];
            }
        }

        return $mapa;
    }

    /**
     * Carrega dependências indexadas por descrição uppercase.
     */
    private function carregarDependencias(int $comumId): array
    {
        try {
            $stmt = $this->conexao->prepare(
                "SELECT id, descricao FROM dependencias WHERE comum_id = :comum_id"
            );
            $stmt->execute([':comum_id' => $comumId]);
        } catch (Exception $e) {
            // Se tabela não tem coluna comum_id, carrega todas
            $stmt = $this->conexao->query("SELECT id, descricao FROM dependencias");
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapa = [];
        foreach ($rows as $row) {
            $mapa[strtoupper(trim((string) ($row['descricao'] ?? '')))] = $row['id'];
        }

        return $mapa;
    }

    // ─── UTILITÁRIOS ───

    /**
     * Remove acentos de uma string (para comparação).
     */
    private function removerAcentos(string $str): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        return $converted !== false ? $converted : $str;
    }

    /**
     * DEPRECATED: Use AnalysisPersistenceService para persistência.
     * Mantém compatibilidade com código antigo.
     */
    public function salvarAnalise(int $importacaoId, array $analise): string
    {
        $persistenceService = new AnalysisPersistenceService();
        return $persistenceService->salvarAnalise($importacaoId, $analise);
    }

    /**
     * DEPRECATED: Use AnalysisPersistenceService para persistência.
     * Mantém compatibilidade com código antigo.
     */
    public function carregarAnalise(int $importacaoId): ?array
    {
        $persistenceService = new AnalysisPersistenceService();
        return $persistenceService->carregarAnalise($importacaoId);
    }

    /**
     * DEPRECATED: Use AnalysisPersistenceService para persistência.
     * Mantém compatibilidade com código antigo.
     */
    public function limparAnalise(int $importacaoId): void
    {
        $persistenceService = new AnalysisPersistenceService();
        $persistenceService->limparAnalise($importacaoId);
    }
}

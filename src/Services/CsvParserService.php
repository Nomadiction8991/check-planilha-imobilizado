<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ConnectionManager;
use League\Csv\Reader;
use League\Csv\CharsetConverter;
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
    private PDO $conexao;

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

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
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

        // ── Detectar localidades (igrejas) distintas no CSV ──
        $codigosComuns = [];
        foreach ($linhas as $linha) {
            $cod = $linha['codigo_comum'] ?? '';
            if ($cod !== '' && !isset($codigosComuns[$cod])) {
                $codigosComuns[$cod] = $linha['localidade'] ?? $cod;
            }
        }

        // ── Resolver cada código_comum → comumId (se já existir no banco) ──
        $mapaCodigoParaComumId = []; // codigo_comum → comumId (0 se não existe ainda)
        $comunsDetectadas = [];      // info para o preview

        foreach ($codigosComuns as $codigoComum => $localidadeTexto) {
            $comumId = $this->buscarComumPorCodigo($codigoComum);
            $mapaCodigoParaComumId[$codigoComum] = $comumId;

            $comunsDetectadas[] = [
                'codigo' => $codigoComum,
                'localidade' => $localidadeTexto,
                'comum_id' => $comumId,
                'existe' => $comumId > 0,
            ];
        }

        // ── Pré-carregar produtos por-comum (Identity Map) ──
        $produtosPorComum = []; // comumId → [codigo_upper => produto]
        $dependenciasPorComum = [];

        foreach ($mapaCodigoParaComumId as $codigoComum => $comumId) {
            if ($comumId > 0 && !isset($produtosPorComum[$comumId])) {
                $produtosPorComum[$comumId] = $this->carregarProdutosDoComum($comumId);
                $dependenciasPorComum[$comumId] = $this->carregarDependencias($comumId);
            }
        }

        // Fallback para linhas sem localidade
        if ($comumIdFallback > 0 && !isset($produtosPorComum[$comumIdFallback])) {
            $produtosPorComum[$comumIdFallback] = $this->carregarProdutosDoComum($comumIdFallback);
            $dependenciasPorComum[$comumIdFallback] = $this->carregarDependencias($comumIdFallback);
        }

        $tiposBens = $this->carregarTiposBens();

        $registros = [];
        $resumo = [
            'total' => 0,
            'novos' => 0,
            'atualizar' => 0,
            'sem_alteracao' => 0,
            'erros' => 0,
            'exclusoes' => 0,
        ];

        // Rastreia códigos vistos no CSV por comumId (para detectar exclusões)
        $codigosPorComum = []; // comumId => [CODIGO_UPPER => true]

        foreach ($linhas as $idx => $linha) {
            $resumo['total']++;

            try {
                // Determinar qual comum usar para esta linha
                $codigoComum = $linha['codigo_comum'] ?? '';
                $comumIdLinha = $mapaCodigoParaComumId[$codigoComum] ?? $comumIdFallback;

                // Rastrear código no set da comum (para detectar exclusões depois)
                $codigoRastr = strtoupper(trim($linha['codigo'] ?? ''));
                if ($comumIdLinha > 0 && $codigoRastr !== '') {
                    $codigosPorComum[$comumIdLinha][$codigoRastr] = true;
                }

                // Produtos e dependências desta comum
                $produtosExistentes = $produtosPorComum[$comumIdLinha] ?? [];
                $dependencias = $dependenciasPorComum[$comumIdLinha] ?? [];

                $registro = $this->analisarLinha($linha, $produtosExistentes, $tiposBens, $dependencias, $comumIdLinha);
                $registro['linha_csv'] = $linha['_linha_original'] ?? ($idx + 1);

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

                $registros[] = $registro;
            } catch (Exception $e) {
                $resumo['erros']++;
                $registros[] = [
                    'linha_csv' => $linha['_linha_original'] ?? ($idx + 1),
                    'status' => 'erro',
                    'acao_sugerida' => self::ACAO_PULAR,
                    'erro' => $e->getMessage(),
                    'dados_csv' => $linha,
                    'dados_db' => null,
                    'diferencas' => [],
                ];
            }
        }

        // ── Detectar produtos do banco que NÃO estão no CSV (candidatos a exclusão) ──
        // Apenas para igrejas conhecidas (comumId > 0) e produtos com código
        foreach ($mapaCodigoParaComumId as $codigoComumExcl => $comumIdExcl) {
            if ($comumIdExcl <= 0) continue;

            $produtosDb = $produtosPorComum[$comumIdExcl] ?? [];
            $codigosNoCSV = $codigosPorComum[$comumIdExcl] ?? [];

            foreach ($produtosDb as $codigoUpper => $produto) {
                if ($codigoUpper === '') continue;          // sem código → não exclui
                if (isset($codigosNoCSV[$codigoUpper])) continue; // aparece no CSV → não exclui

                // Produto tem código mas não está no CSV → candidato a exclusão
                $linhaSintetica = 'ex' . $produto['id_produto'];
                $resumo['exclusoes']++;

                $registros[] = [
                    'linha_csv'     => $linhaSintetica,
                    'status'        => self::STATUS_EXCLUIR,
                    'acao_sugerida' => self::ACAO_EXCLUIR,
                    'dados_csv' => [
                        'codigo'                => $produto['codigo'],
                        'tipo_bem_codigo'       => $produto['tipo_bem_codigo'] ?? '',
                        'tipo_bem_descricao'    => $produto['tipo_bem_descricao'] ?? '',
                        'bem'                   => $produto['bem'] ?? '',
                        'complemento'           => $produto['complemento'] ?? '',
                        'dependencia_descricao' => $produto['dependencia_descricao'] ?? '',
                        'codigo_comum'          => $codigoComumExcl,
                        'localidade'            => '',
                        'nome_original'         => trim(($produto['bem'] ?? '') . ' ' . ($produto['complemento'] ?? '')),
                    ],
                    'dados_db' => [
                        'id_produto'  => $produto['id_produto'],
                        'codigo'      => $produto['codigo'],
                        'bem'         => $produto['bem'] ?? '',
                        'complemento' => $produto['complemento'] ?? '',
                        'dependencia' => $produto['dependencia_descricao'] ?? '',
                    ],
                    'diferencas' => [],
                    'id_produto'    => $produto['id_produto'],
                ];
            }
        }

        return [
            'resumo' => $resumo,
            'registros' => $registros,
            'comuns_detectadas' => $comunsDetectadas,
        ];
    }

    // ─── LEITURA DO CSV ───

    /**
     * Lê o CSV usando league/csv com detecção automática de encoding e delimitador.
     * Aplica o mapeamento de colunas por posição (A, D, P, K etc).
     */
    private function lerCsv(string $caminho, array $mapeamento, int $puloLinhas): array
    {
        // Detectar encoding
        $amostra = (string) file_get_contents($caminho, false, null, 0, 8192);
        $encoding = mb_detect_encoding($amostra, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        // Detectar delimitador
        $delimitador = $this->detectarDelimitador($amostra);

        // Criar reader league/csv
        $csv = Reader::createFromPath($caminho, 'r');
        $csv->setDelimiter($delimitador);
        $csv->setEnclosure('"');

        // Converter encoding para UTF-8 se necessário
        if ($encoding && $encoding !== 'UTF-8') {
            CharsetConverter::addTo($csv, $encoding, 'UTF-8');
        }

        // Ler todas as linhas como array
        $todasLinhas = iterator_to_array($csv->getRecords(), false);

        // Pular linhas de metadados — procurar o cabeçalho real
        $inicioLeitura = $this->encontrarInicioDados($todasLinhas, $puloLinhas, $mapeamento);

        // Extrair dados
        $linhas = [];
        $ultimaLocalidade  = '';  // carry-forward: localidade do último registro válido
        $ultimoCodigoComum = '';  // carry-forward: codigo_comum do último registro válido
        for ($i = $inicioLeitura; $i < count($todasLinhas); $i++) {
            $row = $todasLinhas[$i];

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
                '_linha_original'   => $i + 1,
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
            $resultado['descricao_apos_tipo'] = $textoAposCodigo;

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

                // ── PASSO 1: Remover eco da descrição do tipo ──
                // O CSV ecoa todas as opções do tipo separadas por " / "
                // Remover cada opção que aparece na ordem do início do texto
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
                            $complemento = trim(mb_substr($textoRestante, mb_strlen($bemOpcaoUpper)));
                            $complemento = preg_replace('/^[\s\-\/]+/', '', $complemento);
                            $resultado['complemento'] = mb_strtoupper($complemento, 'UTF-8');
                            $bemEncontrado = true;
                            break;
                        }
                    }

                    if (!$bemEncontrado) {
                        // BEM não correspondeu exatamente → usar primeira opção do tipo
                        // e todo o texto restante como complemento
                        $resultado['bem'] = mb_strtoupper(trim($bensDoTipo[0]), 'UTF-8');
                        $resultado['complemento'] = mb_strtoupper($textoRestante, 'UTF-8');
                        $bemEncontrado = true;
                    }
                }

                if (!$bemEncontrado) {
                    // Nenhum eco removido → fallback: match direto no texto completo
                    foreach ($bensDoTipo as $bemOpcao) {
                        $bemOpcaoNorm = $this->removerAcentos(mb_strtoupper(trim($bemOpcao), 'UTF-8'));
                        if (strpos($textoNorm, $bemOpcaoNorm) === 0) {
                            $resultado['bem'] = mb_strtoupper(trim($bemOpcao), 'UTF-8');
                            $resto = trim(mb_substr($textoUpper, mb_strlen(trim($bemOpcao))));
                            $resto = preg_replace('/^[\s\-\/]+/', '', $resto);
                            $resultado['complemento'] = $resto;
                            $bemEncontrado = true;
                            break;
                        }
                    }
                }

                if (!$bemEncontrado) {
                    $resultado['bem'] = $textoUpper;
                    $resultado['complemento'] = '';
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

        try {
            $stmt = $this->conexao->prepare("SELECT descricao FROM tipos_bens WHERE codigo = :codigo LIMIT 1");
            $stmt->execute([':codigo' => $tipoBemCodigo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || empty($row['descricao'])) {
                return [];
            }

            return array_map('trim', explode('/', $row['descricao']));
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
     */
    private function parsearMapeamento(string $mapeamentoStr): array
    {
        $mapeamento = self::MAPEAMENTO_PADRAO;

        if (empty($mapeamentoStr)) {
            return $mapeamento;
        }

        $pares = explode(';', $mapeamentoStr);
        foreach ($pares as $par) {
            $partes = explode('=', $par, 2);
            if (count($partes) !== 2) continue;

            $campo = trim(strtolower($partes[0]));
            $letra = trim(strtoupper($partes[1]));

            if (!empty($campo) && !empty($letra)) {
                $mapeamento[$campo] = $this->letraParaIndice($letra);
            }
        }

        return $mapeamento;
    }

    /**
     * Converte letra de coluna Excel em índice numérico (A=0, B=1, ... Z=25, AA=26).
     */
    private function letraParaIndice(string $letra): int
    {
        $letra = strtoupper($letra);
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
     */
    private function carregarProdutosDoComum(int $comumId): array
    {
        $sql = "SELECT p.id_produto, p.codigo, p.bem, p.complemento,
                       tb.codigo AS tipo_bem_codigo, tb.descricao AS tipo_bem_descricao,
                       d.descricao AS dependencia_descricao
                FROM produtos p
                LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                LEFT JOIN dependencias d ON p.dependencia_id = d.id
                WHERE p.comum_id = :comum_id AND p.ativo = 1";

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
        $stmt = $this->conexao->query("SELECT codigo, descricao FROM tipos_bens");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapa = [];
        foreach ($rows as $row) {
            $mapa[(string) $row['codigo']] = $row['descricao'];
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

    // ─── PERSISTÊNCIA DA ANÁLISE ───

    /**
     * Salva resultado da análise como JSON no storage/tmp.
     * Usa JSON compacto (sem PRETTY_PRINT) para reduzir tamanho em disco.
     */
    public function salvarAnalise(int $importacaoId, array $analise): string
    {
        $dir = __DIR__ . '/../../storage/tmp';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $caminho = $dir . '/analise_' . $importacaoId . '.json';

        $json = json_encode($analise, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new Exception('Erro ao serializar análise: ' . json_last_error_msg());
        }

        file_put_contents($caminho, $json);

        return $caminho;
    }

    /**
     * Carrega análise salva previamente.
     */
    public function carregarAnalise(int $importacaoId): ?array
    {
        $caminho = __DIR__ . '/../../storage/tmp/analise_' . $importacaoId . '.json';

        if (!file_exists($caminho)) {
            return null;
        }

        $json = file_get_contents($caminho);
        $dados = json_decode($json, true);

        return is_array($dados) ? $dados : null;
    }

    /**
     * Remove arquivo de análise (limpeza).
     */
    public function limparAnalise(int $importacaoId): void
    {
        $caminho = __DIR__ . '/../../storage/tmp/analise_' . $importacaoId . '.json';

        if (file_exists($caminho)) {
            unlink($caminho);
        }
    }
}

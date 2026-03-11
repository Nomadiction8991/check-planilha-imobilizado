<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ConnectionManager;
use App\Services\Csv\AnaliseStorage;
use App\Services\Csv\CsvReader;
use App\Services\Csv\NomeParser;
use PDO;
use Exception;

/**
 * CsvParserService — Orquestra a análise de um CSV de imobilizado CCB.
 *
 * O CSV contém:
 *  - ~25 linhas de metadados (congregação, CNPJ, filtros)
 *  - Linha de cabeçalho: Código,,,Nome,,,Fornecedor,,,,Localidade,...,Dependência,...
 *  - Dados em colunas esparsas
 *  - Campo "Nome": "TIPO_CODE - DESCRICAO BEM COMPLEMENTO"
 *
 * Configuração lida da tabela `configuracoes`:
 *  - pulo_linhas: quantas linhas pular antes do cabeçalho
 *  - mapeamento_colunas: codigo=A;complemento=D;dependencia=P;localidade=K
 *
 * As responsabilidades de I/O de CSV, parsing de nomes e persistência JSON
 * foram extraídas para:
 *  - CsvReader       — leitura/detecção de encoding e delimitador
 *  - NomeParser      — parse do campo Nome em tipo_bem, bem, complemento
 *  - AnaliseStorage  — serialização/desserialização JSON em storage/tmp
 */
class CsvParserService
{
    private PDO $conexao;
    private NomeParser $nomeParser;
    private AnaliseStorage $analiseStorage;
    private CsvReader $csvReader;

    /** Status possíveis para cada registro */
    public const STATUS_NOVO          = 'novo';
    public const STATUS_ATUALIZAR     = 'atualizar';
    public const STATUS_SEM_ALTERACAO = 'sem_alteracao';
    public const STATUS_EXCLUIR       = 'excluir';

    /** Ações disponíveis para o usuário */
    public const ACAO_IMPORTAR = 'importar';
    public const ACAO_PULAR    = 'pular';
    public const ACAO_EXCLUIR  = 'excluir';

    /** Mapeamento padrão de colunas (letra → índice) */
    private const MAPEAMENTO_PADRAO = [
        'codigo'       => 0,   // Coluna A
        'complemento'  => 3,   // Coluna D (campo "Nome" no relatório)
        'dependencia'  => 15,  // Coluna P
        'localidade'   => 10,  // Coluna K
    ];

    /** Linhas de metadados a pular por padrão */
    private const PULO_LINHAS_PADRAO = 25;

    public function __construct(
        PDO $conexao,
        ?NomeParser $nomeParser = null,
        ?AnaliseStorage $analiseStorage = null,
        ?CsvReader $csvReader = null
    ) {
        $this->conexao        = $conexao;
        $this->nomeParser     = $nomeParser     ?? new NomeParser($conexao);
        $this->analiseStorage = $analiseStorage ?? new AnaliseStorage();
        $this->csvReader      = $csvReader      ?? new CsvReader();
    }

    // ─── MÉTODO PRINCIPAL ───

    /**
     * Analisa um CSV e retorna resultado completo de análise.
     * Detecta automaticamente as localidades (igrejas) no CSV.
     * Cada localidade vira uma comum separada. Compara cada linha contra
     * os produtos da respectiva comum.
     *
     * @param string $caminhoArquivo   Caminho do arquivo CSV
     * @param int    $comumIdFallback  Comum padrão para linhas sem localidade (0 = nenhum)
     */
    public function analisar(string $caminhoArquivo, int $comumIdFallback = 0): array
    {
        if (!file_exists($caminhoArquivo)) {
            throw new Exception('Arquivo não encontrado: ' . $caminhoArquivo);
        }

        $config      = $this->carregarConfiguracoes();
        $mapeamento  = $this->parsearMapeamento($config['mapeamento_colunas'] ?? '');
        $puloLinhas  = (int) ($config['pulo_linhas'] ?? self::PULO_LINHAS_PADRAO);

        $linhas = $this->lerCsv($caminhoArquivo, $mapeamento, $puloLinhas);

        if (empty($linhas)) {
            throw new Exception(
                'Arquivo CSV vazio ou sem dados válidos após pular ' . $puloLinhas . ' linhas de cabeçalho'
            );
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
        $mapaCodigoParaComumId = [];
        $comunsDetectadas      = [];

        foreach ($codigosComuns as $codigoComum => $localidadeTexto) {
            $comumId = $this->buscarComumPorCodigo($codigoComum);
            $mapaCodigoParaComumId[$codigoComum] = $comumId;

            $comunsDetectadas[] = [
                'codigo'     => $codigoComum,
                'localidade' => $localidadeTexto,
                'comum_id'   => $comumId,
                'existe'     => $comumId > 0,
            ];
        }

        // ── Pré-carregar produtos por-comum (Identity Map) ──
        $produtosPorComum      = [];
        $dependenciasPorComum  = [];

        foreach ($mapaCodigoParaComumId as $codigoComum => $comumId) {
            if ($comumId > 0 && !isset($produtosPorComum[$comumId])) {
                $produtosPorComum[$comumId]     = $this->carregarProdutosDoComum($comumId);
                $dependenciasPorComum[$comumId] = $this->carregarDependencias($comumId);
            }
        }

        if ($comumIdFallback > 0 && !isset($produtosPorComum[$comumIdFallback])) {
            $produtosPorComum[$comumIdFallback]     = $this->carregarProdutosDoComum($comumIdFallback);
            $dependenciasPorComum[$comumIdFallback] = $this->carregarDependencias($comumIdFallback);
        }

        $tiposBens = $this->carregarTiposBens();

        $registros = [];
        $resumo    = [
            'total'         => 0,
            'novos'         => 0,
            'atualizar'     => 0,
            'sem_alteracao' => 0,
            'erros'         => 0,
            'exclusoes'     => 0,
        ];

        $codigosPorComum = []; // comumId => [CODIGO_UPPER => true]

        foreach ($linhas as $idx => $linha) {
            $resumo['total']++;

            try {
                $codigoComum   = $linha['codigo_comum'] ?? '';
                $comumIdLinha  = $mapaCodigoParaComumId[$codigoComum] ?? $comumIdFallback;

                $codigoRastr = strtoupper(trim($linha['codigo'] ?? ''));
                if ($comumIdLinha > 0 && $codigoRastr !== '') {
                    $codigosPorComum[$comumIdLinha][$codigoRastr] = true;
                }

                $produtosExistentes = $produtosPorComum[$comumIdLinha] ?? [];
                $dependencias       = $dependenciasPorComum[$comumIdLinha] ?? [];

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
                    'linha_csv'    => $linha['_linha_original'] ?? ($idx + 1),
                    'status'       => 'erro',
                    'acao_sugerida' => self::ACAO_PULAR,
                    'erro'         => $e->getMessage(),
                    'dados_csv'    => $linha,
                    'dados_db'     => null,
                    'diferencas'   => [],
                ];
            }
        }

        // ── Detectar produtos do banco que NÃO estão no CSV (candidatos a exclusão) ──
        foreach ($mapaCodigoParaComumId as $codigoComumExcl => $comumIdExcl) {
            if ($comumIdExcl <= 0) continue;

            $produtosDb   = $produtosPorComum[$comumIdExcl] ?? [];
            $codigosNoCSV = $codigosPorComum[$comumIdExcl] ?? [];

            foreach ($produtosDb as $codigoUpper => $produto) {
                if ($codigoUpper === '') continue;
                if (isset($codigosNoCSV[$codigoUpper])) continue;
                if (($produto['ativo'] ?? 1) == 0) continue;

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
                    'id_produto' => $produto['id_produto'],
                ];
            }
        }

        return [
            'resumo'           => $resumo,
            'registros'        => $registros,
            'comuns_detectadas' => $comunsDetectadas,
        ];
    }

    // ─── LEITURA DO CSV ───

    /**
     * Lê o CSV delegando para CsvReader e aplica o mapeamento de colunas.
     */
    private function lerCsv(string $caminho, array $mapeamento, int $puloLinhas): array
    {
        $resultado     = $this->csvReader->lerLinhasBrutas($caminho, $puloLinhas, $mapeamento);
        $todasLinhas   = $resultado['linhas'];
        $inicioLeitura = $resultado['inicioLeitura'];

        $linhas            = [];
        $ultimaLocalidade  = '';
        $ultimoCodigoComum = '';

        for ($i = $inicioLeitura; $i < count($todasLinhas); $i++) {
            $row = $todasLinhas[$i];

            $colCodigo = $mapeamento['codigo'] ?? self::MAPEAMENTO_PADRAO['codigo'];
            $codigo    = trim((string) ($row[$colCodigo] ?? ''));

            if ($codigo === '' || $this->isLinhaMetadados($codigo)) {
                continue;
            }

            $colNome       = $mapeamento['complemento']  ?? self::MAPEAMENTO_PADRAO['complemento'];
            $colDependencia = $mapeamento['dependencia']  ?? self::MAPEAMENTO_PADRAO['dependencia'];
            $colLocalidade = $mapeamento['localidade']   ?? self::MAPEAMENTO_PADRAO['localidade'];

            $nomeCompleto = trim((string) ($row[$colNome]        ?? ''));
            $dependencia  = trim((string) ($row[$colDependencia] ?? ''));
            $localidade   = trim((string) ($row[$colLocalidade]  ?? ''));

            // Carry-forward de localidade
            if (empty($localidade) && !empty($ultimaLocalidade)) {
                $localidade  = $ultimaLocalidade;
                $codigoComum = $ultimoCodigoComum;
            } else {
                $codigoComum = $this->extrairCodigoComum($localidade);
                if (!empty($localidade)) {
                    $ultimaLocalidade  = $localidade;
                    $ultimoCodigoComum = $codigoComum;
                }
            }

            // Fallback: extrai código da comum do código do produto
            if (empty($codigoComum) && strpos($codigo, '/') !== false) {
                $partes      = explode('/', $codigo, 2);
                $codigoComum = trim($partes[0]);
            }

            $parsed        = $this->nomeParser->parsearNome($nomeCompleto);
            $tipoBemCodigo = $parsed['tipo_bem_codigo'] ?: '';
            $bem           = $parsed['bem'] ?: $nomeCompleto;
            $complemento   = $parsed['complemento'] ?: '';

            $linhas[] = [
                'codigo'          => $codigo,
                'tipo_bem_codigo' => $tipoBemCodigo,
                'bem'             => $bem,
                'complemento'     => $complemento,
                'dependencia'     => strtoupper($dependencia),
                'localidade'      => $localidade,
                'codigo_comum'    => $codigoComum,
                'nome_original'   => $nomeCompleto,
                'quantidade'      => 1,
                '_linha_original' => $i + 1,
            ];
        }

        return $linhas;
    }

    /**
     * Verifica se uma linha é metadado/subtotal (não é dado de produto).
     */
    private function isLinhaMetadados(string $codigo): bool
    {
        $codigoNorm = strtolower(trim($codigo));

        $palavrasMetadados = [
            'total', 'subtotal', 'soma', 'resumo', 'grupo',
            'relatório', 'relatorio', 'congregação', 'congregacao',
            'dependência', 'dependencia', 'página', 'pagina', 'folha',
        ];

        foreach ($palavrasMetadados as $palavra) {
            if (str_contains($codigoNorm, $palavra)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extrai o código da comum da coluna "Localidade".
     * Formato: "BR 09-0038" → "09-0038"
     */
    private function extrairCodigoComum(string $localidade): string
    {
        if (empty($localidade)) {
            return '';
        }

        return trim((string) preg_replace('/[^0-9\-]/', '', $localidade));
    }

    // ─── CONFIGURAÇÕES ───

    private function carregarConfiguracoes(): array
    {
        try {
            $stmt   = $this->conexao->query("SELECT * FROM configuracoes LIMIT 1");
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            return $config ?: $this->configuracoesPadrao();
        } catch (Exception $e) {
            return $this->configuracoespadrao();
        }
    }

    private function configuracoesPadrao(): array
    {
        return [
            'pulo_linhas'       => (string) self::PULO_LINHAS_PADRAO,
            'mapeamento_colunas' => 'codigo=A;complemento=D;dependencia=P;localidade=K',
        ];
    }

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

    private function letraParaIndice(string $letra): int
    {
        $letra  = strtoupper($letra);
        $indice = 0;

        for ($i = 0; $i < strlen($letra); $i++) {
            $indice = $indice * 26 + (ord($letra[$i]) - ord('A') + 1);
        }

        return $indice - 1;
    }

    // ─── ANÁLISE E DIFF ───

    private function analisarLinha(
        array $dadosCsv,
        array $produtosExistentes,
        array $tiposBens,
        array $dependencias,
        int $comumId
    ): array {
        $codigo              = $dadosCsv['codigo'] ?? '';
        $tipoBemCodigo       = $dadosCsv['tipo_bem_codigo'] ?? '';
        $bem                 = $dadosCsv['bem'] ?? '';
        $complemento         = $dadosCsv['complemento'] ?? '';
        $dependenciaDescricao = $dadosCsv['dependencia'] ?? '';

        if (empty($codigo)) {
            throw new Exception('Código vazio na linha');
        }

        $tipoBemDesc = $tiposBens[$tipoBemCodigo] ?? ($tipoBemCodigo !== '' ? 'Tipo ' . $tipoBemCodigo : '');
        $depDescNorm = trim(strtoupper($dependenciaDescricao));

        $dadosNormalizados = [
            'codigo'                => $codigo,
            'tipo_bem_codigo'       => $tipoBemCodigo,
            'tipo_bem_descricao'    => $tipoBemDesc,
            'bem'                   => $bem,
            'complemento'           => $complemento,
            'dependencia_descricao' => $depDescNorm ?: $dependenciaDescricao,
            'codigo_comum'          => $dadosCsv['codigo_comum'] ?? '',
            'localidade'            => $dadosCsv['localidade'] ?? '',
            'nome_original'         => $dadosCsv['nome_original'] ?? $bem,
        ];

        $chave     = strtoupper(trim($codigo));
        $produtoDb = $produtosExistentes[$chave] ?? null;

        if (!$produtoDb) {
            return [
                'status'     => self::STATUS_NOVO,
                'dados_csv'  => $dadosNormalizados,
                'dados_db'   => null,
                'diferencas' => [],
                'id_produto' => null,
            ];
        }

        $diferencas = [];

        if (($produtoDb['ativo'] ?? 1) == 0) {
            $diferencas['ativo'] = ['antes' => 'inativo', 'depois' => 'ativo'];
        }

        if (trim($produtoDb['bem'] ?? '') !== trim($bem)) {
            $diferencas['bem'] = ['antes' => $produtoDb['bem'], 'depois' => $bem];
        }

        if (trim($produtoDb['complemento'] ?? '') !== trim($complemento)) {
            $diferencas['complemento'] = ['antes' => $produtoDb['complemento'], 'depois' => $complemento];
        }

        $depDbDesc = trim(strtoupper($produtoDb['dependencia_descricao'] ?? ''));
        if ($depDbDesc !== $depDescNorm) {
            $diferencas['dependencia'] = [
                'antes'  => $produtoDb['dependencia_descricao'] ?? '',
                'depois' => $dependenciaDescricao,
            ];
        }

        $status = empty($diferencas) ? self::STATUS_SEM_ALTERACAO : self::STATUS_ATUALIZAR;

        return [
            'status'     => $status,
            'dados_csv'  => $dadosNormalizados,
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

    private function buscarComumPorCodigo(string $codigoComum): int
    {
        if (empty($codigoComum)) {
            return 0;
        }

        $stmt = $this->conexao->prepare("SELECT id FROM comuns WHERE codigo = :codigo LIMIT 1");
        $stmt->execute([':codigo' => $codigoComum]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['id'] : 0;
    }

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
            $chave       = strtoupper(trim((string) ($row['codigo'] ?? '')));
            $mapa[$chave] = $row;
        }

        return $mapa;
    }

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

    private function carregarDependencias(int $comumId): array
    {
        try {
            $stmt = $this->conexao->prepare(
                "SELECT id, descricao FROM dependencias WHERE comum_id = :comum_id"
            );
            $stmt->execute([':comum_id' => $comumId]);
        } catch (Exception $e) {
            $stmt = $this->conexao->query("SELECT id, descricao FROM dependencias");
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapa = [];
        foreach ($rows as $row) {
            $mapa[strtoupper(trim((string) ($row['descricao'] ?? '')))] = $row['id'];
        }

        return $mapa;
    }

    // ─── PERSISTÊNCIA DA ANÁLISE (delegada para AnaliseStorage) ───

    public function salvarAnalise(int $importacaoId, array $analise): string
    {
        return $this->analiseStorage->salvarAnalise($importacaoId, $analise);
    }

    public function carregarAnalise(int $importacaoId): ?array
    {
        return $this->analiseStorage->carregarAnalise($importacaoId);
    }

    public function limparAnalise(int $importacaoId): void
    {
        $this->analiseStorage->limparAnalise($importacaoId);
    }
}

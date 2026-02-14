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
     * Compara cada linha do CSV com os dados existentes no banco.
     */
    public function analisar(string $caminhoArquivo, int $comumId): array
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

        // Pré-carregar dados do banco (evita N+1 queries — Identity Map)
        $produtosExistentes = $this->carregarProdutosDoComum($comumId);
        $tiposBens = $this->carregarTiposBens();
        $dependencias = $this->carregarDependencias($comumId);

        $registros = [];
        $resumo = [
            'total' => 0,
            'novos' => 0,
            'atualizar' => 0,
            'sem_alteracao' => 0,
            'erros' => 0,
        ];

        foreach ($linhas as $idx => $linha) {
            $resumo['total']++;

            try {
                $registro = $this->analisarLinha($linha, $produtosExistentes, $tiposBens, $dependencias, $comumId);
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

        return [
            'resumo' => $resumo,
            'registros' => $registros,
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

            $nomeCompleto = trim((string) ($row[$colNome] ?? ''));
            $dependencia = trim((string) ($row[$colDependencia] ?? ''));
            $localidade = trim((string) ($row[$colLocalidade] ?? ''));

            // Parsear o campo "Nome" → tipo_bem_codigo + descricao + bem + complemento
            $dadosParsed = $this->parsearNome($nomeCompleto);

            $linhas[] = [
                'codigo' => $codigo,
                'descricao_completa' => $nomeCompleto,
                'tipo_bem_codigo' => (string) $dadosParsed['tipo_bem_codigo'],
                'bem' => $dadosParsed['bem'],
                'complemento' => $dadosParsed['complemento'],
                'dependencia' => strtoupper($dependencia),
                'localidade' => $localidade,
                '_linha_original' => $i + 1, // 1-indexed para o usuário
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
     * Formato esperado: "TIPO_CODE - TIPO_DESC BEM COMPLEMENTO"
     * Exemplo: "70 - REFORMA EDIFICACAO E ALVENARIA REFORMA"
     */
    private function parsearNome(string $nome): array
    {
        $resultado = [
            'tipo_bem_codigo' => '',
            'descricao_apos_tipo' => $nome,
            'bem' => $nome,
            'complemento' => '',
        ];

        if (empty($nome)) {
            return $resultado;
        }

        // Tenta extrair código tipo_bem: "70 - ..." ou "4 - ..."
        if (preg_match('/^\s*(\d{1,3})(?:[\.,]\d+)?\s*[\-–—]\s*(.+)$/u', $nome, $m)) {
            $resultado['tipo_bem_codigo'] = $m[1];
            $textoAposCodigo = trim($m[2]);
            $resultado['descricao_apos_tipo'] = $textoAposCodigo;

            // O texto após o tipo é: "TIPO_DESC BEM COMPLEMENTO"
            // Tentamos separar bem e complemento por " - " se existir
            if (preg_match('/^(.+?)\s+\-\s+(.+)$/u', $textoAposCodigo, $parts)) {
                $resultado['bem'] = trim($parts[1]);
                $resultado['complemento'] = trim($parts[2]);
            } else {
                // Sem separador, tudo é "bem"
                $resultado['bem'] = $textoAposCodigo;
                $resultado['complemento'] = '';
            }
        }

        return $resultado;
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
        $descricaoCompleta = $dadosCsv['descricao_completa'] ?? '';
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
            'descricao_completa' => $descricaoCompleta,
            'tipo_bem_codigo' => $tipoBemCodigo,
            'tipo_bem_descricao' => $tipoBemDesc,
            'bem' => $bem,
            'complemento' => $complemento,
            'dependencia_descricao' => $depDescNorm ?: $dependenciaDescricao,
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

        if (trim($produtoDb['descricao_completa']) !== trim($descricaoCompleta)) {
            $diferencas['descricao_completa'] = [
                'antes' => $produtoDb['descricao_completa'],
                'depois' => $descricaoCompleta,
            ];
        }

        if ((string) ($produtoDb['tipo_bem_codigo'] ?? '') != $tipoBemCodigo) {
            $diferencas['tipo_bem'] = [
                'antes' => ($produtoDb['tipo_bem_codigo'] ?? '') . ' - ' . ($produtoDb['tipo_bem_descricao'] ?? ''),
                'depois' => $tipoBemCodigo . ' - ' . $tipoBemDesc,
            ];
        }

        if (trim($produtoDb['bem'] ?? '') !== trim($bem)) {
            $diferencas['bem'] = [
                'antes' => $produtoDb['bem'] ?? '',
                'depois' => $bem,
            ];
        }

        if (trim($produtoDb['complemento'] ?? '') !== trim($complemento)) {
            $diferencas['complemento'] = [
                'antes' => $produtoDb['complemento'] ?? '',
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
                'id_produto' => $produtoDb['id_produto'],
                'codigo' => $produtoDb['codigo'],
                'descricao_completa' => $produtoDb['descricao_completa'],
                'tipo_bem' => ($produtoDb['tipo_bem_codigo'] ?? '') . ' - ' . ($produtoDb['tipo_bem_descricao'] ?? ''),
                'bem' => $produtoDb['bem'] ?? '',
                'complemento' => $produtoDb['complemento'] ?? '',
                'dependencia' => $produtoDb['dependencia_descricao'] ?? '',
            ],
            'diferencas' => $diferencas,
            'id_produto' => $produtoDb['id_produto'],
        ];
    }

    // ─── CARREGAMENTO DO BANCO (Identity Map) ───

    /**
     * Pré-carrega TODOS os produtos do comum indexados por código uppercase.
     */
    private function carregarProdutosDoComum(int $comumId): array
    {
        $sql = "SELECT p.id_produto, p.codigo, p.descricao_completa, p.bem, p.complemento,
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

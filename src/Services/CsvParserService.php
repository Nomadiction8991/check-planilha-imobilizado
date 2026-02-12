<?php

namespace App\Services;

use App\Core\ConnectionManager;
use PDO;
use Exception;

/**
 * CsvParserService — Serviço moderno para parsing e análise de CSV.
 * 
 * Responsabilidades:
 *  - Ler CSV com PhpSpreadsheet (suporte a encoding, delimitadores)
 *  - Analisar cada linha comparando com dados existentes no banco
 *  - Classificar registros: NOVO, ATUALIZAR, SEM_ALTERACAO
 *  - Detectar diferenças campo a campo (diff)
 *  - Salvar análise em JSON para a tela de preview
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

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
    }

    /**
     * Analisa um CSV e retorna resultado completo de análise.
     * Compara cada linha do CSV com os dados existentes no banco.
     *
     * @param string $caminhoArquivo Caminho absoluto do CSV
     * @param int    $comumId        ID do comum ativo
     * @return array Resultado da análise
     */
    public function analisar(string $caminhoArquivo, int $comumId): array
    {
        if (!file_exists($caminhoArquivo)) {
            throw new Exception('Arquivo não encontrado: ' . $caminhoArquivo);
        }

        $linhas = $this->lerCsv($caminhoArquivo);

        if (empty($linhas)) {
            throw new Exception('Arquivo CSV vazio ou sem dados válidos');
        }

        // Pré-carregar todos os produtos existentes do comum (evita N+1 queries)
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
                $registro['linha_csv'] = $idx + 1; // 1-based para o usuário

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
                    'linha_csv' => $idx + 1,
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

    /**
     * Lê o CSV usando PhpSpreadsheet para suporte robusto a encoding e formatos.
     */
    private function lerCsv(string $caminho): array
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        $reader->setInputEncoding('UTF-8');
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');

        // Tenta detectar delimitador automaticamente
        $amostra = file_get_contents($caminho, false, null, 0, 4096);
        if ($amostra !== false) {
            $contVirgula = substr_count($amostra, ',');
            $contPontoVirgula = substr_count($amostra, ';');
            $contTab = substr_count($amostra, "\t");

            if ($contPontoVirgula > $contVirgula && $contPontoVirgula > $contTab) {
                $reader->setDelimiter(';');
            } elseif ($contTab > $contVirgula && $contTab > $contPontoVirgula) {
                $reader->setDelimiter("\t");
            }

            // Detecta encoding
            $encoding = mb_detect_encoding($amostra, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $reader->setInputEncoding($encoding);
            }
        }

        $spreadsheet = $reader->load($caminho);
        $sheet = $spreadsheet->getActiveSheet();
        $dados = $sheet->toArray(null, true, true, false);

        if (empty($dados)) {
            return [];
        }

        // Primeira linha = cabeçalho
        $cabecalho = array_map('trim', array_map('strtolower', $dados[0]));

        $linhas = [];
        for ($i = 1; $i < count($dados); $i++) {
            $row = $dados[$i];

            // Ignora linhas totalmente vazias
            $temDado = false;
            foreach ($row as $cel) {
                if ($cel !== null && trim((string)$cel) !== '') {
                    $temDado = true;
                    break;
                }
            }
            if (!$temDado) continue;

            // Mapeia coluna → valor usando cabeçalho
            $registro = [];
            foreach ($cabecalho as $colIdx => $nomeColuna) {
                if ($nomeColuna !== '') {
                    $registro[$nomeColuna] = isset($row[$colIdx]) ? trim((string)$row[$colIdx]) : '';
                }
            }

            $linhas[] = $registro;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $linhas;
    }

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
        $descricaoCompleta = $dadosCsv['descricao'] ?? '';
        $tipoBemCodigo = $dadosCsv['tipo_bem'] ?? '';
        $bem = $dadosCsv['bem'] ?? '';
        $complemento = $dadosCsv['complemento'] ?? '';
        $dependenciaDescricao = $dadosCsv['dependencia'] ?? '';

        if (empty($codigo)) {
            throw new Exception('Código vazio na linha');
        }

        // Resolve tipo_bem e dependência dos nomes para informação
        $tipoBemDesc = $tiposBens[$tipoBemCodigo] ?? 'Tipo ' . $tipoBemCodigo;
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

        // Verifica se produto existe no banco
        $chave = strtoupper(trim($codigo));
        $produtoDb = $produtosExistentes[$chave] ?? null;

        if (!$produtoDb) {
            // NOVO — não existe no banco
            return [
                'status' => self::STATUS_NOVO,
                'dados_csv' => $dadosNormalizados,
                'dados_db' => null,
                'diferencas' => [],
                'id_produto' => null,
            ];
        }

        // EXISTENTE — calcula diferenças
        $diferencas = [];

        if ($produtoDb['descricao_completa'] !== $descricaoCompleta) {
            $diferencas['descricao_completa'] = [
                'antes' => $produtoDb['descricao_completa'],
                'depois' => $descricaoCompleta,
            ];
        }

        if ($produtoDb['tipo_bem_codigo'] != $tipoBemCodigo) {
            $diferencas['tipo_bem'] = [
                'antes' => $produtoDb['tipo_bem_codigo'] . ' - ' . ($produtoDb['tipo_bem_descricao'] ?? ''),
                'depois' => $tipoBemCodigo . ' - ' . $tipoBemDesc,
            ];
        }

        if ($produtoDb['bem'] !== $bem) {
            $diferencas['bem'] = [
                'antes' => $produtoDb['bem'],
                'depois' => $bem,
            ];
        }

        if (($produtoDb['complemento'] ?? '') !== $complemento) {
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
                'tipo_bem' => $produtoDb['tipo_bem_codigo'] . ' - ' . ($produtoDb['tipo_bem_descricao'] ?? ''),
                'bem' => $produtoDb['bem'],
                'complemento' => $produtoDb['complemento'] ?? '',
                'dependencia' => $produtoDb['dependencia_descricao'] ?? '',
            ],
            'diferencas' => $diferencas,
            'id_produto' => $produtoDb['id_produto'],
        ];
    }

    /**
     * Pré-carrega TODOS os produtos do comum indexados por código (uppercase).
     * Evita N+1 queries — padrão Identity Map.
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
            $chave = strtoupper(trim($row['codigo']));
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
            $mapa[$row['codigo']] = $row['descricao'];
        }

        return $mapa;
    }

    /**
     * Carrega dependências do comum indexadas por descrição (uppercase).
     */
    private function carregarDependencias(int $comumId): array
    {
        $stmt = $this->conexao->prepare("SELECT id, descricao FROM dependencias WHERE comum_id = :comum_id");
        $stmt->execute([':comum_id' => $comumId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapa = [];
        foreach ($rows as $row) {
            $mapa[strtoupper(trim($row['descricao']))] = $row['id'];
        }

        return $mapa;
    }

    /**
     * Salva resultado da análise como JSON no storage/tmp.
     * Retorna o caminho do arquivo JSON.
     */
    public function salvarAnalise(int $importacaoId, array $analise): string
    {
        $dir = __DIR__ . '/../../storage/tmp';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $caminho = $dir . '/analise_' . $importacaoId . '.json';

        $json = json_encode($analise, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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

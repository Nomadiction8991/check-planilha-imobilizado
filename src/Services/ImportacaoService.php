<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ImportacaoRepository;
use App\Core\ConnectionManager;
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

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
        $this->importacaoRepo = new ImportacaoRepository($this->conexao);
    }

    /**
     * PASSO 1: Registra a importação no banco de dados.
     */
    public function iniciarImportacao(int $usuarioId, int $comumId, string $arquivoNome, string $arquivoCaminho): int
    {
        $totalLinhas = $this->contarLinhasArquivo($arquivoCaminho);

        $dados = [
            'usuario_id' => $usuarioId,
            'comum_id' => $comumId,
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

        $this->importacaoRepo->atualizar($importacaoId, [
            'status' => 'processando',
            'iniciada_em' => date('Y-m-d H:i:s')
        ]);

        $resultado = [
            'sucesso' => 0,
            'erro' => 0,
            'pulados' => 0,
            'excluidos' => 0,
            'erros' => []
        ];

        $comumId = $importacao['comum_id'];
        $registros = $analise['registros'] ?? [];

        // Filtra apenas os que têm ação definida
        $registrosParaProcessar = [];
        foreach ($registros as $registro) {
            $linhaCsv = $registro['linha_csv'];
            $acao = $acoes[$linhaCsv] ?? CsvParserService::ACAO_PULAR;

            if ($acao === CsvParserService::ACAO_PULAR) {
                $resultado['pulados']++;
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
                    $resultadoLote = $this->processarLoteComAcoes($lote, $comumId);
                    $this->acumularResultado($resultado, $resultadoLote);

                    $processados += count($lote);
                    $porcentagem = $totalParaProcessar > 0
                        ? ($processados / $totalParaProcessar) * 100
                        : 100;

                    $this->importacaoRepo->atualizar($importacaoId, [
                        'linhas_processadas' => $processados,
                        'linhas_sucesso' => $resultado['sucesso'],
                        'linhas_erro' => $resultado['erro'],
                        'porcentagem' => round($porcentagem, 2)
                    ]);

                    $lote = [];
                    gc_collect_cycles();
                }
            }

            // Lote restante
            if (!empty($lote)) {
                $resultadoLote = $this->processarLoteComAcoes($lote, $comumId);
                $this->acumularResultado($resultado, $resultadoLote);
                $processados += count($lote);
            }

            $this->importacaoRepo->atualizar($importacaoId, [
                'linhas_processadas' => $processados,
                'linhas_sucesso' => $resultado['sucesso'],
                'linhas_erro' => $resultado['erro'],
                'porcentagem' => 100,
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

        return $resultado;
    }

    /**
     * Processa um lote de registros com ações definidas pelo usuário.
     */
    private function processarLoteComAcoes(array $lote, int $comumId): array
    {
        $resultado = [
            'sucesso' => 0,
            'erro' => 0,
            'excluidos' => 0,
            'erros' => []
        ];

        $this->conexao->beginTransaction();

        try {
            foreach ($lote as $item) {
                $registro = $item['registro'];
                $acao = $item['acao'];

                try {
                    if ($acao === CsvParserService::ACAO_EXCLUIR) {
                        if (!empty($registro['id_produto'])) {
                            $this->desativarProduto($registro['id_produto']);
                            $resultado['excluidos']++;
                        }
                        $resultado['sucesso']++;
                    } elseif ($acao === CsvParserService::ACAO_IMPORTAR) {
                        $this->processarRegistro($registro, $comumId);
                        $resultado['sucesso']++;
                    }
                } catch (Exception $e) {
                    $resultado['erro']++;
                    $resultado['erros'][] = [
                        'linha' => $registro['linha_csv'] ?? 0,
                        'mensagem' => $e->getMessage()
                    ];
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
     * Processa um registro individual (NOVO ou ATUALIZAR).
     */
    private function processarRegistro(array $registro, int $comumId): void
    {
        $dadosCsv = $registro['dados_csv'];

        $codigo = $dadosCsv['codigo'] ?? '';
        $descricaoCompleta = $dadosCsv['descricao_completa'] ?? '';
        $tipoBemCodigo = $dadosCsv['tipo_bem_codigo'] ?? '';
        $bem = $dadosCsv['bem'] ?? '';
        $complemento = $dadosCsv['complemento'] ?? '';
        $dependenciaDescricao = $dadosCsv['dependencia_descricao'] ?? '';
        $bemIdentificado = $dadosCsv['bem_identificado'] ?? true;
        $nomePlanilha = $dadosCsv['nome_original'] ?? $descricaoCompleta;

        $tipoBemId = $this->buscarOuCriarTipoBem($tipoBemCodigo);
        $dependenciaId = $this->buscarOuCriarDependencia($dependenciaDescricao, $comumId);

        if ($registro['status'] === CsvParserService::STATUS_ATUALIZAR && !empty($registro['id_produto'])) {
            $this->atualizarProduto($registro['id_produto'], [
                'descricao_completa' => $descricaoCompleta,
                'descricao_velha' => $descricaoCompleta,
                'tipo_bem_id' => $tipoBemId,
                'bem' => $bem,
                'complemento' => $complemento,
                'dependencia_id' => $dependenciaId,
                'bem_identificado' => $bemIdentificado ? 1 : 0,
                'nome_planilha' => $nomePlanilha,
            ]);
        } else {
            $this->criarProduto([
                'comum_id' => $comumId,
                'codigo' => $codigo,
                'descricao_completa' => $descricaoCompleta,
                'descricao_velha' => $descricaoCompleta,
                'editado_descricao_completa' => $descricaoCompleta,
                'tipo_bem_id' => $tipoBemId,
                'editado_tipo_bem_id' => $tipoBemId,
                'bem' => $bem,
                'editado_bem' => $bem,
                'complemento' => $complemento,
                'editado_complemento' => $complemento,
                'dependencia_id' => $dependenciaId,
                'editado_dependencia_id' => $dependenciaId,
                'novo' => 0,
                'checado' => 0,
                'editado' => 0,
                'imprimir_etiqueta' => 0,
                'imprimir_14_1' => 0,
                'observacao' => '',
                'ativo' => 1,
                'bem_identificado' => $bemIdentificado ? 1 : 0,
                'nome_planilha' => $nomePlanilha,
            ]);
        }
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

    private function buscarOuCriarTipoBem(string $codigo): int
    {
        $stmt = $this->conexao->prepare("SELECT id FROM tipos_bens WHERE codigo = :codigo");
        $stmt->execute([':codigo' => $codigo]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            return (int) $resultado['id'];
        }

        $stmt = $this->conexao->prepare("INSERT INTO tipos_bens (codigo, descricao) VALUES (:codigo, :descricao)");
        $stmt->execute([
            ':codigo' => $codigo,
            ':descricao' => 'Tipo ' . $codigo
        ]);

        return (int) $this->conexao->lastInsertId();
    }

    /**
     * Busca ou cria uma comum pelo código extraído da localidade.
     * Código extraído: "BR 09-0038" → "09-0038"
     */
    private function buscarOuCriarComum(string $codigoComum): int
    {
        if (empty($codigoComum)) {
            throw new Exception('Código da comum vazio — não é possível identificar a comum');
        }

        // Buscar comum pelo código
        $stmt = $this->conexao->prepare("SELECT id FROM comums WHERE codigo = :codigo LIMIT 1");
        $stmt->execute([':codigo' => $codigoComum]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            return (int) $resultado['id'];
        }

        // Comum não encontrada → criar automaticamente
        $descricaoComum = 'Comum ' . $codigoComum;
        
        $stmt = $this->conexao->prepare("INSERT INTO comums (codigo, descricao) VALUES (:codigo, :descricao)");
        $stmt->execute([
            ':codigo' => $codigoComum,
            ':descricao' => $descricaoComum
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

    private function contarLinhasArquivo(string $caminho): int
    {
        $arquivo = fopen($caminho, 'r');
        $linhas = 0;

        fgets($arquivo);

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

    /**
     * Acumula resultados de um lote no resultado geral.
     */
    private function acumularResultado(array &$resultado, array $resultadoLote): void
    {
        $resultado['sucesso'] += $resultadoLote['sucesso'];
        $resultado['erro'] += $resultadoLote['erro'];
        $resultado['excluidos'] += ($resultadoLote['excluidos'] ?? 0);
        $resultado['erros'] = array_merge($resultado['erros'], $resultadoLote['erros']);
    }

    // ─── Método legado para compatibilidade (processar sem preview) ───

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

        $this->importacaoRepo->atualizar($importacaoId, [
            'status' => 'processando',
            'iniciada_em' => date('Y-m-d H:i:s')
        ]);

        $resultado = [
            'sucesso' => 0,
            'erro' => 0,
            'erros' => []
        ];

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

                    $resultado['sucesso'] += $resultadoLote['sucesso'];
                    $resultado['erro'] += $resultadoLote['erro'];
                    $resultado['erros'] = array_merge($resultado['erros'], $resultadoLote['erros']);

                    $porcentagem = ($linhaAtual / $totalLinhas) * 100;
                    $this->importacaoRepo->atualizar($importacaoId, [
                        'linhas_processadas' => $linhaAtual,
                        'linhas_sucesso' => $resultado['sucesso'],
                        'linhas_erro' => $resultado['erro'],
                        'porcentagem' => round($porcentagem, 2)
                    ]);

                    $lote = [];
                    gc_collect_cycles();
                }
            }

            if (!empty($lote)) {
                $resultadoLote = $this->processarLoteLegado($lote, $cabecalho, $importacao['comum_id']);
                $resultado['sucesso'] += $resultadoLote['sucesso'];
                $resultado['erro'] += $resultadoLote['erro'];
                $resultado['erros'] = array_merge($resultado['erros'], $resultadoLote['erros']);

                $this->importacaoRepo->atualizar($importacaoId, [
                    'linhas_processadas' => $linhaAtual,
                    'linhas_sucesso' => $resultado['sucesso'],
                    'linhas_erro' => $resultado['erro'],
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

        return $resultado;
    }

    private function processarLoteLegado(array $lote, array $cabecalho, int $comumId): array
    {
        $resultado = ['sucesso' => 0, 'erro' => 0, 'erros' => []];

        $this->conexao->beginTransaction();

        try {
            foreach ($lote as $item) {
                try {
                    $this->processarLinhaLegada($item['dados'], $cabecalho, $comumId);
                    $resultado['sucesso']++;
                } catch (Exception $e) {
                    $resultado['erro']++;
                    $resultado['erros'][] = [
                        'linha' => $item['numero'],
                        'mensagem' => $e->getMessage()
                    ];
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
        $descricaoCompleta = $dados[$mapa['descricao']] ?? '';
        $tipoBemCodigo = $dados[$mapa['tipo_bem']] ?? '';
        $bem = $dados[$mapa['bem']] ?? '';
        $complemento = $dados[$mapa['complemento']] ?? '';
        $dependenciaDescricao = $dados[$mapa['dependencia']] ?? '';

        $tipoBemId = $this->buscarOuCriarTipoBem($tipoBemCodigo);
        $dependenciaId = $this->buscarOuCriarDependencia($dependenciaDescricao, $comumId);

        $stmt = $this->conexao->prepare("SELECT * FROM produtos WHERE codigo = :codigo AND comum_id = :comum_id");
        $stmt->execute([':codigo' => $codigo, ':comum_id' => $comumId]);
        $produtoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produtoExistente) {
            $this->atualizarProduto($produtoExistente['id_produto'], [
                'descricao_completa' => $descricaoCompleta,
                'descricao_velha' => $descricaoCompleta,
                'tipo_bem_id' => $tipoBemId,
                'bem' => $bem,
                'complemento' => $complemento,
                'dependencia_id' => $dependenciaId
            ]);
        } else {
            $this->criarProduto([
                'comum_id' => $comumId,
                'codigo' => $codigo,
                'descricao_completa' => $descricaoCompleta,
                'descricao_velha' => $descricaoCompleta,
                'editado_descricao_completa' => $descricaoCompleta,
                'tipo_bem_id' => $tipoBemId,
                'editado_tipo_bem_id' => $tipoBemId,
                'bem' => $bem,
                'editado_bem' => $bem,
                'complemento' => $complemento,
                'editado_complemento' => $complemento,
                'dependencia_id' => $dependenciaId,
                'editado_dependencia_id' => $dependenciaId,
                'novo' => 0,
                'checado' => 0,
                'editado' => 0,
                'imprimir_etiqueta' => 0,
                'imprimir_14_1' => 0,
                'observacao' => '',
                'ativo' => 1
            ]);
        }
    }
}

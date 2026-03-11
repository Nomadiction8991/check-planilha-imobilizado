<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ImportacaoRepository;
use PDO;
use Exception;

/**
 * LegacyImportacaoService — Fluxo legado de importação sem preview.
 *
 * Processa todas as linhas do CSV diretamente, sem a etapa de análise/diff.
 * Mantido para compatibilidade com o caminho de fallback em confirmarImportacao()
 * quando a análise JSON não está disponível.
 *
 * O fluxo recomendado é:
 *   CsvParserService::analisar() → preview → ImportacaoService::processarComAcoes()
 *
 * @see ImportacaoService::processarComAcoes()
 */
class LegacyImportacaoService
{
    private PDO $conexao;
    private ImportacaoRepository $importacaoRepo;
    private const LOTE_SIZE = 100;

    public function __construct(PDO $conexao)
    {
        $this->conexao        = $conexao;
        $this->importacaoRepo = new ImportacaoRepository($this->conexao);
    }

    /**
     * Processa todas as linhas do CSV diretamente (sem preview).
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

        $stmtDeleteErros = $this->conexao->prepare('DELETE FROM import_erros WHERE importacao_id = :id');
        $stmtDeleteErros->execute([':id' => $importacaoId]);

        $this->importacaoRepo->atualizar($importacaoId, [
            'status'      => 'processando',
            'iniciada_em' => date('Y-m-d H:i:s'),
        ]);

        $resultado = ['sucesso' => 0, 'erro' => 0, 'erros' => []];

        try {
            $arquivo = fopen($importacao['arquivo_caminho'], 'r');

            if (!$arquivo) {
                throw new Exception('Não foi possível abrir o arquivo');
            }

            $cabecalho  = fgetcsv($arquivo, 0, ',');
            $linhaAtual = 0;
            $lote       = [];
            $totalLinhas = $importacao['total_linhas'];

            while (($linha = fgetcsv($arquivo, 0, ',')) !== false) {
                $linhaAtual++;

                $lote[] = ['numero' => $linhaAtual, 'dados' => $linha];

                if (count($lote) >= self::LOTE_SIZE) {
                    $resultadoLote = $this->processarLote($lote, $cabecalho, $importacao['comum_id']);

                    $resultado['sucesso'] += $resultadoLote['sucesso'];
                    $resultado['erro']    += $resultadoLote['erro'];
                    $resultado['erros']    = array_merge($resultado['erros'], $resultadoLote['erros']);

                    $porcentagem = ($linhaAtual / $totalLinhas) * 100;
                    $this->importacaoRepo->atualizar($importacaoId, [
                        'linhas_processadas' => $linhaAtual,
                        'linhas_sucesso'     => $resultado['sucesso'],
                        'linhas_erro'        => $resultado['erro'],
                        'porcentagem'        => round($porcentagem, 2),
                    ]);

                    $lote = [];
                    gc_collect_cycles();
                }
            }

            if (!empty($lote)) {
                $resultadoLote = $this->processarLote($lote, $cabecalho, $importacao['comum_id']);
                $resultado['sucesso'] += $resultadoLote['sucesso'];
                $resultado['erro']    += $resultadoLote['erro'];
                $resultado['erros']    = array_merge($resultado['erros'], $resultadoLote['erros']);

                $this->importacaoRepo->atualizar($importacaoId, [
                    'linhas_processadas' => $linhaAtual,
                    'linhas_sucesso'     => $resultado['sucesso'],
                    'linhas_erro'        => $resultado['erro'],
                    'porcentagem'        => 100,
                ]);
            }

            fclose($arquivo);

            $this->importacaoRepo->atualizar($importacaoId, [
                'status'       => 'concluida',
                'concluida_em' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            $this->importacaoRepo->atualizar($importacaoId, [
                'status'        => 'erro',
                'mensagem_erro' => $e->getMessage(),
                'concluida_em'  => date('Y-m-d H:i:s'),
            ]);
            throw $e;
        }

        return $resultado;
    }

    // ─── Lote e linha ───

    private function processarLote(array $lote, array $cabecalho, int $comumId): array
    {
        $resultado = ['sucesso' => 0, 'erro' => 0, 'erros' => []];

        $this->conexao->beginTransaction();

        try {
            foreach ($lote as $item) {
                try {
                    $this->processarLinha($item['dados'], $cabecalho, $comumId);
                    $resultado['sucesso']++;
                } catch (Exception $e) {
                    $resultado['erro']++;
                    $resultado['erros'][] = [
                        'linha'    => $item['numero'],
                        'mensagem' => $e->getMessage(),
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

    private function processarLinha(array $dados, array $cabecalho, int $comumId): void
    {
        $mapa = array_flip($cabecalho);

        $codigo              = $dados[$mapa['codigo']]      ?? '';
        $tipoBemCodigo       = $dados[$mapa['tipo_bem']]    ?? '';
        $bem                 = $dados[$mapa['bem']]         ?? '';
        $complemento         = $dados[$mapa['complemento']] ?? '';
        $dependenciaDescricao = $dados[$mapa['dependencia']] ?? '';

        $tipoBemId     = $this->buscarOuCriarTipoBem($tipoBemCodigo);
        $dependenciaId = $this->buscarOuCriarDependencia($dependenciaDescricao, $comumId);

        $stmt = $this->conexao->prepare(
            "SELECT * FROM produtos WHERE codigo = :codigo AND comum_id = :comum_id"
        );
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

    // ─── Auxiliares (duplicados do ImportacaoService para manter independência) ───

    private function buscarOuCriarTipoBem(string $codigo): int
    {
        $stmt = $this->conexao->prepare("SELECT id FROM tipos_bens WHERE codigo = :codigo");
        $stmt->execute([':codigo' => $codigo]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            return (int) $resultado['id'];
        }

        $stmt = $this->conexao->prepare(
            "INSERT INTO tipos_bens (codigo, descricao) VALUES (:codigo, :descricao)"
        );
        $stmt->execute([':codigo' => $codigo, ':descricao' => 'Tipo ' . $codigo]);

        return (int) $this->conexao->lastInsertId();
    }

    private function buscarOuCriarDependencia(string $descricao, int $comumId): int
    {
        $descricao = trim(strtoupper($descricao));
        if (empty($descricao)) {
            $descricao = 'SEM DEPENDÊNCIA';
        }

        $stmt = $this->conexao->prepare(
            "SELECT id FROM dependencias WHERE descricao = :descricao AND comum_id = :comum_id"
        );
        $stmt->execute([':descricao' => $descricao, ':comum_id' => $comumId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            return (int) $resultado['id'];
        }

        $stmt = $this->conexao->prepare(
            "INSERT INTO dependencias (comum_id, descricao) VALUES (:comum_id, :descricao)"
        );
        $stmt->execute([':comum_id' => $comumId, ':descricao' => $descricao]);

        return (int) $this->conexao->lastInsertId();
    }

    private function atualizarProduto(int $id, array $dados): void
    {
        $colunasPermitidas = [
            'tipo_bem_id', 'bem', 'complemento', 'dependencia_id', 'importado', 'ativo',
        ];

        $dados = array_intersect_key($dados, array_flip($colunasPermitidas));

        if (empty($dados)) {
            return;
        }

        $sets   = [];
        $params = [':id' => $id];

        foreach ($dados as $campo => $valor) {
            $sets[]          = "$campo = :$campo";
            $params[":$campo"] = $valor;
        }

        $sql  = "UPDATE produtos SET " . implode(', ', $sets) . " WHERE id_produto = :id";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute($params);
    }

    private function criarProduto(array $dados): int
    {
        $campos       = array_keys($dados);
        $placeholders = array_map(fn($c) => ":$c", $campos);

        $sql  = "INSERT INTO produtos (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->conexao->prepare($sql);

        foreach ($dados as $campo => $valor) {
            $stmt->bindValue(":$campo", $valor);
        }

        $stmt->execute();
        return (int) $this->conexao->lastInsertId();
    }
}

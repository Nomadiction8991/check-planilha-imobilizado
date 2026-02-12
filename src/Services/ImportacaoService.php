<?php

namespace App\Services;

use App\Repositories\ImportacaoRepository;
use App\Core\ConnectionManager;
use PDO;
use Exception;

class ImportacaoService
{
    private ImportacaoRepository $importacaoRepo;
    private PDO $conexao;
    private const LOTE_SIZE = 100; // Processar 100 linhas por vez para atualizar progresso a cada 1%

    public function __construct(?PDO $conexao = null)
    {
        $this->conexao = $conexao ?? ConnectionManager::getConnection();
        $this->importacaoRepo = new ImportacaoRepository($this->conexao);
    }

    /**
     * Inicia uma nova importação e registra no banco
     */
    public function iniciarImportacao(int $usuarioId, int $comumId, string $arquivoNome, string $arquivoCaminho): int
    {
        // Contar total de linhas do arquivo
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
     * Processa a importação em lotes com atualização de progresso
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

        // Atualiza status para processando
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

            // Ler cabeçalho
            $cabecalho = fgetcsv($arquivo, 0, ',');

            $linhaAtual = 0;
            $lote = [];
            $totalLinhas = $importacao['total_linhas'];

            while (($linha = fgetcsv($arquivo, 0, ',')) !== false) {
                $linhaAtual++;

                // Adiciona linha ao lote
                $lote[] = [
                    'numero' => $linhaAtual,
                    'dados' => $linha
                ];

                // Processa quando atingir tamanho do lote ou fim do arquivo
                if (count($lote) >= self::LOTE_SIZE) {
                    $resultadoLote = $this->processarLote($lote, $cabecalho, $importacao['comum_id']);

                    $resultado['sucesso'] += $resultadoLote['sucesso'];
                    $resultado['erro'] += $resultadoLote['erro'];
                    $resultado['erros'] = array_merge($resultado['erros'], $resultadoLote['erros']);

                    // Atualiza progresso
                    $porcentagem = ($linhaAtual / $totalLinhas) * 100;
                    $this->importacaoRepo->atualizar($importacaoId, [
                        'linhas_processadas' => $linhaAtual,
                        'linhas_sucesso' => $resultado['sucesso'],
                        'linhas_erro' => $resultado['erro'],
                        'porcentagem' => round($porcentagem, 2)
                    ]);

                    // Limpa lote
                    $lote = [];

                    // Libera memória
                    gc_collect_cycles();
                }
            }

            // Processa lote restante
            if (!empty($lote)) {
                $resultadoLote = $this->processarLote($lote, $cabecalho, $importacao['comum_id']);

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

            // Marca como concluída
            $this->importacaoRepo->atualizar($importacaoId, [
                'status' => 'concluida',
                'concluida_em' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Marca como erro
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
     * Processa um lote de linhas
     */
    private function processarLote(array $lote, array $cabecalho, int $comumId): array
    {
        $resultado = [
            'sucesso' => 0,
            'erro' => 0,
            'erros' => []
        ];

        $this->conexao->beginTransaction();

        try {
            foreach ($lote as $item) {
                $linhaNumero = $item['numero'];
                $dados = $item['dados'];

                try {
                    $this->processarLinha($dados, $cabecalho, $comumId);
                    $resultado['sucesso']++;
                } catch (Exception $e) {
                    $resultado['erro']++;
                    $resultado['erros'][] = [
                        'linha' => $linhaNumero,
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
     * Processa uma linha individual
     */
    private function processarLinha(array $dados, array $cabecalho, int $comumId): void
    {
        // Mapeia colunas
        $mapa = array_flip($cabecalho);

        // Extrai dados
        $codigo = $dados[$mapa['codigo']] ?? '';
        $descricaoCompleta = $dados[$mapa['descricao']] ?? '';
        $tipoBemCodigo = $dados[$mapa['tipo_bem']] ?? '';
        $bem = $dados[$mapa['bem']] ?? '';
        $complemento = $dados[$mapa['complemento']] ?? '';
        $dependenciaDescricao = $dados[$mapa['dependencia']] ?? '';

        // Busca ou cria tipo de bem
        $tipoBemId = $this->buscarOuCriarTipoBem($tipoBemCodigo);

        // Busca ou cria dependência
        $dependenciaId = $this->buscarOuCriarDependencia($dependenciaDescricao, $comumId);

        // Verifica se produto já existe
        $produtoExistente = $this->buscarProdutoPorCodigo($codigo, $comumId);

        if ($produtoExistente) {
            // Atualiza produto existente
            $this->atualizarProduto($produtoExistente['id_produto'], [
                'descricao_completa' => $descricaoCompleta,
                'descricao_velha' => $descricaoCompleta, // Salva descrição original
                'tipo_bem_id' => $tipoBemId,
                'bem' => $bem,
                'complemento' => $complemento,
                'dependencia_id' => $dependenciaId
            ]);
        } else {
            // Cria novo produto
            $this->criarProduto([
                'comum_id' => $comumId,
                'codigo' => $codigo,
                'descricao_completa' => $descricaoCompleta,
                'descricao_velha' => $descricaoCompleta, // Salva descrição original
                'editado_descricao_completa' => $descricaoCompleta,
                'tipo_bem_id' => $tipoBemId,
                'editado_tipo_bem_id' => $tipoBemId,
                'bem' => $bem,
                'editado_bem' => $bem,
                'complemento' => $complemento,
                'editado_complemento' => $complemento,
                'dependencia_id' => $dependenciaId,
                'editado_dependencia_id' => $dependenciaId,
                'novo' => 1,
                'checado' => 0,
                'editado' => 0,
                'imprimir_etiqueta' => 0,
                'imprimir_14_1' => 0,
                'observacao' => '',
                'ativo' => 1
            ]);
        }
    }

    private function buscarOuCriarTipoBem(string $codigo): int
    {
        $stmt = $this->conexao->prepare("SELECT id FROM tipos_bens WHERE codigo = :codigo");
        $stmt->execute([':codigo' => $codigo]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            return (int) $resultado['id'];
        }

        // Cria novo tipo
        $stmt = $this->conexao->prepare("INSERT INTO tipos_bens (codigo, descricao) VALUES (:codigo, :descricao)");
        $stmt->execute([
            ':codigo' => $codigo,
            ':descricao' => 'Tipo ' . $codigo
        ]);

        return (int) $this->conexao->lastInsertId();
    }

    private function buscarOuCriarDependencia(string $descricao, int $comumId): int
    {
        $descricao = trim(strtoupper($descricao));

        $stmt = $this->conexao->prepare("SELECT id FROM dependencias WHERE descricao = :descricao AND comum_id = :comum_id");
        $stmt->execute([
            ':descricao' => $descricao,
            ':comum_id' => $comumId
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            return (int) $resultado['id'];
        }

        // Cria nova dependência
        $stmt = $this->conexao->prepare("INSERT INTO dependencias (comum_id, descricao) VALUES (:comum_id, :descricao)");
        $stmt->execute([
            ':comum_id' => $comumId,
            ':descricao' => $descricao
        ]);

        return (int) $this->conexao->lastInsertId();
    }

    private function buscarProdutoPorCodigo(string $codigo, int $comumId): ?array
    {
        $stmt = $this->conexao->prepare("SELECT * FROM produtos WHERE codigo = :codigo AND comum_id = :comum_id");
        $stmt->execute([
            ':codigo' => $codigo,
            ':comum_id' => $comumId
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
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

        // Pula cabeçalho
        fgets($arquivo);

        while (!feof($arquivo)) {
            if (fgets($arquivo)) {
                $linhas++;
            }
        }

        fclose($arquivo);
        return $linhas;
    }

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
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ConnectionManager;
use App\Core\Logger;
use App\Repositories\ComumRepository;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * Relatorio141Service — Serviço para geração de relatórios 14.1.
 *
 * Responsável por:
 * - Buscar dados de uma comum (CNPJ, número do relatório)
 * - Buscar produtos relacionados
 * - Renderizar template do relatório 14.1
 *
 * @since 14.0
 */
class Relatorio141Service
{
    /** @var ComumRepository Repositório de comuns */
    private ComumRepository $comumRepository;

    /** @var PDO Conexão com banco de dados */
    private PDO $pdo;

    /**
     * Construtor injetando dependências.
     *
     * @param ComumRepository $comumRepository Repositório de comuns
     * @param PDO|null $pdo Conexão PDO (opcional, usa ConnectionManager se null)
     */
    public function __construct(ComumRepository $comumRepository, ?PDO $pdo = null)
    {
        $this->comumRepository = $comumRepository;
        $this->pdo = $pdo ?? ConnectionManager::getConnection();
    }

    /**
     * Gera dados completos para o relatório 14.1.
     *
     * @param int $idComum ID da comum
     * @return array Array associativo com chaves: cnpj, numero_relatorio, casa_oracao, comum, produtos, total_produtos
     * @throws InvalidArgumentException Se a comum não for encontrada
     */
    public function gerarRelatorio(int $idComum): array
    {
        if ($idComum <= 0) {
            throw new InvalidArgumentException('ID da comum deve ser maior que zero');
        }

        $comum = $this->buscarDadosComum($idComum);

        if (!$comum) {
            Logger::warn("Tentativa de gerar relatório para comum inexistente", ['comum_id' => $idComum]);
            throw new InvalidArgumentException("Comum não encontrada: ID {$idComum}");
        }

        $produtos = $this->buscarProdutos($idComum);

        Logger::info('Relatório 14.1 gerado com sucesso', [
            'comum_id' => $idComum,
            'total_produtos' => count($produtos),
        ]);

        return [
            'cnpj' => $comum['cnpj'] ?? '',
            'numero_relatorio' => $comum['numero_relatorio'] ?? $idComum,
            'casa_oracao' => $comum['casa_oracao'] ?? '',
            'comum' => $comum['descricao'] ?? '',
            'produtos' => $produtos,
            'total_produtos' => count($produtos),
        ];
    }

    /**
     * Busca dados da comum pelo ID.
     *
     * @param int $idComum ID da comum
     * @return array|null Array com dados da comum ou null se não encontrada
     */
    private function buscarDadosComum(int $idComum): ?array
    {
        try {
            $sql = "SELECT
                        c.id,
                        c.descricao as comum,
                        c.cnpj,
                        c.codigo as numero_relatorio,
                        c.descricao as casa_oracao
                    FROM comuns c
                    WHERE c.id = :id
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException('Erro ao preparar statement para buscar comum');
            }

            $stmt->execute(['id' => $idComum]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return $resultado ?: null;
        } catch (\PDOException $e) {
            Logger::error('Erro ao buscar dados da comum', [
                'comum_id' => $idComum,
                'erro' => $e->getMessage(),
            ]);
            throw new RuntimeException("Erro ao buscar comum: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Busca produtos da comum.
     *
     * @param int $idComum ID da comum
     * @return array Array de produtos (pode estar vazio)
     */
    private function buscarProdutos(int $idComum): array
    {
        try {
            $sql = "SELECT
                        p.id_produto as id,
                        p.codigo,
                        CONCAT_WS(' ', p.bem, p.complemento) as descricao,
                        CONCAT_WS(' ', p.bem, p.complemento) as descricao_completa,
                        p.observacao as obs,
                        p.bem,
                        p.complemento,
                        p.tipo_bem_id
                    FROM produtos p
                    WHERE p.comum_id = :id_comum
                    AND p.ativo = 1
                    ORDER BY p.codigo ASC";

            $stmt = $this->pdo->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException('Erro ao preparar statement para buscar produtos');
            }

            $stmt->execute(['id_comum' => $idComum]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            Logger::error('Erro ao buscar produtos para relatório', [
                'comum_id' => $idComum,
                'erro' => $e->getMessage(),
            ]);
            throw new RuntimeException("Erro ao buscar produtos: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Renderiza o template do relatório 14.1 com os dados.
     *
     * @param int $idComum ID da comum
     * @return string HTML renderizado do relatório
     * @throws RuntimeException Se o template não for encontrado ou houver erro na renderização
     */
    public function renderizar(int $idComum): string
    {
        try {
            $dados = $this->gerarRelatorio($idComum);

            // Extrai variáveis para uso no template
            extract($dados, EXTR_SKIP);

            $templatePath = __DIR__ . '/../Views/reports/report-141-template.php';

            if (!file_exists($templatePath)) {
                Logger::error('Template de relatório não encontrado', [
                    'caminho' => $templatePath,
                ]);
                throw new RuntimeException("Template não encontrado: {$templatePath}");
            }

            if (!is_readable($templatePath)) {
                throw new RuntimeException("Template não legível: {$templatePath}");
            }

            ob_start();
            include $templatePath;
            $html = ob_get_clean();

            if ($html === false) {
                throw new RuntimeException('Erro ao renderizar template');
            }

            Logger::info('Template renderizado com sucesso', ['comum_id' => $idComum]);

            return $html;
        } catch (\Exception $e) {
            Logger::exception($e, 'Erro ao renderizar relatório 14.1', ['comum_id' => $idComum]);
            throw new RuntimeException("Erro ao renderizar relatório: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Gera dados de relatório em branco para pré-preenchimento.
     *
     * Útil para gerar templates com linhas vazias para preenchimento manual.
     *
     * @param int $numProdutos Número de linhas de produto (padrão: 10)
     * @return array Array com estrutura de relatório vazia
     */
    public function gerarEmBranco(int $numProdutos = 10): array
    {
        if ($numProdutos < 1) {
            $numProdutos = 10;
        }

        $produtos = array_fill(0, $numProdutos, [
            'codigo' => '',
            'descricao' => '',
            'obs' => '',
            'bem' => '',
            'complemento' => '',
            'tipo_bem_id' => '',
        ]);

        return [
            'cnpj' => '',
            'numero_relatorio' => '',
            'casa_oracao' => '',
            'comum' => '',
            'produtos' => $produtos,
            'total_produtos' => $numProdutos,
        ];
    }

    /**
     * Gera estatísticas dos produtos de uma comum.
     *
     * Calcula percentuais de preenchimento de campos opcionais.
     *
     * @param int $idComum ID da comum
     * @return array Array com estatísticas (total, percentuais, etc)
     * @throws RuntimeException Se houver erro ao buscar produtos
     */
    public function gerarEstatisticas(int $idComum): array
    {
        try {
            $produtos = $this->buscarProdutos($idComum);

            $estatisticas = [
                'total_produtos' => count($produtos),
                'produtos_ativos' => 0,
                'produtos_com_obs' => 0,
                'percentual_completo' => 0.0,
            ];

            foreach ($produtos as $produto) {
                $estatisticas['produtos_ativos']++;

                if (!empty($produto['obs'])) {
                    $estatisticas['produtos_com_obs']++;
                }
            }

            // Calcular percentuais
            if ($estatisticas['total_produtos'] > 0) {
                $total = $estatisticas['total_produtos'];
                $estatisticas['percentual_com_obs'] = round(
                    ($estatisticas['produtos_com_obs'] / $total) * 100,
                    2
                );
            }

            Logger::info('Estatísticas geradas com sucesso', [
                'comum_id' => $idComum,
                'total_produtos' => $estatisticas['total_produtos'],
            ]);

            return $estatisticas;
        } catch (\Exception $e) {
            Logger::exception($e, 'Erro ao gerar estatísticas', ['comum_id' => $idComum]);
            throw new RuntimeException("Erro ao gerar estatísticas: {$e->getMessage()}", 0, $e);
        }
    }
}

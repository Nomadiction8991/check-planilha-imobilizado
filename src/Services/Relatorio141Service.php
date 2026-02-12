<?php

require_once __DIR__ . '/../Repositories/ComumRepository.php';

/**
 * Relatório 14.1 Service - Geração de relatórios 14.1
 * 
 * Serviço responsável por gerar dados formatados para o template
 * do Relatório 14.1 (inventário de bens imobilizados).
 * 
 * Usa repositórios para acesso a dados seguindo SOLID.
 * 
 * @package App\Services
 * @version 1.0.0
 */
class Relatorio141Service
{
    private ComumRepository $comumRepository;
    private PDO $pdo;

    /**
     * @param ComumRepository $comumRepository Repository de comuns
     * @param PDO|null $pdo Conexão PDO (opcional, para queries customizadas)
     */
    public function __construct(ComumRepository $comumRepository, ?PDO $pdo = null)
    {
        $this->comumRepository = $comumRepository;

        if ($pdo === null) {
            require_once __DIR__ . '/../Core/ConnectionManager.php';
            $pdo = ConnectionManager::getInstance()->getConnection();
        }

        $this->pdo = $pdo;
    }

    /**
     * Gera relatório completo para uma comum específica
     * 
     * @param int $idComum ID da comum
     * @return array Dados formatados para o template
     * @throws InvalidArgumentException Se comum não encontrada
     */
    public function gerarRelatorio(int $idComum): array
    {
        // Buscar dados da comum
        $comum = $this->buscarDadosComum($idComum);

        if (!$comum) {
            throw new InvalidArgumentException("Comum não encontrada: ID {$idComum}");
        }

        // Buscar produtos da comum
        $produtos = $this->buscarProdutos($idComum);

        // Formatar dados para o template
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
     * Busca dados da comum para o relatório
     * 
     * @param int $idComum ID da comum
     * @return array|false Dados da comum ou false se não encontrada
     */
    private function buscarDadosComum(int $idComum): array|false
    {
        $sql = "SELECT 
                    c.id,
                    c.descricao as comum,
                    c.cnpj,
                    c.codigo as numero_relatorio,
                    c.descricao as casa_oracao
                FROM comuns c 
                WHERE c.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $idComum]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca produtos de uma comum
     * 
     * Retorna produtos ordenados por código.
     * 
     * @param int $idComum ID da comum
     * @return array Array de produtos
     */
    private function buscarProdutos(int $idComum): array
    {
        $sql = "SELECT 
                    p.id,
                    p.codigo,
                    p.descricao_completa as descricao,
                    p.observacao as obs,
                    p.marca,
                    p.modelo,
                    p.num_serie,
                    p.ano_fabric,
                    p.valor_estimado,
                    p.estado_conservacao,
                    p.tipo_bem_id
                FROM produtos p
                WHERE p.comum_id = :id_comum
                ORDER BY p.codigo";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_comum' => $idComum]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Renderiza template do relatório preenchido
     * 
     * @param int $idComum ID da comum
     * @return string HTML renderizado
     * @throws InvalidArgumentException Se comum não encontrada
     */
    public function renderizar(int $idComum): string
    {
        $dados = $this->gerarRelatorio($idComum);

        // Extrair variáveis para o template
        extract($dados);

        // Tentar incluir template (primeiro em src/, depois app/)
        $templatePaths = [
            __DIR__ . '/../Views/planilhas/relatorio141_template.php',
            __DIR__ . '/../../app/views/planilhas/relatorio141_template.php',
        ];

        $templateFound = false;
        foreach ($templatePaths as $templatePath) {
            if (file_exists($templatePath)) {
                ob_start();
                include $templatePath;
                return ob_get_clean();
            }
        }

        throw new RuntimeException('Template do relatório 14.1 não encontrado');
    }

    /**
     * Gera estrutura de relatório em branco para preenchimento manual
     * 
     * @param int $numProdutos Número de produtos em branco a gerar
     * @return array Estrutura de dados em branco
     */
    public function gerarEmBranco(int $numProdutos = 10): array
    {
        $produtos = array_fill(0, $numProdutos, [
            'codigo' => '',
            'descricao' => '',
            'obs' => '',
            'marca' => '',
            'modelo' => '',
            'num_serie' => '',
            'ano_fabric' => '',
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
     * Gera resumo estatístico do relatório
     * 
     * @param int $idComum ID da comum
     * @return array Estatísticas do relatório
     */
    public function gerarEstatisticas(int $idComum): array
    {
        $produtos = $this->buscarProdutos($idComum);

        $estatisticas = [
            'total_produtos' => count($produtos),
            'produtos_com_marca' => 0,
            'produtos_com_modelo' => 0,
            'produtos_com_num_serie' => 0,
            'produtos_com_ano_fabric' => 0,
            'valor_total_estimado' => 0.0,
        ];

        foreach ($produtos as $produto) {
            if (!empty($produto['marca'])) {
                $estatisticas['produtos_com_marca']++;
            }
            if (!empty($produto['modelo'])) {
                $estatisticas['produtos_com_modelo']++;
            }
            if (!empty($produto['num_serie'])) {
                $estatisticas['produtos_com_num_serie']++;
            }
            if (!empty($produto['ano_fabric'])) {
                $estatisticas['produtos_com_ano_fabric']++;
            }
            if (!empty($produto['valor_estimado'])) {
                $estatisticas['valor_total_estimado'] += (float) $produto['valor_estimado'];
            }
        }

        // Calcular percentuais
        if ($estatisticas['total_produtos'] > 0) {
            $total = $estatisticas['total_produtos'];
            $estatisticas['percentual_com_marca'] = round(($estatisticas['produtos_com_marca'] / $total) * 100, 2);
            $estatisticas['percentual_com_modelo'] = round(($estatisticas['produtos_com_modelo'] / $total) * 100, 2);
            $estatisticas['percentual_com_num_serie'] = round(($estatisticas['produtos_com_num_serie'] / $total) * 100, 2);
            $estatisticas['percentual_com_ano_fabric'] = round(($estatisticas['produtos_com_ano_fabric'] / $total) * 100, 2);
        }

        return $estatisticas;
    }
}

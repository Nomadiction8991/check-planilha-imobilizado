<?php

/**
 * Gerador de RelatÃ³rios 14.1
 * 
 * Classe helper para preencher o template do RelatÃ³rio 14.1
 * com dados da planilha e produtos automaticamente
 */

class Relatorio141Generator
{

    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Gera relatÃ³rio para uma planilha especÃ­fica
     * 
     * @param int $id_planilha ID da planilha
     * @return array Dados formatados para o template
     */
    public function gerarRelatorio($id_comum)
    {
        // Buscar dados da comum
        $planilha = $this->buscarPlanilha($id_comum);

        if (!$planilha) {
            throw new Exception("Comum nÃ£o encontrada");
        }

        // Buscar produtos da comum
        $produtos = $this->buscarProdutos($id_comum);

        // Formatar dados para o template
        return [
            'cnpj' => $planilha['cnpj'] ?? '',
            'numero_relatorio' => $planilha['numero_relatorio'] ?? $id_comum,
            'casa_oracao' => $planilha['casa_oracao'] ?? '',
            'produtos' => $produtos
        ];
    }

    /**
     * Busca dados da planilha
     */
    private function buscarPlanilha($id_planilha)
    {
        $sql = "SELECT 
                    c.descricao as comum,
                    c.cnpj,
                    c.codigo as numero_relatorio,
                    c.descricao as casa_oracao
                FROM comums c 
                WHERE c.id = :id"; // Alterado para usar 'comums' diretamente (refactor 'planilhas' -> 'comums')

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id_planilha]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca produtos da planilha
     */
    private function buscarProdutos($id_comum)
    {
        $sql = "SELECT 
                    p.codigo,
                    p.descricao_completa as descricao,
                    p.observacao as obs,
                    p.marca,
                    p.modelo,
                    p.num_serie,
                    p.ano_fabric
                FROM produtos p
                WHERE p.comum_id = :id_comum
                ORDER BY p.codigo"; // Ajustado para usar coluna comum_id

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_comum' => $id_comum]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Renderiza o template preenchido
     */
    public function renderizar($id_planilha)
    {
        $dados = $this->gerarRelatorio($id_planilha);

        // Extrair variÃ¡veis para o template
        extract($dados);

        // Incluir o template
        ob_start();
        include __DIR__ . '/../../app/views/planilhas/relatorio141_template.php';
        return ob_get_clean();
    }

    /**
     * Gera relatÃ³rio em branco para preenchimento manual
     */
    public function gerarEmBranco($num_paginas = 1)
    {
        $produtos = array_fill(0, $num_paginas, [
            'codigo' => '',
            'descricao' => '',
            'obs' => ''
        ]);

        return [
            'cnpj' => '',
            'numero_relatorio' => '',
            'casa_oracao' => '',
            'produtos' => $produtos
        ];
    }
}

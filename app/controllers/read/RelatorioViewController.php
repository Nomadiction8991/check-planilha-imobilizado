<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

class RelatorioViewController
{
    private $pdo;
    private $id_planilha;
    private $formulario;

    public function __construct($pdo, $id_planilha, $formulario)
    {
        $this->pdo = $pdo;
        $this->id_planilha = $id_planilha;
        $this->formulario = $formulario;
    }

    private function buscarDadosPlanilha()
    {
        $sql = "SELECT c.id, c.descricao as comum, c.cnpj, c.administracao, c.cidade, c.setor
                FROM comums c WHERE c.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $this->id_planilha]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function buscarProdutos()
    {
        $sql = "SELECT 
                    p.id, p.codigo, p.descricao_completa, p.observacao, p.marca, p.modelo, p.num_serie,
                    p.tipo_id, p.dependencia_id, p.condicao_14_1,
                    p.nota_numero, p.nota_data, p.nota_valor, p.nota_fornecedor,
                    tb.descricao as tipo_descricao,
                    d.descricao as dependencia_descricao,
                    admin.nome as administrador_nome, admin.cpf as administrador_cpf,
                    doador.nome as doador_nome, doador.cpf as doador_cpf, doador.rg as doador_rg,
                    doador.casado as doador_casado, doador.endereco as doador_endereco,
                    doador.nome_conjuge as doador_nome_conjuge,
                    doador.cpf_conjuge as doador_cpf_conjuge,
                    doador.rg_conjuge as doador_rg_conjuge
                FROM produtos p
                LEFT JOIN tipo_bens tb ON p.tipo_id = tb.id
                LEFT JOIN dependencias d ON p.dependencia_id = d.id
                LEFT JOIN administradores admin ON p.administrador_id = admin.id
                LEFT JOIN doadores doador ON p.doador_id = doador.id
                WHERE p.comum_id = :id_comum
                ORDER BY p.codigo";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_comum' => $this->id_planilha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obterDados()
    {
        $planilha = $this->buscarDadosPlanilha();

        if (!$planilha) {
            throw new Exception('Planilha não encontrada');
        }

        return [
            'planilha' => $planilha,
            'produtos' => $this->buscarProdutos(),
            'formulario' => $this->formulario
        ];
    }
}

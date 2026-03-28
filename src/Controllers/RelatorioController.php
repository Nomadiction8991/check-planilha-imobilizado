<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ConnectionManager;
use App\Core\SessionManager;
use PDO;

class RelatorioController extends BaseController
{
    private function carregarDadosComum(int $comumId): array
    {
        $conexao = ConnectionManager::getConnection();
        $stmtComum = $conexao->prepare(
            'SELECT cnpj, descricao, administracao, cidade, setor FROM comums WHERE id = :id'
        );
        $stmtComum->bindValue(':id', $comumId, PDO::PARAM_INT);
        $stmtComum->execute();

        return $stmtComum->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function carregarProdutosFormulario(int $comumId): array
    {
        $conexao = ConnectionManager::getConnection();
        $stmtProd = $conexao->prepare(
            "SELECT
                p.id_produto AS id,
                p.codigo,
                TRIM(CONCAT_WS(' ',
                    CASE WHEN (tb.codigo IS NOT NULL OR tb.descricao IS NOT NULL)
                         THEN TRIM(CONCAT_WS(' - ', tb.codigo, tb.descricao))
                         ELSE NULL END,
                    NULLIF(TRIM(COALESCE(NULLIF(p.editado_bem,''), p.bem)), ''),
                    NULLIF(TRIM(COALESCE(NULLIF(p.editado_complemento,''), p.complemento)), '')
                )) AS descricao_completa,
                p.condicao_14_1,
                p.nota_numero,
                p.nota_data,
                p.nota_valor,
                p.nota_fornecedor,
                d.descricao AS dependencia_descricao,
                u.nome      AS administrador_nome,
                NULL        AS administrador_assinatura,
                NULL        AS doador_nome,
                NULL        AS doador_cpf,
                NULL        AS doador_rg,
                0           AS doador_rg_igual_cpf,
                0           AS doador_casado,
                NULL        AS doador_nome_conjuge,
                NULL        AS doador_cpf_conjuge,
                NULL        AS doador_rg_conjuge,
                0           AS doador_rg_conjuge_igual_cpf,
                NULL        AS doador_assinatura,
                NULL        AS doador_assinatura_conjuge,
                NULL        AS doador_endereco_logradouro,
                NULL        AS doador_endereco_numero,
                NULL        AS doador_endereco_complemento,
                NULL        AS doador_endereco_bairro,
                NULL        AS doador_endereco_cidade,
                NULL        AS doador_endereco_estado,
                NULL        AS doador_endereco_cep
             FROM produtos p
             LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
             LEFT JOIN dependencias d ON p.dependencia_id = d.id
             LEFT JOIN usuarios u ON p.administrador_acessor_id = u.id
             WHERE p.comum_id = :comum_id
               AND p.imprimir_14_1 = 1
               AND p.ativo = 1
             ORDER BY p.codigo ASC"
        );
        $stmtProd->bindValue(':comum_id', $comumId, PDO::PARAM_INT);
        $stmtProd->execute();

        return $stmtProd->fetchAll(PDO::FETCH_ASSOC);
    }

    public function __construct(?PDO $conexao = null)
    {
        // Dependencies handled by repositories if needed in the future
    }

    public function relatorio141(): void
    {
        $comumId    = SessionManager::getComumId();
        $idPlanilha = $comumId;

        if (!$comumId) {
            $this->redirecionar('/churches?erro=Selecione+uma+comum+para+gerar+o+relat%C3%B3rio');
            return;
        }

        $comum = $this->carregarDadosComum($comumId);
        $produtos = $this->carregarProdutosFormulario($comumId);

        $this->renderizar('reports/report-141', [
            'id_planilha'            => $idPlanilha,
            'comum_id'               => $comumId,
            'produtos'               => $produtos,
            'cnpj_planilha'          => $comum['cnpj']          ?? '',
            'comum_planilha'         => $comum['descricao']     ?? '',
            'administracao_planilha' => $comum['administracao'] ?? '',
            'cidade_planilha'        => $comum['cidade']        ?? '',
            'casa_oracao_auto'       => $comum['descricao']     ?? '',
            'numero_relatorio_auto'  => '',
        ]);
    }

    public function visualizar(): void
    {
        $comumId = SessionManager::getComumId();
        if (!$comumId) {
            $this->redirecionar('/churches?erro=Selecione+uma+comum+para+gerar+o+relat%C3%B3rio');
            return;
        }

        $formulario = trim((string)$this->query('formulario', ''));
        if ($formulario === '') {
            $this->redirecionar('/churches?erro=Formulário não especificado');
            return;
        }

        $formularioNormalizado = str_replace('-', '.', $formulario);
        $formulariosPermitidos = ['14.1', '14.2', '14.3', '14.4', '14.5', '14.6', '14.7', '14.8'];

        if (!in_array($formularioNormalizado, $formulariosPermitidos, true)) {
            $this->redirecionar('/churches?erro=Formul%C3%A1rio+inv%C3%A1lido');
            return;
        }

        $this->renderizar('reports/view', [
            'formulario'  => $formularioNormalizado,
            'id_planilha' => $comumId,
            'comum_id'    => $comumId,
            'planilha'    => $this->carregarDadosComum($comumId),
            'produtos'    => $this->carregarProdutosFormulario($comumId),
        ]);
    }

    public function assinatura(): void
    {
        $comumId = SessionManager::getComumId();
        if (!$comumId) {
            $this->redirecionar('/churches?erro=Selecione+uma+comum+para+assinar');
            return;
        }

        $this->renderizar('reports/signature', [
            'id_planilha' => $comumId,
            'comum_id'    => $comumId,
        ]);
    }

    /**
     * Abre a página de "Relatório de Alterações" (impressão).
     * Rendeiriza a view existente em `spreadsheets/report-print-changes.php`.
     */
    public function alteracoes(): void
    {
        $comumId = SessionManager::getComumId();
        $idPlanilha = $comumId;

        if (!$idPlanilha || $idPlanilha <= 0) {
            // mantém comportamento consistente: direciona para seleção de comum
            $this->redirecionar('/churches?erro=Selecione+uma+comum+para+ver+o+relat%C3%B3rio');
            return;
        }

        // Fornecer a conexão PDO à view (algumas views usam $conexao diretamente)
        $conexao = ConnectionManager::getConnection();

        $this->renderizar('spreadsheets/report-print-changes', [
            'id_planilha' => $idPlanilha,
            'comum_id'    => $comumId,
            'conexao'     => $conexao,
        ]);
    }
}

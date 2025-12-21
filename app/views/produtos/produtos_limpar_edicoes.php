<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
// AutenticaÃƒÂ§ÃƒÂ£o

// Parâmetros
// Aceitar tanto id_PRODUTO (legacy) quanto id_produto (mais comum) para compatibilidade
$id_PRODUTO = $_GET['id_PRODUTO'] ?? $_GET['id_produto'] ?? null;
$comum_id = $_GET['comum_id'] ?? $_GET['id'] ?? null;

// Filtros para retorno
$pagina = $_GET['pagina'] ?? 1;
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['filtro_codigo'] ?? '';
$filtro_STATUS = $_GET['STATUS'] ?? '';

function redirectBack($params)
{
    $qs = http_build_query($params);
    header('Location: ../planilhas/planilha_visualizar.php?' . $qs);
    exit;
}

if (!$id_PRODUTO || !$comum_id) {
    redirectBack([
        'id' => $comum_id,
        'comum_id' => $comum_id,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'filtro_codigo' => $filtro_codigo,
        'codigo' => $filtro_codigo,
        'status' => $filtro_STATUS,
        'STATUS' => $filtro_STATUS,
        'erro' => 'Parâmetros inválidos'
    ]);
}

try {
    // LIMPAR campos de ediÃƒÂ§ÃƒÂ£o na tabela PRODUTOS - USANDO id_PRODUTO
    // Importante: usar valores padrÃƒÂ£o vÃƒÂ¡lidos ('' ou 0) pois colunas sÃƒÂ£o NOT NULL em alguns bancos
    $sql_update = "UPDATE PRODUTOS 
                   SET editado_tipo_bem_id = 0,
                       editado_bem = '',
                       editado_complemento = '',
                       editado_dependencia_id = 0,
                       editado_descricao_completa = '',
                       imprimir_etiqueta = 0,
                       editado = 0
                   WHERE id_PRODUTO = :id_PRODUTO 
                     AND comum_id = :comum_id";

    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bindValue(':id_PRODUTO', $id_PRODUTO);
    $stmt_update->bindValue(':comum_id', $comum_id);
    $stmt_update->execute();

    $msg = 'EdiÃƒÂ§ÃƒÂµes limpas com sucesso!';

    redirectBack([
        'id' => $comum_id,
        'comum_id' => $comum_id,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'filtro_codigo' => $filtro_codigo,
        'codigo' => $filtro_codigo,
        'status' => $filtro_STATUS,
        'STATUS' => $filtro_STATUS,
        'sucesso' => $msg
    ]);
} catch (Exception $e) {
    redirectBack([
        'id' => $comum_id,
        'comum_id' => $comum_id,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'filtro_codigo' => $filtro_codigo,
        'codigo' => $filtro_codigo,
        'status' => $filtro_STATUS,
        'STATUS' => $filtro_STATUS,
        'erro' => 'Erro ao limpar edições: ' . $e->getMessage()
    ]);
}

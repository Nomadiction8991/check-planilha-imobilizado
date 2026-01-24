<?php
 // AutenticaÃ§Ã£o
require_once dirname(__DIR__, 2) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$produto_id = (int) ($_POST['produto_id'] ?? 0);
$comum_id = (int) ($_POST['comum_id'] ?? 0);
$checado = (int) ($_POST['checado'] ?? 0);

$filtros = [
    'pagina' => $_POST['pagina'] ?? 1,
    'nome' => $_POST['nome'] ?? '',
    'dependencia' => $_POST['dependencia'] ?? '',
    'codigo' => $_POST['codigo'] ?? '',
    'status' => $_POST['status'] ?? ''
];

$redirectBase = '../../views/planilhas/planilha_visualizar.php';
$buildRedirect = function (string $erro = '') use ($redirectBase, $comum_id, $filtros): string {
    $params = array_merge(['id' => $comum_id, 'comum_id' => $comum_id], $filtros);
    if ($erro !== '') {
        $params['erro'] = $erro;
    }
    return $redirectBase . '?' . http_build_query($params);
};

if ($produto_id <= 0 || $comum_id <= 0) {
    $msg = 'PARÂMETROS INVÁLIDOS PARA MARCAR O PRODUTO';
    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => $msg], 400);
    }
    header('Location: ' . $buildRedirect($msg));
    exit;
}

try {
    // Buscar status atual do produto
    $sql_verifica = "SELECT COALESCE(p.ativo, 1) AS ativo, COALESCE(p.imprimir_etiqueta, 0) AS imprimir, COALESCE(p.editado, 0) AS editado 
                     FROM produtos p 
                     WHERE p.id_produto = :produto_id";
    $stmt_verifica = $conexao->prepare($sql_verifica);
    $stmt_verifica->bindValue(':produto_id', $produto_id, PDO::PARAM_INT);
    $stmt_verifica->execute();
    $status = $stmt_verifica->fetch();

    // Regras de validaÃ§Ã£o
    if ($checado === 1 && $status && $status['ativo'] == 0) {
        $msg = 'NÃO É POSSÍVEL MARCAR COMO CHECADO ENQUANTO O PRODUTO ESTIVER NO DR';
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => $msg], 422);
        }
        header('Location: ' . $buildRedirect($msg));
        exit;
    }

    // Apenas bloquear desmarcar check se produto estiver no DR (ativo=0)
    if ($checado === 0 && $status && $status['ativo'] == 0) {
        $msg = 'NÃO É POSSÍVEL DESMARCAR O CHECK SE O PRODUTO ESTIVER NO DR';
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => $msg], 422);
        }
        header('Location: ' . $buildRedirect($msg));
        exit;
    }
    
    // Atualizar coluna checado na tabela produtos
    $sql = "UPDATE produtos SET checado = :checado WHERE id_produto = :produto_id";
    $stmt = $conexao->prepare($sql);
    $stmt->bindValue(':produto_id', $produto_id, PDO::PARAM_INT);
    $stmt->bindValue(':checado', $checado, PDO::PARAM_INT);
    $stmt->execute();

    if (is_ajax_request()) {
        json_response([
            'success' => true,
            'produto_id' => $produto_id,
            'checado' => $checado,
            'message' => $checado ? 'PRODUTO MARCADO COMO CHECADO' : 'PRODUTO DESMARCADO'
        ]);
    }
    
    header('Location: ' . $buildRedirect());
    exit;
    
} catch (Exception $e) {
    $msg = 'ERRO AO PROCESSAR CHECK: ' . $e->getMessage();
    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => $msg], 500);
    }
    header('Location: ' . $buildRedirect($msg));
    exit;
}




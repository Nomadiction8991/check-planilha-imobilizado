<?php
// AutenticaÃ§Ã£o
require_once dirname(__DIR__, 2) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$produto_id = (int) ($_POST['produto_id'] ?? 0);
// Aceitar 'comum_id' preferencialmente; caso contrário aceitar 'planilha_id' para compatibilidade
$comum_id = (int) ($_POST['comum_id'] ?? $_POST['planilha_id'] ?? 0);
$imprimir = (int) ($_POST['imprimir'] ?? 0);

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
    $msg = 'PARÂMETROS INVÁLIDOS PARA MARCAR ETIQUETA';
    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => $msg], 400);
    }
    header('Location: ' . $buildRedirect($msg));
    exit;
}

try {
    // Impedir remoção da etiqueta se produto estiver editado
    $stmt_check = $conexao->prepare('SELECT COALESCE(editado,0) AS editado FROM produtos WHERE id_produto = :id_produto AND comum_id = :comum_id');
    $stmt_check->bindValue(':id_produto', $produto_id, PDO::PARAM_INT);
    $stmt_check->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $info = $stmt_check->fetch(PDO::FETCH_ASSOC);
    if (($info['editado'] ?? 0) == 1 && $imprimir === 0) {
        $msg = 'NÃO É POSSÍVEL REMOVER A ETIQUETA ENQUANTO HOUVER ALTERAÇÕES EDITADAS (REMOVA AS ALTERAÇÕES PARA REMOVER)';
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => $msg], 422);
        }
        header('Location: ' . $buildRedirect($msg));
        exit;
    }

    // Se for marcar para imprimir, garantir que o produto fique marcado como checado automaticamente.
    // Se for desmarcar, não alterar o campo 'checado' (permanece como está).
    if ($imprimir === 1) {
        $stmt = $conexao->prepare('UPDATE produtos SET imprimir_etiqueta = 1, checado = 1 WHERE id_produto = :id_produto AND comum_id = :comum_id');
        $stmt->bindValue(':id_produto', $produto_id, PDO::PARAM_INT);
        $stmt->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $conexao->prepare('UPDATE produtos SET imprimir_etiqueta = 0 WHERE id_produto = :id_produto AND comum_id = :comum_id');
        $stmt->bindValue(':id_produto', $produto_id, PDO::PARAM_INT);
        $stmt->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    if (is_ajax_request()) {
        json_response([
            'success' => true,
            'produto_id' => $produto_id,
            'imprimir' => $imprimir,
            'message' => $imprimir ? 'PRODUTO MARCADO PARA ETIQUETA' : 'PRODUTO REMOVIDO DAS ETIQUETAS'
        ]);
    }

    header('Location: ' . $buildRedirect());
    exit;
} catch (Exception $e) {
    $msg = 'ERRO AO PROCESSAR IMPRESSÃO: ' . $e->getMessage();
    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => $msg], 500);
    }
    header('Location: ' . $buildRedirect($msg));
    exit;
}

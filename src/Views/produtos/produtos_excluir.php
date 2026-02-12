<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
// AUTENTICAÇÁO

if (function_exists('is_ajax_request') && is_ajax_request()) {
    header('Content-Type: application/json');
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter IDs e ID da comum
$comum_id = $_POST['comum_id'] ?? $_POST['id_planilha'] ?? null;
$ids_produtos = $_POST['ids_produtos'] ?? $_POST['ids_PRODUTOS'] ?? [];

if (!$comum_id || empty($ids_produtos)) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

try {
    // Converter array de IDs para valores seguros
    $ids_produtos = array_map('intval', $ids_produtos);
    $placeholders = implode(',', array_fill(0, count($ids_produtos), '?'));

    // Preparar SQL
    $sql = "DELETE FROM produtos WHERE comum_id = ? AND id_produto IN ($placeholders)";
    $stmt = $conexao->prepare($sql);

    // Bind do ID da comum
    $stmt->bindValue(1, $comum_id, PDO::PARAM_INT);

    // Bind dos IDs dos produtos
    foreach ($ids_produtos as $index => $id) {
        $stmt->bindValue($index + 2, $id, PDO::PARAM_INT);
    }

    // Executar
    if ($stmt->execute()) {
        $payload = ['success' => true, 'message' => 'Produtos excluídos com sucesso'];
        if (function_exists('is_ajax_request') && !is_ajax_request()) {
            header('Location: ./produtos_listar.php?comum_id=' . urlencode((string)$comum_id) . '&deleted=1');
            exit;
        }
        echo json_encode($payload);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir produtos']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

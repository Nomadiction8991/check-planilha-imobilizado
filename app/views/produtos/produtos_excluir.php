<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
// AUTENTICAÇÃO

header('Content-Type: application/json');

// Verificar se Ã© POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'MÃ©todo NÃO permitido']);
    exit;
}

// Obter IDs e ID da comum
$comum_id = $_POST['comum_id'] ?? $_POST['id_planilha'] ?? null;
$ids_PRODUTOS = $_POST['ids_PRODUTOS'] ?? [];

if (!$comum_id || empty($ids_PRODUTOS)) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

try {
    // Converter array de IDs para valores seguros
    $ids_PRODUTOS = array_map('intval', $ids_PRODUTOS);
    $placeholders = implode(',', array_fill(0, count($ids_PRODUTOS), '?'));

    // Preparar SQL
    $sql = "DELETE FROM produtos WHERE comum_id = ? AND id_PRODUTO IN ($placeholders)";
    $stmt = $conexao->prepare($sql);

    // Bind do ID da comum
    $stmt->bindValue(1, $comum_id, PDO::PARAM_INT);

    // Bind dos IDs dos PRODUTOS
    foreach ($ids_PRODUTOS as $index => $id) {
        $stmt->bindValue($index + 2, $id, PDO::PARAM_INT);
    }

    // Executar
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'PRODUTOS excluÃ­dos com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir PRODUTOS']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

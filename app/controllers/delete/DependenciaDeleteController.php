<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Clear any accidental output buffers that could contaminate JSON
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID nÃ£o informado']);
    exit;
}

try {
    $stmt = $conexao->prepare('DELETE FROM dependencias WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Dependência excluída com sucesso'], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    error_log('Erro ao excluir dependÃªncia: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir dependência'], JSON_UNESCAPED_UNICODE);
    exit;
}



<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$acao = $_POST['acao'] ?? ''; // 'assinar' ou 'desassinar'
$produtos_ids = $_POST['produtos'] ?? []; // Array de IDs de produtos

if (empty($produtos_ids) || !is_array($produtos_ids)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum produto selecionado']);
    exit;
}

// Todos os usuários assinam como administradores
$coluna_assinatura = 'administrador_acessor_id';

try {
    $conexao->beginTransaction();

    if ($acao === 'assinar') {
        // Assinar produtos: definir o ID do usuário
        $sql = "UPDATE produtos SET {$coluna_assinatura} = :usuario_id WHERE id_produto = :produto_id";
        $stmt = $conexao->prepare($sql);

        foreach ($produtos_ids as $produto_id) {
            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':produto_id' => (int)$produto_id
            ]);
        }

        $mensagem = 'Produtos assinados com sucesso';
    } elseif ($acao === 'desassinar') {
        // Desassinar produtos: limpar o ID do usuário (setar 0)
        $sql = "UPDATE produtos SET {$coluna_assinatura} = 0 WHERE id_produto = :produto_id AND {$coluna_assinatura} = :usuario_id";
        $stmt = $conexao->prepare($sql);

        foreach ($produtos_ids as $produto_id) {
            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':produto_id' => (int)$produto_id
            ]);
        }

        $mensagem = 'Assinatura removida com sucesso';
    } else {
        throw new Exception('Ação inválida');
    }

    $conexao->commit();
    echo json_encode(['success' => true, 'message' => $mensagem]);
} catch (Exception $e) {
    $conexao->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

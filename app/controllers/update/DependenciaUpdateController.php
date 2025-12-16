<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensagem = '';
$tipo_mensagem = '';

if ($id <= 0) {
    header('Location: ./dependencias_listar.php');
    exit;
}

// Buscar dependÃƒÂªncia
try {
    $stmt = $conexao->prepare('SELECT * FROM dependencias WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $dependencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dependencia) {
        throw new Exception('DependÃƒÂªncia nÃƒÂ£o encontrada.');
    }
} catch (Throwable $e) {
    $mensagem = 'Erro: ' . $e->getMessage();
    $tipo_mensagem = 'danger';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = isset($_POST['descricao']) ? trim((string)$_POST['descricao']) : '';

    try {
        if ($descricao === '') {
            throw new Exception('A descrição é obrigatória.');
        }

        // Atualizar apenas descrição (campo 'codigo' removido)
        $sql = 'UPDATE dependencias SET descricao = :descricao WHERE id = :id';
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':descricao', mb_strtoupper($descricao, 'UTF-8'));
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();

        $mensagem = 'DependÃƒÂªncia atualizada com sucesso!';
        $tipo_mensagem = 'success';
        header('Location: ../../views/dependencias/dependencias_listar.php?success=1');
        exit;
    } catch (Throwable $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}




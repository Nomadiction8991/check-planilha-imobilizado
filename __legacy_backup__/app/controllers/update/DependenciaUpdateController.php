<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensagem = '';
$tipo_mensagem = '';

if ($id <= 0) {
    header('Location: ./dependencias_listar.php');
    exit;
}

// Buscar dependªncia
try {
    $stmt = $conexao->prepare('SELECT * FROM dependencias WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $dependencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dependencia) {
        throw new Exception('Dependªncia n£o encontrada.');
    }
} catch (Throwable $e) {
    $mensagem = 'Erro: ' . $e->getMessage();
    $tipo_mensagem = 'danger';
}

// Processar formulrio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = isset($_POST['descricao']) ? trim((string)$_POST['descricao']) : '';

    try {
        if ($descricao === '') {
            throw new Exception('A descrio  obrigatria.');
        }

        // Atualizar apenas descrio (campo 'codigo' removido)
        $sql = 'UPDATE dependencias SET descricao = :descricao WHERE id = :id';
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':descricao', mb_strtoupper($descricao, 'UTF-8'));
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();

        $mensagem = 'Dependncia atualizada com sucesso!';
        $tipo_mensagem = 'success';
        // Preserve filters when returning to list
        $retQ = [];
        if (!empty($_GET['busca'])) { $retQ['busca'] = $_GET['busca']; }
        if (!empty($_GET['pagina'])) { $retQ['pagina'] = $_GET['pagina']; }
        $retQ['success'] = 1;
        header('Location: ../../views/dependencias/dependencias_listar.php?' . http_build_query($retQ));
        exit;
    } catch (Throwable $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}




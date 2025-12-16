<?php
declare(strict_types=1);
// CRUD/READ/dependencia.php - implementaÃ§Ã£o limpa

require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$limite = 10; // limit 10 registros por página
$offset = ($pagina - 1) * $limite;
$busca = trim((string)($_GET['busca'] ?? ''));

try {
    if (!$conexao) {
        throw new Exception('Sem conexão com o banco de dados');
    }

    // Count with optional search
    if ($busca !== '') {
        $sql_count = 'SELECT COUNT(*) FROM dependencias WHERE descricao LIKE :busca';
        $stmt_count = $conexao->prepare($sql_count);
        $stmt_count->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
        $stmt_count->execute();
        $total_registros = (int) $stmt_count->fetchColumn();

        $sql = "SELECT id, descricao FROM dependencias WHERE descricao LIKE :busca ORDER BY descricao ASC, id ASC LIMIT :limite OFFSET :offset";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
    } else {
        $sql_count = 'SELECT COUNT(*) FROM dependencias';
        $total_registros = (int) $conexao->query($sql_count)->fetchColumn();

        $sql = "SELECT id, descricao FROM dependencias ORDER BY descricao ASC, id ASC LIMIT :limite OFFSET :offset";
        $stmt = $conexao->prepare($sql);
    }

    $total_paginas = (int) ceil($total_registros / $limite);

    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $dependencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $dependencias = [];
    $total_registros = 0;
    $total_paginas = 0;
    $pagina = 1;
    error_log('Erro ao carregar dependências: ' . $e->getMessage());
}

?>



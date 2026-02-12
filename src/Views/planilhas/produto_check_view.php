<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
// AUTENTICAÇÁO
// Endpoint público para processar o check do PRODUTO
// Inclui a lógica do CRUD e ajusta os redirecionamentos para o contexto correto

// Capturar dados antes de incluir
$_POST_BACKUP = $_POST;
$_REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

// Incluir conexão

if ($_REQUEST_METHOD === 'POST') {
    $PRODUTO_id = $_POST_BACKUP['PRODUTO_id'] ?? null;
    $comum_id = $_POST_BACKUP['comum_id'] ?? $_POST_BACKUP['id_planilha'] ?? null;
    $checado = $_POST_BACKUP['checado'] ?? 0;

    // Preservar filtros
    $filtros = [
        'pagina' => $_POST_BACKUP['pagina'] ?? 1,
        'nome' => $_POST_BACKUP['nome'] ?? '',
        'dependencia' => $_POST_BACKUP['dependencia'] ?? '',
        'codigo' => $_POST_BACKUP['codigo'] ?? '',
        'STATUS' => $_POST_BACKUP['STATUS'] ?? ''
    ];

    if (!$PRODUTO_id || !$id_planilha) {
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: ./planilha_visualizar.php?' . $query_string);
        exit;
    }

    try {
        // BUSCAR STATUS atual no novo schema (produtos) - USANDO id_produto
        $stmt_STATUS = $conexao->prepare('SELECT checado, imprimir_etiqueta, imprimir_14_1 FROM produtos WHERE id_produto = :id_produto AND comum_id = :comum_id');
        $stmt_STATUS->bindValue(':id_produto', $PRODUTO_id, PDO::PARAM_INT);
        $stmt_STATUS->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt_STATUS->execute();
        $STATUS = $stmt_STATUS->fetch(PDO::FETCH_ASSOC);

        if (!$STATUS) {
            throw new Exception('PRODUTO NÃO encontrado.');
        }

        // Regra: NÃO pode desmarcar checado se estiver marcado para impressão
        if ((int)$checado === 0 && (($STATUS['imprimir_etiqueta'] ?? 0) == 1 || ($STATUS['imprimir_14_1'] ?? 0) == 1)) {
            $query_string = http_build_query(array_merge(
                ['id' => $id_planilha],
                $filtros,
                ['erro' => 'Não é possível desmarcar o check se o produto estiver marcado para impressão.']
            ));
            header('Location: ./planilha_visualizar.php?' . $query_string);
            exit;
        }

        // ATUALIZAR flag no próprio produto - USANDO id_produto
        $stmt_up = $conexao->prepare('UPDATE produtos SET checado = :checado WHERE id_produto = :id_produto AND comum_id = :comum_id');
        $stmt_up->bindValue(':checado', (int)$checado, PDO::PARAM_INT);
        $stmt_up->bindValue(':id_produto', $PRODUTO_id, PDO::PARAM_INT);
        $stmt_up->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt_up->execute();

        // Redirecionar de volta mantendo os filtros
        $query_string = http_build_query(array_merge(['id' => $comum_id, 'comum_id' => $comum_id], $filtros));
        header('Location: ./planilha_visualizar.php?' . $query_string);
        exit;
    } catch (Exception $e) {
        $query_string = http_build_query(array_merge(
            ['id' => $id_planilha],
            $filtros,
            ['erro' => 'Erro ao processar check: ' . $e->getMessage()]
        ));
        header('Location: ./planilha_visualizar.php?' . $query_string);
        exit;
    }
} else {
    header('Location: ../../../index.php');
    exit;
}

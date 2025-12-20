<?php
// scripts/reconcile-editado-flags.php
// Uso: php reconcile-editado-flags.php [--apply]
// Sem --apply: mostra quantos registros seriam afetados (dry-run)
// Com --apply: executa o UPDATE para garantir imprimir_etiqueta=1 e checado=1 onde editado=1

require_once __DIR__ . '/../app/bootstrap.php';

$apply = in_array('--apply', $argv, true);

try {
    $sql_check = "SELECT COUNT(*) AS cnt FROM produtos WHERE COALESCE(editado,0) = 1 AND (COALESCE(imprimir_etiqueta,0) <> 1 OR COALESCE(checado,0) <> 1)";
    $stmt = $conexao->prepare($sql_check);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $cnt = (int)($row['cnt'] ?? 0);

    echo "Produtos inconsistentes que seriam atualizados: ${cnt}\n";

    if ($cnt === 0) {
        echo "Nenhuma ação necessária.\n";
        exit(0);
    }

    if (!$apply) {
        echo "Modo dry-run: rode 'php reconcile-editado-flags.php --apply' para aplicar as alterações.\n";
        exit(0);
    }

    $sql_update = "UPDATE produtos SET imprimir_etiqueta = 1, checado = 1 WHERE COALESCE(editado,0) = 1 AND (COALESCE(imprimir_etiqueta,0) <> 1 OR COALESCE(checado,0) <> 1)";
    $stmt2 = $conexao->prepare($sql_update);
    $stmt2->execute();
    $affected = $stmt2->rowCount();
    echo "Atualizado(s) ${affected} registro(s).\n";
    exit(0);
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . "\n";
    exit(1);
}

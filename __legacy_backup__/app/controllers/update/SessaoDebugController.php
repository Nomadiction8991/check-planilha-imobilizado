<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG DE SESSÁƒO ===\n\n";

echo "Sessão Iniciada: " . (session_status() == PHP_SESSION_ACTIVE ? "SIM" : "NÃO") . "\n\n";

echo "--- Dados da Sessão ---\n";
echo "usuario_id: " . (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 'NÃO DEFINIDO') . "\n";
echo "usuario_nome: " . (isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'NÃO DEFINIDO') . "\n";
echo "usuario_email: " . (isset($_SESSION['usuario_email']) ? $_SESSION['usuario_email'] : 'NÃO DEFINIDO') . "\n";
echo "is_admin: " . (!empty($_SESSION['is_admin']) ? '1' : '0') . "\n";
echo "is_doador: " . (!empty($_SESSION['is_doador']) ? '1' : '0') . "\n";

echo "\n--- Verificação de Autenticação ---\n";
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
    echo "âœ… Usuário autenticado corretamente!\n";
    echo "âœ… ID válido: " . $_SESSION['usuario_id'] . "\n";
} else {
    echo "âŒ Usuário NÃO está autenticado!\n";
}

echo "\n--- Todas as chaves da sessão ---\n";
foreach ($_SESSION as $key => $value) {
    echo "$key => " . (is_array($value) ? 'Array' : $value) . "\n";
}

echo "\n=== FIM DEBUG ===\n";
?>



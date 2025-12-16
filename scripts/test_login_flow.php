<?php
/**
 * Script para testar EXATAMENTE o fluxo de login passo a passo
 * e descobrir onde está falhando.
 */

define('SKIP_AUTH', true);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

echo "=== TEST LOGIN FLOW ===\n\n";

// 1. Verificar dados do usuario id=1
echo "1. Verificando usuário id=1 no banco...\n";
$stmt = $conexao->prepare('SELECT * FROM usuarios WHERE id = 1');
$stmt->execute();
$usuario = $stmt->fetch();

if (!$usuario) {
    echo "   ERROR: Usuário id=1 não encontrado!\n";
    exit(1);
}

echo "   ID: " . $usuario['id'] . "\n";
echo "   Email: " . $usuario['email'] . "\n";
echo "   Nome: " . $usuario['nome'] . "\n";
echo "   Ativo: " . $usuario['ativo'] . "\n";
echo "   Senha hash length: " . strlen($usuario['senha']) . "\n";
echo "   Senha hash (first 30 chars): " . substr($usuario['senha'], 0, 30) . "...\n";

// 2. Testar password_verify com as 2 emails possiveis
echo "\n2. Testando password_verify...\n";

// Qual é a senha correta?
echo "   Qual é a senha que você usa para login?\n";
echo "   (Digite e pressione Enter): ";
$senha_input = trim(fgets(STDIN));

if (empty($senha_input)) {
    echo "   Senha vazia. Abortando.\n";
    exit(1);
}

// password_verify NUNCA deve uppercase a senha
$result = password_verify($senha_input, $usuario['senha']);
echo "   password_verify('{$senha_input}', hash) = " . ($result ? "TRUE" : "FALSE") . "\n";

if (!$result) {
    echo "\n   ⚠️ ERRO: Senha não bate!\n";
    echo "   Possíveis causas:\n";
    echo "     - A senha foi armazenada com hash errado\n";
    echo "     - A senha no banco não foi salva com password_hash()\n";
    echo "     - Você está digitando a senha errada\n";
    
    // Tentar com uppercase (errado, mas para debug)
    echo "\n   Testando se password_verify aceita uppercase...\n";
    $result_upper = password_verify(strtoupper($senha_input), $usuario['senha']);
    echo "   password_verify(strtoupper, hash) = " . ($result_upper ? "TRUE" : "FALSE") . "\n";
    
    exit(1);
}

echo "   ✅ Senha correta!\n";

// 3. Simular exatamente o que login.php faz
echo "\n3. Simulando exatamente o fluxo de login.php...\n";

// Destruir session anterior (limpar)
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
    session_start();
}

// Regenerate session id (como login.php faz)
echo "   Regenerando session id...\n";
$old_id = session_id();
session_regenerate_id(true);
$new_id = session_id();
echo "   Old session_id: $old_id\n";
echo "   New session_id: $new_id\n";

// Setar session vars (como login.php faz)
echo "   Setando session vars...\n";
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];
$_SESSION['usuario_email'] = $usuario['email'];
$_SESSION['is_admin'] = ((int)$usuario['id'] === 1);
$_SESSION['is_doador'] = false;

// Escrever session (como login.php faz)
echo "   Executando session_write_close()...\n";
session_write_close();

echo "   Session vars depois de write_close:\n";
echo "     usuario_id: " . ($_SESSION['usuario_id'] ?? 'NOT SET') . "\n";
echo "     usuario_nome: " . ($_SESSION['usuario_nome'] ?? 'NOT SET') . "\n";
echo "     is_admin: " . ($_SESSION['is_admin'] ?? 'NOT SET') . "\n";

// 4. Agora simular uma requisição para index.php
echo "\n4. Simulando uma requisição para index.php...\n";

// Reabrir a session (como index.php faria)
session_start();
echo "   Session ID after restart: " . session_id() . "\n";
echo "   Session vars:\n";
echo "     usuario_id: " . ($_SESSION['usuario_id'] ?? 'NOT SET') . "\n";
echo "     usuario_nome: " . ($_SESSION['usuario_nome'] ?? 'NOT SET') . "\n";
echo "     is_admin: " . ($_SESSION['is_admin'] ?? 'NOT SET') . "\n";

if (!isset($_SESSION['usuario_id'])) {
    echo "\n   ⚠️ ERRO CRÍTICO: Session perdida após write_close + restart!\n";
    echo "   Isto explica o redirect loop!\n";
    echo "\n   Causas possíveis:\n";
    echo "     - session.save_path não tem permissão de escrita\n";
    echo "     - Sessions estão sendo armazenadas em Redis/Memcached e não está conectado\n";
    echo "     - Caminho da session está diferente entre requests\n";
    echo "\n   Inspecionando session.save_path:\n";
    echo "     session.save_path = " . ini_get('session.save_path') . "\n";
    echo "     session.save_handler = " . ini_get('session.save_handler') . "\n";
    
    // Tentar verificar permissões
    $save_path = ini_get('session.save_path');
    if ($save_path && is_dir($save_path)) {
        echo "     save_path exists: YES\n";
        echo "     save_path is_writable: " . (is_writable($save_path) ? "YES" : "NO") . "\n";
    } else {
        echo "     save_path exists: NO or invalid\n";
    }
    
    exit(1);
}

echo "\n✅ SUCESSO: Session foi preservada! Usuário pode fazer login.\n";
echo "\nO problema está em outro lugar - provavelmente em index.php ou no cookie.\n";
exit(0);

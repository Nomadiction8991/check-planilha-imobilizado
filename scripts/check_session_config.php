<?php
define('SKIP_AUTH', true);
require_once dirname(__DIR__) . '/config/bootstrap.php';

echo "=== SESSION CONFIGURATION ===\n\n";

echo "session.save_handler = " . ini_get('session.save_handler') . "\n";
echo "session.save_path = " . ini_get('session.save_path') . "\n";
echo "session.name = " . ini_get('session.name') . "\n";
echo "session.cookie_path = " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_domain = " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_secure = " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly = " . ini_get('session.cookie_httponly') . "\n";
echo "session.cookie_samesite = " . ini_get('session.cookie_samesite') . "\n";

$save_path = ini_get('session.save_path');

echo "\n=== VERIFICANDO SESSION.SAVE_PATH ===\n";
if (!$save_path || $save_path === '') {
    echo "⚠️  session.save_path está VAZIO!\n";
    echo "   Usando default do PHP: /tmp\n";
    $save_path = '/tmp';
}

if (strpos($save_path, ';') !== false) {
    echo "⚠️  session.save_path tem formato com profundidade: $save_path\n";
    $parts = explode(';', $save_path);
    $save_path = $parts[1] ?? $parts[0];
    echo "   Extractando caminho: $save_path\n";
}

echo "Verificando: $save_path\n";

if (!is_dir($save_path)) {
    echo "❌ ERRO: Diretório NÃO EXISTE!\n";
} else {
    echo "✅ Diretório existe\n";
    
    if (is_writable($save_path)) {
        echo "✅ Diretório é GRAVÁVEL\n";
    } else {
        echo "❌ ERRO: Diretório NÃO É GRAVÁVEL!\n";
        echo "   Permissões: " . substr(sprintf('%o', fileperms($save_path)), -4) . "\n";
    }
    
    // Listar arquivos de session
    $files = glob($save_path . '/sess_*');
    echo "\n   Total de arquivos de session: " . count($files) . "\n";
    if (count($files) > 0) {
        echo "   Últimos 5 arquivos:\n";
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        foreach (array_slice($files, 0, 5) as $f) {
            $mtime = filemtime($f);
            $ago = time() - $mtime;
            echo "     - " . basename($f) . " (modificado há " . $ago . "s)\n";
        }
    }
}

echo "\n=== TESTE DE WRITE ===\n";
session_start();
$_SESSION['test'] = microtime(true);
session_write_close();

echo "✅ Session escrita\n";
echo "   session_id = " . session_id() . "\n";

// Tenta ler de novo
session_start();
if (isset($_SESSION['test'])) {
    echo "✅ Session LIDA COM SUCESSO após write_close\n";
    echo "   test value = " . $_SESSION['test'] . "\n";
} else {
    echo "❌ ERRO: Session NÃO FOI LIDA!\n";
    echo "   Session vars: " . implode(', ', array_keys($_SESSION)) . "\n";
}

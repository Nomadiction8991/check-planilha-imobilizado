<?php
// Activate a user by id or email, optionally uppercase their email.
// Usage: php scripts/activate_user.php --id=1 --uppercase

$options = [];
foreach ($argv as $arg) {
    if (strpos($arg, '--') === 0) {
        $parts = explode('=', substr($arg, 2), 2);
        $options[$parts[0]] = $parts[1] ?? true;
    }
}

$id = $options['id'] ?? null;
$email = $options['email'] ?? null;
$uppercase = isset($options['uppercase']);

if (!$id && !$email) {
    echo "Usage: php scripts/activate_user.php --id=1 [--uppercase] OR --email=foo@example.com [--uppercase]\n";
    exit(1);
}

$host = getenv('DB_HOST') ?: 'anvy.com.br';
$db   = getenv('DB_NAME') ?: 'anvycomb_checkplanilha';
$user = getenv('DB_USER') ?: 'anvycomb_checkplanilha';
$pass = getenv('DB_PASS') ?: 'uGyzaCndm7EDahptkBZd';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE utf8mb4_unicode_ci",
    ]);

    if ($email) {
        $stmt = $pdo->prepare('SELECT id, email FROM usuarios WHERE UPPER(email) = UPPER(?) LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if (!$row) { echo "No user found for email $email\n"; exit(1); }
        $id = $row['id'];
    }

    if ($uppercase) {
        $stmt = $pdo->prepare('UPDATE usuarios SET email = UPPER(email) WHERE id = ?');
        $stmt->execute([$id]);
        echo "Uppercased email for user id=$id\n";
    }

    $stmt = $pdo->prepare('UPDATE usuarios SET ativo = 1 WHERE id = ?');
    $stmt->execute([$id]);
    echo "Activated user id=$id\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

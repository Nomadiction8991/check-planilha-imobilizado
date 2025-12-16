<?php
// Simple session inspector for localhost only. Not to be enabled in production.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit;
}
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
echo "COOKIE:\n";
print_r($_COOKIE);
echo "\nSESSION:\n";
print_r($_SESSION);
echo "\nSession ID: " . session_id() . "\n";

// Also display headers
if (function_exists('getallheaders')) {
    echo "\nHEADERS:\n";
    print_r(getallheaders());
}

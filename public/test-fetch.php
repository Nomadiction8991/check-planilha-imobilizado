<?php
// Simple test to check if POST arrives
header('Content-Type: application/json');
$log = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'cookies' => $_COOKIE,
    'post' => $_POST,
    'input' => file_get_contents('php://input'),
];
file_put_contents('/tmp/fetch-test.log', json_encode($log, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
echo json_encode(['success' => true, 'data' => $log]);

<?php
header('Content-Type: application/json; charset=utf-8');

$resp = [
    'status' => 'ok',
    'message' => 'API test endpoint',
    'method' => $_SERVER['REQUEST_METHOD'],
    'time' => date(DATE_ATOM),
];

if (!empty($_GET)) {
    $resp['query'] = $_GET;
}

echo json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

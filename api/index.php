<?php
header('Content-Type: application/json; charset=utf-8');
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$endpoints = [
    "$base/test" => 'GET — simple JSON test endpoint',
];
echo json_encode([
    'status' => 'ok',
    'message' => 'API root',
    'endpoints' => $endpoints,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

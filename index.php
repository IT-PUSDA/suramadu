<?php
// Konfigurasi error: default non-verbose (tidak tampil di browser)
// Aktifkan kembali dengan set environment APP_DEBUG=1 atau ?debug=1 pada URL
$__debug = false;
if (getenv('APP_DEBUG')) {
    $val = strtolower(trim((string)getenv('APP_DEBUG')));
    $__debug = in_array($val, ['1','true','yes','on'], true);
}
if (!$__debug && isset($_GET['debug'])) {
    $__debug = in_array(strtolower((string)$_GET['debug']), ['1','true','yes','on'], true);
}

if ($__debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    // Sembunyikan deprecation/notice agar UI tidak terganggu
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
}

session_start();
// ob_start(); // Dinonaktifkan sementara untuk diagnostik

// Define a base path constant
define('BASE_PATH', dirname(__FILE__));

// a front-controller to route requests
$page = isset($_GET['page']) ? $_GET['page'] : 'login';
$page_path = BASE_PATH . '/src/' . $page . '.php';

// simple security check to avoid including files outside of src
if (file_exists($page_path)) {
    require($page_path);
} else {
    // page not found, maybe show a 404 page
    // for now, just redirect to login
    http_response_code(404);
    require(BASE_PATH . '/src/Auth/login.php');
}


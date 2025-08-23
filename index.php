<?php
// KODE UNTUK MENAMPILKAN ERROR SECARA PAKSA
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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


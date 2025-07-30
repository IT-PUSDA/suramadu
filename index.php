<?php
session_start();
ob_start();

// Define a base path constant
define('BASE_PATH', dirname(__FILE__));

// a front-controller to route requests
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

$page_path = BASE_PATH . '/src/' . $page . '.php';

// simple security check to avoid including files outside of src
if (file_exists($page_path) && strpos(realpath($page_path), BASE_PATH . '/src') === 0) {
    require($page_path);
} else {
    // page not found, maybe show a 404 page
    // for now, just redirect to login
    http_response_code(404);
    require(BASE_PATH . '/src/login.php');
}

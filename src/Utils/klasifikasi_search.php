<?php
// JSON endpoint for klasifikasi autocomplete
session_start();
require_once __DIR__ . '/../include/config.php';

header('Content-Type: application/json; charset=utf-8');

// Force connection charset to UTF-8
if (function_exists('mysqli_set_charset')) {
    @mysqli_set_charset($config, 'utf8mb4');
}

// Only allow when logged in
if (empty($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode([ 'error' => 'Unauthorized' ]);
    exit;
}

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

// Escape and build LIKE pattern
if ($term === '') {
    // Return top rows when no term provided (for focus-open)
    $sql = "SELECT id_klasifikasi, kode, nama, uraian
            FROM tbl_klasifikasi
            ORDER BY kode ASC";
} else {
    $safe = mysqli_real_escape_string($config, $term);
    $like = "%$safe%";
    // No LIMIT per request: return all matched rows
    $sql = "SELECT id_klasifikasi, kode, nama, uraian
            FROM tbl_klasifikasi
            WHERE kode LIKE '$like' OR nama LIKE '$like' OR uraian LIKE '$like'
            ORDER BY kode ASC";
}

$res = mysqli_query($config, $sql);
$out = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $out[] = [
            'id'    => (int)$row['id_klasifikasi'],
            'kode'  => $row['kode'],
            'nama'  => $row['nama'],
            'uraian'=> $row['uraian']
        ];
    }
}

echo json_encode($out);
exit;
?>

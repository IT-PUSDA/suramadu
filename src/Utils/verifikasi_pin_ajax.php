<?php
session_start();
require_once __DIR__ . '/../include/config.php';

header('Content-Type: application/json');

// Basic security checks
if (empty($_SESSION['admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_surat']) || !isset($_POST['pin'])) {
    echo json_encode(['success' => false, 'message' => 'Akses tidak sah.']);
    exit();
}

$id_surat = mysqli_real_escape_string($config, $_POST['id_surat']);
$submitted_pin = $_POST['pin'];

// Fetch the PIN hash from the database
$query = mysqli_query($config, "SELECT pin FROM tbl_surat_keluar WHERE id_surat='$id_surat'");

if (mysqli_num_rows($query) > 0) {
    list($pin_hash) = mysqli_fetch_array($query);

    // If PIN is not set in DB (for old data), or if user is super admin, grant access
    if (empty($pin_hash) || (isset($_SESSION['admin']) && $_SESSION['admin'] == 1)) {
        echo json_encode(['success' => true]);
        exit();
    }

    // Verify the submitted PIN against the hash
    if (password_verify($submitted_pin, $pin_hash)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'PIN yang Anda masukkan salah.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Data surat tidak ditemukan.']);
}
exit();
?>

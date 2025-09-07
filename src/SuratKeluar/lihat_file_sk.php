<?php
// filepath: c:\laragon\www\ams\src\SuratKeluar\lihat_file_sk.php
// Memulai session untuk memeriksa status login
session_start();

// Memuat file konfigurasi database
require_once __DIR__ . '/../include/config.php';
// Centralized operator access helper
if (!function_exists('operator_access_info')) { @include_once __DIR__ . '/../include/operator_access.php'; }

// Keamanan: Pastikan pengguna sudah login
if (empty($_SESSION['admin'])) {
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

// Memeriksa apakah parameter id_surat ada
if (!isset($_GET['id_surat']) || empty($_GET['id_surat'])) {
    die('ERROR: ID Surat tidak ditemukan.');
}

$id_surat = mysqli_real_escape_string($config, $_GET['id_surat']);

// Keamanan tambahan: izinkan akses jika:
//  - Super admin (1)
//  - Sudah punya tiket verifikasi PIN
//  - Operator (3) yang mengakses surat di dalam kelompok bidangnya sendiri
// Flag awal
$is_operator = (isset($_SESSION['admin']) && (int)$_SESSION['admin'] === 3);
$operator_group_access = false;

// Mengambil data file dari database
$query = mysqli_query($config, "SELECT file,id_user FROM tbl_surat_keluar WHERE id_surat='$id_surat'");

if (mysqli_num_rows($query) == 0) {
    die('ERROR: Data surat tidak ditemukan.');
}

list($file, $owner_id) = mysqli_fetch_array($query);

// Tentukan akses operator menggunakan helper setelah tahu owner
if ($is_operator && function_exists('operator_access_info')) {
    $opInfo = operator_access_info($config, $_SESSION, (int)$owner_id);
    $operator_group_access = $opInfo['operator_group_access'];
}

// Pimpinan (2) juga boleh melihat file tanpa PIN
if (empty($_SESSION['file_access_granted'][$id_surat]) && (!isset($_SESSION['admin']) || (!in_array((int)$_SESSION['admin'], [1,2]) && !$operator_group_access))) {
    http_response_code(403); // Forbidden
    die('Akses tidak sah. Verifikasi PIN dibutuhkan atau Anda bukan operator dalam bidang surat ini.');
}

// Pembatasan tambahan untuk level Bidang (4): hanya boleh melihat file milik sendiri
if ((int)$_SESSION['admin'] === 4 && (int)$owner_id !== (int)$_SESSION['id_user']) {
    http_response_code(403);
    die('Akses ditolak: Anda tidak dapat melihat file milik bidang lain.');
}

// Jika file tidak ada di database, hentikan proses
if (empty($file)) {
    die('ERROR: File untuk surat ini tidak ditemukan di database.');
}

// Fungsi untuk melayani file
function serve_file($file, $id_surat)
{
    // Path absolut ke file
    $file_path = realpath(__DIR__ . '/../../upload/surat_keluar') . '/' . $file;

    if (file_exists($file_path)) {
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        // Bersihkan output buffer sebelum mengirim header
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Atur header berdasarkan tipe file
        if ($file_extension == 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        }
        
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . filesize($file_path));
        
        // Baca dan kirim file
        readfile($file_path);

        // Hapus tiket akses setelah digunakan (one-time access)
        if (isset($_SESSION['file_access_granted'][$id_surat])) {
            unset($_SESSION['file_access_granted'][$id_surat]);
        }

        exit(); // Hentikan eksekusi setelah file dikirim
    } else {
        die('ERROR: File fisik tidak ditemukan di server.');
    }
}

// Tampilkan file (akses valid atau super admin)
serve_file($file, $id_surat);

?>
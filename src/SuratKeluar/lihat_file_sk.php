<?php
// filepath: c:\laragon\www\ams\src\SuratKeluar\lihat_file_sk.php
// Memulai session untuk memeriksa status login
session_start();

// Memuat file konfigurasi database
require_once __DIR__ . '/../include/config.php';

// Keamanan: Pastikan pengguna sudah login
if (empty($_SESSION['admin'])) {
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

// Memeriksa apakah parameter id_surat ada
if (!isset($_GET['id_surat']) || empty($_GET['id_surat'])) {
    die('ERROR: ID Surat tidak ditemukan.');
}

$id_surat = mysqli_real_escape_string($config, $_GET['id_surat']);

// Mengambil data file dari database
$query = mysqli_query($config, "SELECT file FROM tbl_surat_keluar WHERE id_surat='$id_surat'");

if (mysqli_num_rows($query) == 0) {
    die('ERROR: Data surat tidak ditemukan.');
}

list($file) = mysqli_fetch_array($query);

// Jika file tidak ada di database, hentikan proses
if (empty($file)) {
    die('ERROR: File untuk surat ini tidak ditemukan di database.');
}

// Fungsi untuk melayani file
function serve_file($file)
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
        exit(); // Hentikan eksekusi setelah file dikirim
    } else {
        die('ERROR: File fisik tidak ditemukan di server.');
    }
}

// Langsung panggil fungsi untuk menampilkan file, karena verifikasi sudah dilakukan di halaman sebelumnya.
serve_file($file);

?>
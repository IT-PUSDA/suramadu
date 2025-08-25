<?php
// filepath: c:\laragon\www\ams\src\SuratKeluar\lihat_file_sk.php
// Memulai session untuk memeriksa status login
session_start();

// Memuat file konfigurasi database secara langsung
require_once __DIR__ . '/../include/config.php';

// Keamanan: Pastikan pengguna sudah login
if (empty($_SESSION['admin'])) {
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

// Memeriksa apakah parameter id_surat ada dan tidak kosong
if (isset($_GET['id_surat']) && !empty($_GET['id_surat'])) {
    $id_surat = mysqli_real_escape_string($config, $_GET['id_surat']);

    // Mengambil nama file dari database
    $query = mysqli_query($config, "SELECT file FROM tbl_surat_keluar WHERE id_surat='$id_surat'");
    
    if (mysqli_num_rows($query) > 0) {
        list($file) = mysqli_fetch_array($query);

        if (!empty($file)) {
            // Membuat path absolut yang pasti benar ke folder upload
            $file_path = realpath(__DIR__ . '/../../upload/surat_keluar') . '/' . $file;

            if (file_exists($file_path)) {
                // Mendapatkan ekstensi file
                $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

                // Membersihkan semua output buffer yang mungkin ada untuk mencegah data rusak
                @ob_end_clean();

                // Mengatur header berdasarkan tipe file
                if ($file_extension == 'pdf') {
                    // Jika PDF, tampilkan di browser
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
                } else {
                    // Jika bukan PDF, paksa unduh
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                }
                
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                header('Content-Length: ' . filesize($file_path));
                
                // Membaca dan mengirimkan isi file ke browser
                readfile($file_path);
                exit();
            } else {
                die('Kesalahan: File fisik tidak ditemukan di server.');
            }
        } else {
            die('Informasi: Tidak ada file yang terlampir untuk surat ini.');
        }
    } else {
        die('Kesalahan: Data surat tidak ditemukan.');
    }
} else {
    die('Kesalahan: ID Surat tidak valid.');
}
?>
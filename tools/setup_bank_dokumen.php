<?php
// Database migration untuk fitur Bank Dokumen

require_once __DIR__ . '/../src/include/config.php';

echo "=== Setup Bank Dokumen Tables ===\n\n";

// 1. Tabel untuk kategori berkas dokumen (TU, SUN-GRAM, KEUANGAN)
$sql1 = "CREATE TABLE IF NOT EXISTS tbl_bank_dokumen_kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT,
    warna_bg VARCHAR(50) DEFAULT 'khaki',
    tgl_buat DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// 2. Tabel untuk jenis berkas (IT/Kepegawaian, dll) per kategori
$sql2 = "CREATE TABLE IF NOT EXISTS tbl_bank_dokumen_jenis (
    id_jenis INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT NOT NULL,
    nama_jenis VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    tgl_buat DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES tbl_bank_dokumen_kategori(id_kategori) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// 3. Tabel untuk dokumen/file yang diupload
$sql3 = "CREATE TABLE IF NOT EXISTS tbl_bank_dokumen_file (
    id_file INT AUTO_INCREMENT PRIMARY KEY,
    id_jenis INT NOT NULL,
    id_kategori INT NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    pembuat VARCHAR(100),
    id_user INT,
    ukuran_file INT,
    tipe_file VARCHAR(50),
    tgl_buat DATETIME DEFAULT CURRENT_TIMESTAMP,
    tgl_update DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jenis) REFERENCES tbl_bank_dokumen_jenis(id_jenis) ON DELETE CASCADE,
    FOREIGN KEY (id_kategori) REFERENCES tbl_bank_dokumen_kategori(id_kategori) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$results = [];

if (mysqli_query($config, $sql1)) {
    $results[] = "✓ Tabel tbl_bank_dokumen_kategori dibuat/ada";
} else {
    $results[] = "✗ Error tbl_bank_dokumen_kategori: " . mysqli_error($config);
}

if (mysqli_query($config, $sql2)) {
    $results[] = "✓ Tabel tbl_bank_dokumen_jenis dibuat/ada";
} else {
    $results[] = "✗ Error tbl_bank_dokumen_jenis: " . mysqli_error($config);
}

if (mysqli_query($config, $sql3)) {
    $results[] = "✓ Tabel tbl_bank_dokumen_file dibuat/ada";
} else {
    $results[] = "✗ Error tbl_bank_dokumen_file: " . mysqli_error($config);
}

// Insert kategori default
$kategori_check = mysqli_query($config, "SELECT COUNT(*) as c FROM tbl_bank_dokumen_kategori");
$cat_row = mysqli_fetch_assoc($kategori_check);

if ((int)$cat_row['c'] === 0) {
    $cat_sql = "INSERT INTO tbl_bank_dokumen_kategori (nama_kategori, deskripsi, warna_bg) VALUES 
                ('TU', 'Tata Usaha', 'khaki'),
                ('SUN-GRAM', 'Surat dan Penomoran Gramatikal', 'khaki'),
                ('KEUANGAN', 'Keuangan', 'khaki')";
    if (mysqli_query($config, $cat_sql)) {
        $results[] = "✓ Kategori default (TU, SUN-GRAM, KEUANGAN) diinsersi";
    } else {
        $results[] = "✗ Error insert kategori: " . mysqli_error($config);
    }
} else {
    $results[] = "ℹ Kategori sudah ada";
}

echo implode("\n", $results) . "\n\n";
echo "=== Setup Selesai ===\n";

?>

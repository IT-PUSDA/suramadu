<?php
// Script migrasi: menghitung data existing dan mengisi tbl_file_position dengan counter yang benar
// Jalankan SATU KALI sebelum go-live dengan sistem page+line baru

require_once __DIR__ . '/../src/include/config.php';
require_once __DIR__ . '/../src/include/file_sequence.php';

echo "=== Migrasi Counter dari Data Existing ===\n\n";

// 1. Hitung existing records per (year, bidang, jenis) dari tbl_surat_keluar
$query = "SELECT 
    YEAR(tgl_surat) AS year,
    bidang,
    COALESCE(jenis, 'umum') AS jenis,
    COUNT(*) AS total
FROM tbl_surat_keluar
WHERE bidang IS NOT NULL AND bidang != ''
GROUP BY YEAR(tgl_surat), bidang, jenis
ORDER BY year, bidang, jenis";

$result = mysqli_query($config, $query);
if (!$result) {
    die("ERROR query: " . mysqli_error($config) . "\n");
}

// 2. Pastikan tabel tbl_file_position ada
mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_file_position (
    `year` INT NOT NULL,
    `bidang` VARCHAR(50) NOT NULL,
    `jenis` VARCHAR(50) NOT NULL,
    `seq` INT NOT NULL,
    PRIMARY KEY (`year`, `bidang`, `jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$migrated = 0;
$skipped = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $year = (int)$row['year'];
    $bidang = mysqli_real_escape_string($config, $row['bidang']);
    $jenis = mysqli_real_escape_string($config, $row['jenis']);
    $count = (int)$row['total'];
    
    // Cek apakah sudah ada entry
    $check = mysqli_query($config, "SELECT seq FROM tbl_file_position WHERE `year`='$year' AND bidang='$bidang' AND jenis='$jenis'");
    if ($check && mysqli_num_rows($check) > 0) {
        $existing = mysqli_fetch_assoc($check);
        echo "SKIP: year=$year bidang=$bidang jenis=$jenis (sudah ada seq=" . $existing['seq'] . ")\n";
        $skipped++;
        continue;
    }
    
    // Insert dengan seq = count (karena record berikutnya akan jadi count+1)
    $insert = mysqli_query($config, "INSERT INTO tbl_file_position (`year`, bidang, jenis, seq) VALUES ('$year', '$bidang', '$jenis', '$count')");
    if ($insert) {
        echo "OK: year=$year bidang=$bidang jenis=$jenis -> set seq=$count (next akan jadi " . ($count+1) . ")\n";
        $migrated++;
    } else {
        echo "ERROR: year=$year bidang=$bidang jenis=$jenis -> " . mysqli_error($config) . "\n";
    }
}

echo "\n=== Selesai ===\n";
echo "Migrated: $migrated kombinasi\n";
echo "Skipped: $skipped kombinasi (sudah ada)\n";

// 3. Hitung juga untuk tbl_notdin jika ada
$notdinCheck = mysqli_query($config, "SHOW TABLES LIKE 'tbl_notdin'");
if ($notdinCheck && mysqli_num_rows($notdinCheck) > 0) {
    echo "\n--- Migrasi tbl_notdin (jika format nomor compatible) ---\n";
    // Catatan: tbl_notdin perlu dicek apakah sudah punya kolom bidang dan format yang sesuai
    // Jika belum, skip atau perlu custom handling
    echo "SKIP: tbl_notdin (perlu review manual)\n";
}

echo "\nSelesai! Sekarang sistem akan melanjutkan dari counter yang benar.\n";
?>

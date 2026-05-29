<?php
include 'src/include/config.php';

echo "=== CHECKING KLASIFIKASI CODES ===\n\n";

// Get the surat with number 0137
echo "1. Surat dengan nomor 0137:\n";
$surat = mysqli_query($config, "SELECT id_surat, no_surat, kode, jenis, tgl_surat FROM tbl_surat_keluar WHERE no_surat LIKE '%/0137/%'");
if ($surat && mysqli_num_rows($surat) > 0) {
    while ($row = mysqli_fetch_assoc($surat)) {
        echo "   ID: " . $row['id_surat'] . "\n";
        echo "   No Surat: " . $row['no_surat'] . "\n";
        echo "   Kode: " . $row['kode'] . "\n";
        echo "   Jenis: " . $row['jenis'] . "\n";
        echo "   Tgl Surat: " . $row['tgl_surat'] . "\n\n";
    }
} else {
    echo "   Tidak ditemukan\n";
}

// Get the surat with number 10643
echo "2. Surat dengan nomor 10643:\n";
$surat2 = mysqli_query($config, "SELECT id_surat, no_surat, kode, jenis, tgl_surat FROM tbl_surat_keluar WHERE no_surat LIKE '%/10643/%'");
if ($surat2 && mysqli_num_rows($surat2) > 0) {
    while ($row = mysqli_fetch_assoc($surat2)) {
        echo "   ID: " . $row['id_surat'] . "\n";
        echo "   No Surat: " . $row['no_surat'] . "\n";
        echo "   Kode: " . $row['kode'] . "\n";
        echo "   Jenis: " . $row['jenis'] . "\n";
        echo "   Tgl Surat: " . $row['tgl_surat'] . "\n\n";
    }
} else {
    echo "   Tidak ditemukan\n";
}

// Show all keuangan surats created recently
echo "3. Surat keuangan terbaru (jenis=keuangan):\n";
$keuangan = mysqli_query($config, "SELECT id_surat, no_surat, kode, tgl_surat FROM tbl_surat_keluar WHERE jenis='keuangan' ORDER BY id_surat DESC LIMIT 5");
if ($keuangan && mysqli_num_rows($keuangan) > 0) {
    while ($row = mysqli_fetch_assoc($keuangan)) {
        echo "   ID " . $row['id_surat'] . ": " . $row['no_surat'] . " (kode: " . $row['kode'] . ", tgl: " . $row['tgl_surat'] . ")\n";
    }
} else {
    echo "   Tidak ada surat keuangan\n";
}

// Count surats by jenis
echo "\n4. Total per jenis:\n";
$counts = mysqli_query($config, "SELECT jenis, COUNT(*) as cnt FROM tbl_surat_keluar GROUP BY jenis");
while ($row = mysqli_fetch_assoc($counts)) {
    echo "   - " . ($row['jenis'] ?? 'null') . ": " . $row['cnt'] . "\n";
}

?>

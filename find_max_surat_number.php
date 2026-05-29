<?php
include 'src/include/config.php';

echo "=== ANALISIS NOMOR GLOBAL TERAKHIR ===\n\n";

// Cari nomor tertinggi yang ada di semua surat
$q = mysqli_query($config, "SELECT no_surat FROM tbl_surat_keluar ORDER BY id_surat DESC LIMIT 100");

$max_num = 0;
$max_surat = '';

if ($q && mysqli_num_rows($q) > 0) {
    while ($row = mysqli_fetch_assoc($q)) {
        $no_surat = $row['no_surat'];
        // Format: kode / nomor / bidang / tahun
        $parts = explode('/', $no_surat);
        if (count($parts) >= 2) {
            $num_part = $parts[1];
            // Extract only the numeric part (clean up)
            $num_only = (int) preg_replace('/[^0-9]/', '', $num_part);
            if ($num_only > $max_num) {
                $max_num = $num_only;
                $max_surat = $no_surat;
            }
        }
    }
}

echo "Nomor tertinggi ditemukan: " . $max_num . "\n";
echo "Surat: " . $max_surat . "\n";
echo "Nomor berikutnya seharusnya: " . ($max_num + 1) . "\n";

// Check current sequence counter tables
echo "\n=== CURRENT SEQUENCE TABLES ===\n\n";

// Check if we have global sequence table
echo "1. tbl_file_sequence (global per year):\n";
$fileSeq = mysqli_query($config, "SELECT * FROM tbl_file_sequence ORDER BY year DESC");
if ($fileSeq && mysqli_num_rows($fileSeq) > 0) {
    while ($row = mysqli_fetch_assoc($fileSeq)) {
        echo "   Year: " . $row['year'] . ", Seq: " . $row['seq'] . "\n";
    }
} else {
    echo "   Kosong\n";
}

echo "\n2. tbl_file_position (per bidang per jenis):\n";
$filePosCount = mysqli_query($config, "SELECT COUNT(*) as cnt FROM tbl_file_position");
$r = mysqli_fetch_assoc($filePosCount);
echo "   Total rows: " . $r['cnt'] . "\n";
echo "   Ini adalah sistem halaman-baris per (bidang, jenis)\n";

// Test: what should be the next number for today if continuing from 10643?
echo "\n=== NEXT NUMBER UNTUK HARI INI ===\n";
echo "Jika menggunakan global sequential counter: " . ($max_num + 1) . "\n";

?>

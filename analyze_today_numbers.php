<?php
include 'src/include/config.php';

echo "=== ANALISIS NOMOR SURAT HARI INI (29 Mei 2026) ===\n\n";

// Check what's in tbl_date_sequence for today
echo "1. Sequences untuk hari ini (2026-05-29):\n";
$todaySeq = mysqli_query($config, "SELECT tgl_surat, jenis, seq FROM tbl_date_sequence WHERE tgl_surat = '2026-05-29'");
if ($todaySeq && mysqli_num_rows($todaySeq) > 0) {
    while ($row = mysqli_fetch_assoc($todaySeq)) {
        echo "   - tgl_surat: " . $row['tgl_surat'] . ", jenis: " . $row['jenis'] . ", seq: " . $row['seq'] . "\n";
    }
} else {
    echo "   ⚠ TIDAK ADA DATA untuk hari ini!\n";
}

// Check what's the day of year for today
$today = date('Y-m-d');
$dayOfYear = (int)date('z', strtotime($today)) + 1;
$formattedDay = sprintf('%02d', $dayOfYear);
echo "\n2. Informasi hari ini (29 Mei 2026):\n";
echo "   - Day of Year: " . $dayOfYear . " (formatted: " . $formattedDay . ")\n";
echo "   - Jadi format nomor baru harusnya: " . $formattedDay . "XX (misal " . $formattedDay . "01, " . $formattedDay . "02, dst)\n";

// Check recent surat keuangan
echo "\n3. Surat keuangan terbaru:\n";
$recentKeuangan = mysqli_query($config, "SELECT id_surat, no_surat, tgl_surat, jenis FROM tbl_surat_keluar WHERE jenis='keuangan' ORDER BY id_surat DESC LIMIT 5");
if ($recentKeuangan && mysqli_num_rows($recentKeuangan) > 0) {
    while ($row = mysqli_fetch_assoc($recentKeuangan)) {
        echo "   ID " . $row['id_surat'] . ": " . $row['no_surat'] . " (tgl: " . $row['tgl_surat'] . ")\n";
    }
} else {
    echo "   Tidak ada surat keuangan\n";
}

// Check recent surat umum
echo "\n4. Surat umum terbaru:\n";
$recentUmum = mysqli_query($config, "SELECT id_surat, no_surat, tgl_surat, jenis FROM tbl_surat_keluar WHERE jenis='umum' ORDER BY id_surat DESC LIMIT 5");
if ($recentUmum && mysqli_num_rows($recentUmum) > 0) {
    while ($row = mysqli_fetch_assoc($recentUmum)) {
        echo "   ID " . $row['id_surat'] . ": " . $row['no_surat'] . " (tgl: " . $row['tgl_surat'] . ")\n";
    }
} else {
    echo "   Tidak ada surat umum\n";
}

// Count surat per jenis for today
echo "\n5. Count surat untuk hari ini per jenis:\n";
$countToday = mysqli_query($config, "SELECT jenis, COUNT(*) as cnt FROM tbl_surat_keluar WHERE tgl_surat='2026-05-29' GROUP BY jenis");
if ($countToday && mysqli_num_rows($countToday) > 0) {
    while ($row = mysqli_fetch_assoc($countToday)) {
        echo "   - " . $row['jenis'] . ": " . $row['cnt'] . " surat\n";
    }
} else {
    echo "   Tidak ada surat untuk hari ini\n";
}

// Show all dates in tbl_date_sequence
echo "\n6. Semua data dalam tbl_date_sequence:\n";
$allSeq = mysqli_query($config, "SELECT tgl_surat, jenis, seq FROM tbl_date_sequence ORDER BY tgl_surat DESC");
if ($allSeq && mysqli_num_rows($allSeq) > 0) {
    echo "   Count: " . mysqli_num_rows($allSeq) . "\n";
    while ($row = mysqli_fetch_assoc($allSeq)) {
        echo "   - " . $row['tgl_surat'] . " | " . $row['jenis'] . " | seq=" . $row['seq'] . "\n";
    }
} else {
    echo "   Kosong!\n";
}

?>

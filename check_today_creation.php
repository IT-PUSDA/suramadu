<?php
include 'src/include/config.php';

echo "=== SUARAT CREATED TODAY (2026-05-29) ===\n\n";

// Check all surats created today, regardless of jenis
$today = mysqli_query($config, "SELECT id_surat, no_surat, kode, jenis, tgl_surat, tgl_input FROM tbl_surat_keluar WHERE tgl_surat='2026-05-29' ORDER BY id_surat DESC");
if ($today && mysqli_num_rows($today) > 0) {
    while ($row = mysqli_fetch_assoc($today)) {
        echo "ID: " . $row['id_surat'] . "\n";
        echo "  No Surat: " . $row['no_surat'] . "\n";
        echo "  Kode: " . $row['kode'] . "\n";
        echo "  Jenis: " . $row['jenis'] . "\n";
        echo "  Tgl Surat: " . $row['tgl_surat'] . "\n";
        echo "  Tgl Input: " . $row['tgl_input'] . "\n\n";
    }
} else {
    echo "  No surats found\n";
}

// Look for any surat created today but with earlier date
echo "=== SURATS UPDATED/CREATED TODAY (tgl_input or tgl_update) ===\n\n";
$todayUpdated = mysqli_query($config, "SELECT id_surat, no_surat, kode, jenis, tgl_surat, tgl_input, tgl_update FROM tbl_surat_keluar WHERE DATE(tgl_input)='2026-05-29' OR DATE(tgl_update)='2026-05-29' ORDER BY id_surat DESC LIMIT 10");
if ($todayUpdated && mysqli_num_rows($todayUpdated) > 0) {
    while ($row = mysqli_fetch_assoc($todayUpdated)) {
        echo "ID: " . $row['id_surat'] . "\n";
        echo "  No Surat: " . $row['no_surat'] . "\n";
        echo "  Kode: " . $row['kode'] . "\n";
        echo "  Jenis DB: " . $row['jenis'] . "\n";
        echo "  Tgl Surat: " . $row['tgl_surat'] . "\n";
        echo "  Tgl Input: " . $row['tgl_input'] . "\n";
        echo "  Tgl Update: " . $row['tgl_update'] . "\n\n";
    }
} else {
    echo "  No surats found\n";
}

// Check sequence tables to see what was incremented today
echo "=== SEQUENCE COUNTERS (most recent) ===\n\n";
$seqs = mysqli_query($config, "SELECT year, bidang, jenis, seq FROM tbl_file_position ORDER BY year DESC, bidang LIMIT 30");
if ($seqs && mysqli_num_rows($seqs) > 0) {
    while ($row = mysqli_fetch_assoc($seqs)) {
        echo "Year: " . $row['year'] . " | Bidang: " . $row['bidang'] . " | Jenis: " . $row['jenis'] . " | Seq: " . $row['seq'] . "\n";
    }
}

?>

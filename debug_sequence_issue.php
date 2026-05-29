<?php
include 'src/include/config.php';

// Check all recent updates
$q = mysqli_query($config, "SELECT id_surat, no_surat, tgl_surat, jenis, tgl_input, tgl_update FROM tbl_surat_keluar ORDER BY tgl_update DESC LIMIT 30");

if ($q && mysqli_num_rows($q) > 0) {
    echo "Last 30 updates:\n";
    echo str_pad('ID', 6) . ' | ' . str_pad('Nomor Surat', 40) . ' | ' . str_pad('Jenis', 15) . ' | tgl_surat | Updated\n';
    echo str_repeat('-', 130) . "\n";
    while ($r = mysqli_fetch_assoc($q)) {
        $jenis = $r['jenis'] ?? 'none';
        echo str_pad($r['id_surat'], 6) . ' | ' . str_pad($r['no_surat'], 40) . ' | ' . str_pad($jenis, 15) . ' | ' . substr($r['tgl_surat'], 0, 10) . ' | ' . $r['tgl_update'] . "\n";
    }
}

echo "\n\n=== All date sequences ===\n";
$q2 = mysqli_query($config, "SELECT tgl_surat, jenis, seq FROM tbl_date_sequence ORDER BY tgl_surat DESC LIMIT 30");
if ($q2 && mysqli_num_rows($q2) > 0) {
    echo str_pad('Date', 15) . ' | ' . str_pad('Jenis', 20) . ' | Seq\n';
    echo str_repeat('-', 60) . "\n";
    while ($r = mysqli_fetch_assoc($q2)) {
        echo str_pad($r['tgl_surat'], 15) . ' | ' . str_pad($r['jenis'], 20) . ' | ' . $r['seq'] . "\n";
    }
}

?>

<?php
include 'src/include/config.php';

// Show all surat with jenis 'keuangan' sorted by last update
echo "=== Surat Keuangan (Latest First) ===\n";
$q = mysqli_query($config, "SELECT id_surat, no_surat, tgl_surat, jenis, tgl_input, tgl_update FROM tbl_surat_keluar WHERE jenis='keuangan' ORDER BY id_surat DESC LIMIT 10");
if ($q && mysqli_num_rows($q) > 0) {
    echo str_pad('ID', 6) . ' | ' . str_pad('No Surat', 45) . ' | tgl_surat\n';
    echo str_repeat('-', 100) . "\n";
    while ($r = mysqli_fetch_assoc($q)) {
        echo str_pad($r['id_surat'], 6) . ' | ' . str_pad($r['no_surat'], 45) . ' | ' . $r['tgl_surat'] . "\n";
    }
}

// Show total surats per jenis
echo "\n\n=== Count by Jenis ===\n";
$q2 = mysqli_query($config, "SELECT jenis, COUNT(*) as cnt FROM tbl_surat_keluar GROUP BY jenis");
if ($q2) {
    while ($r = mysqli_fetch_assoc($q2)) {
        echo "Jenis: " . ($r['jenis'] ?? 'null') . " => Count: " . $r['cnt'] . "\n";
    }
}

// Check if tbl_date_sequence has old structure without jenis
echo "\n\n=== tbl_date_sequence columns ===\n";
$cols = mysqli_query($config, "DESCRIBE tbl_date_sequence");
if ($cols) {
    while ($col = mysqli_fetch_assoc($cols)) {
        echo "Column: " . $col['Field'] . " (Type: " . $col['Type'] . ")\n";
    }
}

// Check for any data in sequence table
echo "\n\n=== Any data in tbl_date_sequence? ===\n";
$countSeq = mysqli_query($config, "SELECT COUNT(*) as cnt FROM tbl_date_sequence");
if ($countSeq) {
    $r = mysqli_fetch_assoc($countSeq);
    echo "Total records: " . $r['cnt'] . "\n";
}

// Look for old tbl_date_sequence structure
echo "\n\n=== Check for old sequence table ===\n";
$oldTable = mysqli_query($config, "SHOW TABLES LIKE 'tbl_date_sequence_old'");
if ($oldTable && mysqli_num_rows($oldTable) > 0) {
    echo "Old table exists! Checking data...\n";
    $oldData = mysqli_query($config, "SELECT * FROM tbl_date_sequence_old LIMIT 10");
    if ($oldData) {
        while ($row = mysqli_fetch_assoc($oldData)) {
            echo json_encode($row) . "\n";
        }
    }
}

?>

<?php
include 'src/include/config.php';
include 'src/include/file_sequence.php';

echo "=== TESTING GLOBAL SEQUENTIAL COUNTER ===\n\n";

// Test fungsi get_next_global_surat_number beberapa kali
for ($i = 0; $i < 5; $i++) {
    $next = get_next_global_surat_number($config);
    echo "Call " . ($i + 1) . ": Next number = " . $next . "\n";
}

echo "\n=== NOMOR TERAKHIR YANG ADA ===\n";
// Find max again to verify
$q = mysqli_query($config, "SELECT no_surat FROM tbl_surat_keluar ORDER BY id_surat DESC LIMIT 1");
if ($q && mysqli_num_rows($q) > 0) {
    $row = mysqli_fetch_assoc($q);
    echo "Surat terakhir: " . $row['no_surat'] . "\n";
    
    $parts = explode('/', $row['no_surat']);
    if (count($parts) >= 2) {
        $num_part = $parts[1];
        $num_only = (int) preg_replace('/[^0-9]/', '', $num_part);
        echo "Nomor terakhir: " . $num_only . "\n";
        echo "Nomor berikutnya seharusnya: " . ($num_only + 1) . "\n";
    }
}

?>

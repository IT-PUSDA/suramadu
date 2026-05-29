<?php
include 'src/include/config.php';

echo "=== DEBUG: CHECK ALL SURAT NUMBERS ===\n\n";

// Get all surat and extract numbers
$q = mysqli_query($config, "SELECT no_surat FROM tbl_surat_keluar ORDER BY id_surat DESC LIMIT 50");

$numbers = [];
if ($q && mysqli_num_rows($q) > 0) {
    while ($row = mysqli_fetch_assoc($q)) {
        $no_surat = $row['no_surat'];
        $parts = explode('/', $no_surat);
        if (count($parts) >= 2) {
            $num_part = $parts[1];
            $num_only = (int) preg_replace('/[^0-9]/', '', $num_part);
            $numbers[] = [
                'full' => $no_surat,
                'num_part' => $num_part,
                'num_only' => $num_only
            ];
        }
    }
}

echo "Top 20 surats with extracted numbers:\n";
for ($i = 0; $i < min(20, count($numbers)); $i++) {
    $n = $numbers[$i];
    echo $i . ": " . str_pad($n['full'], 45) . " | num_part: " . str_pad($n['num_part'], 10) . " | numeric: " . $n['num_only'] . "\n";
}

echo "\n\nMax number found: " . max(array_column($numbers, 'num_only')) . "\n";

?>

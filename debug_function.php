<?php
include 'src/include/config.php';
include 'src/include/file_sequence.php';

echo "=== DEBUG get_next_global_surat_number ===\n\n";

// Manually do what the function does but with debug output
$query = mysqli_query($config, 
    "SELECT no_surat FROM tbl_surat_keluar ORDER BY id_surat DESC LIMIT 1000");

$max_num = 0;
$found_entries = 0;
if ($query && mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $found_entries++;
        $no_surat = $row['no_surat'];
        // Format: kode / nomor / bidang / tahun
        $parts = explode('/', $no_surat);
        if (count($parts) >= 2) {
            $num_part = $parts[1];
            // Remove non-numeric characters
            $num_only = (int) preg_replace('/[^0-9]/', '', $num_part);
            if ($num_only > $max_num) {
                echo "New max found: " . $num_only . " from \"" . $no_surat . "\" (part: \"" . $num_part . "\")\n";
                $max_num = $num_only;
            }
        }
        
        if ($found_entries <= 10) {
            echo "Entry $found_entries: " . $no_surat . " | parts: " . json_encode($parts) . " | num_part: " . $num_part . " | num_only: " . $num_only . "\n";
        }
    }
}

echo "\n\nTotal entries checked: " . $found_entries . "\n";
echo "Final max_num: " . $max_num . "\n";
echo "Next number should be: " . ($max_num + 1) . "\n";

// Now call the actual function
echo "\n\nCalling get_next_global_surat_number():\n";
$result = get_next_global_surat_number($config);
echo "Result: " . $result . "\n";

?>

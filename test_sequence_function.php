<?php
include 'src/include/config.php';
include 'src/include/file_sequence.php';

echo "=== TESTING get_sequence_code_with_sisipan ===\n\n";

// Test dengan tanggal hari ini dalam berbagai format
$test_dates = [
    '2026-05-29',      // Format ISO
    '29-05-2026',      // Format DD-MM-YYYY
    '2026/05/29',      // Format dengan slash
];

foreach ($test_dates as $date_input) {
    echo "Testing dengan input: '$date_input'\n";
    
    // Hitung day of year
    $ts = strtotime($date_input);
    if ($ts === false) {
        echo "  ✗ strtotime() failed!\n";
    } else {
        $doy = (int)date('z', $ts) + 1;
        $formatted = sprintf('%02d', $doy);
        echo "  - strtotime hasil: " . date('Y-m-d', $ts) . "\n";
        echo "  - Day of Year: " . $doy . " (formatted: " . $formatted . ")\n";
        
        try {
            $seq_code = get_sequence_code_with_sisipan($config, 2026, '104.1', 'umum', $date_input);
            echo "  - Sequence code: " . $seq_code . "\n";
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
}

// Check surat yang baru dibuat tadi
echo "=== CHECKING SURAT YANG BARU DIBUAT HARI INI ===\n";
$newest = mysqli_query($config, "SELECT id_surat, no_surat, tgl_surat, jenis FROM tbl_surat_keluar WHERE id_surat = (SELECT MAX(id_surat) FROM tbl_surat_keluar)");
if ($newest && mysqli_num_rows($newest) > 0) {
    $row = mysqli_fetch_assoc($newest);
    echo "ID: " . $row['id_surat'] . "\n";
    echo "No Surat: " . $row['no_surat'] . "\n";
    echo "Tgl Surat: " . $row['tgl_surat'] . "\n";
    echo "Jenis: " . $row['jenis'] . "\n";
    
    // Extract sequence code from no_surat
    // Format: kode / pos_code / bidang / year
    $parts = explode('/', $row['no_surat']);
    if (count($parts) >= 2) {
        $pos_code = $parts[1];
        echo "\nExtracted pos_code: " . $pos_code . "\n";
        
        // Parse pos_code (should be dayXXseqXX)
        if (strlen($pos_code) >= 4) {
            $day_part = substr($pos_code, 0, -2);
            $seq_part = substr($pos_code, -2);
            echo "  - Day part: " . $day_part . " (should be 149 for May 29)\n";
            echo "  - Seq part: " . $seq_part . "\n";
        }
    }
}

?>

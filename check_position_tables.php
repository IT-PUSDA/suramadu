<?php
include 'src/include/config.php';

echo "=== CHECKING FILE POSITION TABLES ===\n\n";

// Check tbl_file_position structure (per bidang per jenis)
echo "1. tbl_file_position (per bidang per jenis):\n";
$filePos = mysqli_query($config, "SHOW TABLES LIKE 'tbl_file_position'");
if ($filePos && mysqli_num_rows($filePos) > 0) {
    echo "   Table exists. Data:\n";
    $data = mysqli_query($config, "SELECT * FROM tbl_file_position ORDER BY year DESC, bidang, jenis LIMIT 20");
    if ($data && mysqli_num_rows($data) > 0) {
        while ($row = mysqli_fetch_assoc($data)) {
            echo "   - Year: " . $row['year'] . ", Bidang: " . $row['bidang'] . ", Jenis: " . $row['jenis'] . ", Seq: " . $row['seq'] . "\n";
        }
    } else {
        echo "   Table is empty\n";
    }
} else {
    echo "   Table does NOT exist\n";
}

// Check tbl_file_position_bidang structure (global per bidang, across all jenis)
echo "\n2. tbl_file_position_bidang (global per bidang):\n";
$filePosGlobal = mysqli_query($config, "SHOW TABLES LIKE 'tbl_file_position_bidang'");
if ($filePosGlobal && mysqli_num_rows($filePosGlobal) > 0) {
    echo "   Table exists. Data:\n";
    $data = mysqli_query($config, "SELECT * FROM tbl_file_position_bidang ORDER BY year DESC, bidang LIMIT 20");
    if ($data && mysqli_num_rows($data) > 0) {
        while ($row = mysqli_fetch_assoc($data)) {
            echo "   - Year: " . $row['year'] . ", Bidang: " . $row['bidang'] . ", Seq: " . $row['seq'] . "\n";
        }
    } else {
        echo "   Table is empty\n";
    }
} else {
    echo "   Table does NOT exist\n";
}

// Check tbl_file_sequence (global yearly)
echo "\n3. tbl_file_sequence (global per year):\n";
$fileSeq = mysqli_query($config, "SHOW TABLES LIKE 'tbl_file_sequence'");
if ($fileSeq && mysqli_num_rows($fileSeq) > 0) {
    echo "   Table exists. Data:\n";
    $data = mysqli_query($config, "SELECT * FROM tbl_file_sequence ORDER BY year DESC LIMIT 20");
    if ($data && mysqli_num_rows($data) > 0) {
        while ($row = mysqli_fetch_assoc($data)) {
            echo "   - Year: " . $row['year'] . ", Seq: " . $row['seq'] . "\n";
        }
    } else {
        echo "   Table is empty\n";
    }
} else {
    echo "   Table does NOT exist\n";
}

// Let me also check what functions are actually available
echo "\n4. Testing which sequence function is used for surat umum:\n";
include 'src/include/file_sequence.php';

// Simulate the function call for surat umum today
$year = 2026;
$bidang = '104.1';
$jenis = 'umum';
$tgl_surat = '2026-05-29';

echo "   Testing next_position_sequence_for_year_and_bidang()...\n";
if (function_exists('next_position_sequence_for_year_and_bidang')) {
    $seq = next_position_sequence_for_year_and_bidang($config, $year, $bidang, $jenis);
    echo "   Result: $seq\n";
    
    $pos_code = page_line_label_from_seq($seq, 40);
    echo "   pos_code: $pos_code\n";
} else {
    echo "   Function does NOT exist!\n";
}

?>

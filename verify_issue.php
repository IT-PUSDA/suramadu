<?php
include 'src/include/config.php';

// Check the actual PRIMARY KEY structure
echo "=== tbl_date_sequence indexes ===\n";
$indexQuery = mysqli_query($config, "SHOW INDEXES FROM tbl_date_sequence");
if ($indexQuery) {
    while ($idx = mysqli_fetch_assoc($indexQuery)) {
        echo "Key: {$idx['Key_name']}, Column: {$idx['Column_name']}, Non_unique: {$idx['Non_unique']}\n";
    }
}

// Check current data
echo "\n\n=== Current data in tbl_date_sequence ===\n";
$dataQuery = mysqli_query($config, "SELECT * FROM tbl_date_sequence");
if ($dataQuery) {
    while ($row = mysqli_fetch_assoc($dataQuery)) {
        echo json_encode($row) . "\n";
    }
}

// Show the migration issue - jenis column doesn't exist!
echo "\n\n=== PROBLEM IDENTIFIED ===\n";
echo "Table structure mismatch!\n";
echo "- Expected: tgl_surat DATE, jenis VARCHAR(50), seq INT with PRIMARY KEY (tgl_surat, jenis)\n";

$descQuery = mysqli_query($config, "DESC tbl_date_sequence");
echo "- Actual columns:\n";
while ($col = mysqli_fetch_assoc($descQuery)) {
    echo "  -> " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

?>

<?php
include 'src/include/config.php';

echo "=== FIXING tbl_date_sequence ===\n\n";

// Step 1: Backup data from old table
echo "1. Backing up old data...\n";
$backupData = [];
$oldQuery = mysqli_query($config, "SELECT * FROM tbl_date_sequence");
if ($oldQuery && mysqli_num_rows($oldQuery) > 0) {
    while ($row = mysqli_fetch_assoc($oldQuery)) {
        $backupData[] = $row;
    }
    echo "   Backed up " . count($backupData) . " records\n";
} else {
    echo "   No data to backup\n";
}

// Step 2: Drop the old table
echo "\n2. Dropping old table structure...\n";
$dropResult = mysqli_query($config, "DROP TABLE IF EXISTS tbl_date_sequence");
if ($dropResult) {
    echo "   ✓ Old table dropped\n";
} else {
    echo "   ✗ Error dropping table: " . mysqli_error($config) . "\n";
    exit(1);
}

// Step 3: Create new table with correct structure
echo "\n3. Creating new table with correct structure...\n";
$createSQL = "CREATE TABLE tbl_date_sequence (
    tgl_surat DATE NOT NULL,
    jenis VARCHAR(50) NOT NULL,
    seq INT NOT NULL DEFAULT 1,
    PRIMARY KEY (tgl_surat, jenis),
    INDEX idx_jenis (jenis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$createResult = mysqli_query($config, $createSQL);
if ($createResult) {
    echo "   ✓ New table created with composite PRIMARY KEY (tgl_surat, jenis)\n";
} else {
    echo "   ✗ Error creating table: " . mysqli_error($config) . "\n";
    exit(1);
}

// Step 4: Restore data with proper jenis values
echo "\n4. Restoring data with proper structure...\n";
if (!empty($backupData)) {
    $restored = 0;
    foreach ($backupData as $row) {
        // If jenis doesn't exist in backup, default to 'umum'
        $jenis = $row['jenis'] ?? 'umum';
        $tgl = $row['tgl_surat'];
        $seq = intval($row['seq']);
        
        $insertSQL = "INSERT INTO tbl_date_sequence (tgl_surat, jenis, seq) VALUES ('$tgl', '$jenis', $seq)
                      ON DUPLICATE KEY UPDATE seq = GREATEST(seq, $seq)";
        if (mysqli_query($config, $insertSQL)) {
            $restored++;
        }
    }
    echo "   ✓ Restored $restored records\n";
} else {
    echo "   No data to restore\n";
}

// Step 5: Verify the new structure
echo "\n5. Verifying new structure...\n";
$verifyIndex = mysqli_query($config, "SHOW INDEXES FROM tbl_date_sequence WHERE Key_name='PRIMARY'");
if ($verifyIndex && mysqli_num_rows($verifyIndex) > 0) {
    echo "   ✓ Composite PRIMARY KEY verified:\n";
    while ($idx = mysqli_fetch_assoc($verifyIndex)) {
        echo "     - " . $idx['Column_name'] . "\n";
    }
} else {
    echo "   ✗ PRIMARY KEY not found!\n";
}

// Step 6: Check current data
echo "\n6. Current data in tbl_date_sequence:\n";
$checkData = mysqli_query($config, "SELECT * FROM tbl_date_sequence ORDER BY tgl_surat DESC LIMIT 5");
if ($checkData && mysqli_num_rows($checkData) > 0) {
    while ($row = mysqli_fetch_assoc($checkData)) {
        echo "   - tgl_surat: " . $row['tgl_surat'] . ", jenis: " . $row['jenis'] . ", seq: " . $row['seq'] . "\n";
    }
}

echo "\n✅ FIX COMPLETE! The sequence table now correctly supports separate counters per jenis.\n";
echo "This will prevent other document types from being affected when creating keuangan documents.\n";

?>

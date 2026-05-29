<?php
include 'src/include/config.php';

echo "=== MIGRATING DATA FROM tbl_date_sequence_old ===\n\n";

// Check if old table exists
$oldTableExists = mysqli_query($config, "SHOW TABLES LIKE 'tbl_date_sequence_old'");
if (!$oldTableExists || mysqli_num_rows($oldTableExists) === 0) {
    echo "No old table found. Nothing to migrate.\n";
    exit(0);
}

echo "1. Reading data from tbl_date_sequence_old...\n";
$oldData = [];
$readOld = mysqli_query($config, "SELECT * FROM tbl_date_sequence_old");
if ($readOld && mysqli_num_rows($readOld) > 0) {
    while ($row = mysqli_fetch_assoc($readOld)) {
        $oldData[] = $row;
    }
    echo "   Found " . count($oldData) . " old records\n";
    echo "   Sample: " . json_encode($oldData[0]) . "\n";
}

// Migrate data, preserving jenis if it exists
echo "\n2. Migrating data to new table...\n";
$migrated = 0;
$skipped = 0;
foreach ($oldData as $row) {
    $tgl = mysqli_real_escape_string($config, $row['tgl_surat']);
    $jenis = mysqli_real_escape_string($config, $row['jenis'] ?? 'umum');
    $seq = intval($row['seq']);
    
    // Insert with ON DUPLICATE KEY to avoid conflicts
    $sql = "INSERT INTO tbl_date_sequence (tgl_surat, jenis, seq) 
            VALUES ('$tgl', '$jenis', $seq)
            ON DUPLICATE KEY UPDATE seq = GREATEST(seq, $seq)";
    
    if (mysqli_query($config, $sql)) {
        $migrated++;
    } else {
        $skipped++;
        echo "   ⚠ Error migrating: " . mysqli_error($config) . "\n";
    }
}
echo "   ✓ Migrated: $migrated records\n";
if ($skipped > 0) echo "   ⚠ Skipped: $skipped records\n";

// Show final data
echo "\n3. Current data in tbl_date_sequence (sample):\n";
$sample = mysqli_query($config, "SELECT tgl_surat, jenis, seq FROM tbl_date_sequence ORDER BY tgl_surat DESC LIMIT 10");
if ($sample && mysqli_num_rows($sample) > 0) {
    while ($row = mysqli_fetch_assoc($sample)) {
        echo "   " . $row['tgl_surat'] . " | " . $row['jenis'] . " | seq=" . $row['seq'] . "\n";
    }
}

// Archive old table
echo "\n4. Archiving old table...\n";
$renameResult = mysqli_query($config, "RENAME TABLE tbl_date_sequence_old TO tbl_date_sequence_old_archive");
if ($renameResult) {
    echo "   ✓ Old table archived as tbl_date_sequence_old_archive\n";
} else {
    echo "   ✗ Could not rename old table\n";
}

echo "\n✅ MIGRATION COMPLETE!\n";

?>

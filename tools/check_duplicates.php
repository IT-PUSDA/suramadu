<?php
// Simple duplicate-check script for tbl_surat_keluar
require_once __DIR__ . '/../src/include/config.php';

if (!isset($config) || !$config) {
    echo "DB connection not available.\n";
    exit(1);
}

function runQuery($config, $sql) {
    $res = mysqli_query($config, $sql);
    if ($res === false) {
        echo "ERROR: " . mysqli_error($config) . "\n";
        return null;
    }
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
    return $rows;
}

echo "Checking duplicate no_surat (global)...\n";
$q1 = "SELECT no_surat, COUNT(*) AS c FROM tbl_surat_keluar GROUP BY no_surat HAVING c>1";
$r1 = runQuery($config, $q1);
if ($r1 === null) { exit(1); }
if (count($r1) === 0) {
    echo "No global duplicates found.\n\n";
} else {
    echo "Found global duplicates (no_surat,count):\n";
    foreach ($r1 as $row) { echo "  {$row['no_surat']}  -> {$row['c']}\n"; }
    echo "\n";
}

echo "Checking duplicate no_surat per jenis...\n";
$q2 = "SELECT no_surat, jenis, COUNT(*) AS c FROM tbl_surat_keluar GROUP BY no_surat, jenis HAVING c>1";
$r2 = runQuery($config, $q2);
if ($r2 === null) { exit(1); }
if (count($r2) === 0) {
    echo "No duplicates per-jenis found.\n\n";
} else {
    echo "Found duplicates per-jenis (no_surat, jenis, count):\n";
    foreach ($r2 as $row) { echo "  {$row['no_surat']} | {$row['jenis']}  -> {$row['c']}\n"; }
    echo "\n";
}

echo "Checking presence of unique index uq_no_surat...\n";
$q3 = "SELECT COUNT(*) AS c FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'tbl_surat_keluar' AND index_name = 'uq_no_surat'";
$r3 = runQuery($config, $q3);
if ($r3 === null) { exit(1); }
$hasIdx = (int)$r3[0]['c'] > 0;
echo "uq_no_surat exists: " . ($hasIdx ? 'YES' : 'NO') . "\n\n";

echo "Checking storage engine for relevant tables...\n";
$q4 = "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('tbl_surat_keluar','tbl_date_sequence','tbl_file_position','tbl_file_position_bidang','tbl_file_sequence')";
$r4 = runQuery($config, $q4);
if ($r4 === null) { exit(1); }
foreach ($r4 as $row) { echo "  {$row['TABLE_NAME']} -> {$row['ENGINE']}\n"; }

echo "\nDone.\n";

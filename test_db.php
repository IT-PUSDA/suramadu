<?php
require 'src/include/config.php';

// examine sequence table
$result = mysqli_query($config, "SHOW TABLES LIKE 'tbl_date_sequence'");
echo "tbl_date_sequence exists? ".(mysqli_num_rows($result)?'yes':'no')."\n";
if(mysqli_num_rows($result)){
    $r2 = mysqli_query($config, "SELECT * FROM tbl_date_sequence ORDER BY tgl_surat DESC LIMIT 20");
    while($r=mysqli_fetch_assoc($r2)){
        echo "seq[{$r['tgl_surat']}] = {$r['seq']}\n";
    }
}

// show any old table
$resold = mysqli_query($config, "SHOW TABLES LIKE 'tbl_date_sequence_old'");
echo "tbl_date_sequence_old exists? ".(mysqli_num_rows($resold)?'yes':'no')."\n";
if(mysqli_num_rows($resold)){
    $q = mysqli_query($config, "SELECT COUNT(*) as c FROM tbl_date_sequence_old");
    $r = mysqli_fetch_assoc($q);
    echo "old rows: {$r['c']}\n";
}

$result2 = mysqli_query($config, "SHOW INDEX FROM tbl_surat_keluar WHERE Key_name='uq_no_surat'");
echo "uq_no_surat rows: ".mysqli_num_rows($result2)."\n";
while($row=mysqli_fetch_assoc($result2)){
    print_r($row);
}

// also show counts for today's date
$today = date('Y-m-d');
$q3 = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE tgl_surat='$today'");
$r3 = mysqli_fetch_assoc($q3);
echo "surat count for $today = {$r3['c']}\n";

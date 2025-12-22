<?php
require_once __DIR__ . '/../src/include/config.php';
header('Content-Type: text/plain; charset=utf-8');
echo "Recent produk_hukum rows:\n";
$res = mysqli_query($config, "SELECT id_surat,id_user,jenis,status,id_arsip_berkas,no_surat,tgl_dibuat FROM tbl_surat_keluar WHERE jenis='produk_hukum' ORDER BY id_surat DESC LIMIT 20");
if (!$res) { echo "Query failed: " . mysqli_error($config) . "\n"; exit(1); }
while ($r = mysqli_fetch_assoc($res)) {
    echo sprintf("id=%s | id_user=%s | jenis=%s | status=%s | id_arsip_berkas=%s | no_surat=%s | tgl_dibuat=%s\n",
        $r['id_surat'],$r['id_user'],$r['jenis'],$r['status'],$r['id_arsip_berkas'],$r['no_surat'],$r['tgl_dibuat']);
}

// Also show operator mapping resolution for current session user if possible
session_start();
if (!empty($_SESSION['username'])) {
    echo "\nCurrent session username: " . $_SESSION['username'] . " id_user=" . ($_SESSION['id_user'] ?? '') . " admin=" . ($_SESSION['admin'] ?? '') . "\n";
}

?>

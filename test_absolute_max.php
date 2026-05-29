<?php
require 'src/include/config.php';
$max = 0;
$max_str = '';
$q = mysqli_query($config, 'SELECT no_surat FROM tbl_surat_keluar');
while($r = mysqli_fetch_assoc($q)) {
    $p = explode('/', $r['no_surat']);
    if(count($p) >= 2) {
        $n = (int)preg_replace('/[^0-9]/', '', $p[1]);
        if($n > $max) {
            $max = $n;
            $max_str = $r['no_surat'];
        }
    }
}
echo "Absolute Max: $max from $max_str\n";
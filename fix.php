<?php
$s = chr(39).'.'.chr(36).'extra'.chr(43).chr(39).chr(34).chr(62);
$r = chr(39).'.'.chr(36).'extra'.chr(46).chr(39).chr(34).chr(62);
foreach(['c:/laragon/www/ams/src/SuratKeluar/transaksi_surat_keluar_keuangan.php', 'c:/laragon/www/ams/src/SuratKeluar/transaksi_surat_keluar_nota_dinas.php', 'c:/laragon/www/ams/src/SuratKeluar/transaksi_surat_keluar_produk_hukum.php'] as $f) {
  file_put_contents($f, str_replace($s, $r, file_get_contents($f)));
}
$f2 = 'c:/laragon/www/ams/src/SuratKeluar/proses_tambah_surat_keluar_nota_dinas.php';
file_put_contents($f2, str_replace('\Dconfig', '$config', file_get_contents($f2)));
echo "OK";

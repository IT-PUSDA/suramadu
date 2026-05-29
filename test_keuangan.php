<?php
require 'src/include/config.php';
$q = mysqli_query($config, "SELECT id_surat, no_surat, tgl_surat FROM tbl_surat_keluar WHERE jenis='keuangan' ORDER BY id_surat DESC LIMIT 10");
while($r=mysqli_fetch_assoc($q)) print_r($r);
<?php
$f = 'src/SuratKeluar/proses_tambah_surat_keluar_nota_dinas.php';
$c = file_get_contents($f);
$c = str_replace(
"    // Use global sequential counter (kontinyu: 10644, 10645, 120101, ...)\r\n    \$next_num = get_next_global_surat_number(\$config);\r\n    \$pos_code = (string)\$next_num;\r\n    \$no_surat = \$nkode . '/' . \$pos_code . '/' . \$bidang . '/' . \$year;",
"    \$pos_code = get_sequence_code_with_sisipan(\Dconfig, (int)\$year, \$bidang, 'nota_dinas', \$tgl_surat);\n    \$no_surat = \$nkode . '/' . \$pos_code . '/' . \$bidang . '/' . \$year;", $c);
$c = str_replace(
"    // Use global sequential counter (kontinyu: 10644, 10645, 120101, ...)\n    \$next_num = get_next_global_surat_number(\$config);\n    \<pos_code = (string)\$next_num;\n    \$no_surat = \$nkode . '/' . \$pos_code . '/' . \$bidang . '/' . \$year;",
"    \$pos_code = get_sequence_code_with_sisipan(\Dconfig, (int)\$year, \$bidang, 'nota_dinas', \$tgl_surat);\n    \$no_surat = \$nkode . '/' . \$pos_code . '/' . \$bidang . '/' . \$year;", $c);
file_put_contents($f, $c);

$f = 'src/SuratKeluar/proses_tambah_surat_keluar_produk_hukum.php';
$c = file_get_contents($f);
$c = str_replace(
"    // Use global sequential counter (kontinyu: 10644, 10645, 120101, ...)\r\n    \$next_num = get_next_global_surat_number(\$config);\r\n    \$pos_code = (string)\$next_num;\r\n    \$no_surat = \$nkode . '/' . \$pos_code . '/' . \$bidang . '/' . \$year;",
"    \$pos_code = get_sequence_code_with_sisipan(\$config, (int)\$year, \$bidang, 'produk_hukum', \$tgl_surat);\n    \$no_surat = \$nkode . '/' . \$pos_code . '/' . \$bidang . '/' . \$year;", $c);
$c = str_replace(
"    // Use global sequential counter (kontinyu: 10644, 10645, 120101, ...)\n    \$next_num = get_next_global_surat_number(\$config);\n    \<pos_code = (string)\$next_num;\n    \$no_surat = \$nkode . '/' . \$pos_code . '/' . \$bidang . '/' . \$year;",
"    \$pos_code = get_sequence_code_with_sisipan(\Dconfig, (int)\$year, \$bidang, 'produk_hukum', \$tgl_surat);\n    \$no_surat = \$nkode . '/' . \$pos_code . '/' . \$bidang . '/' . \$year;", $c);
file_put_contents($f, $c);


$f = 'src/NotaDinas/tambah_nota_dinas.php';
$c = file_get_contents($f);
$c = str_replace(
"                // Use global sequential counter (kontinyu)\r\n                \$next_num = get_next_global_surat_number(\$config);\r\n                \$pos_code = (string)\$next_num;\r\n                // Build nomor nota dinas\r\n                \$no_notdin = \$pos_code . '/' . \$bidang_code . '/' . \$year_notdin;",
"                \$pos_code = get_sequence_code_with_sisipan(\$config, (int)\$year_notdin, \$bidang_code, 'nota_dinas', \tgl_notdin);\n                \$no_notdin = \$pos_code . '/' . \$bidang_code . '/' . \$year_notdin;", $c);
$c = str_replace(
"                // Use global sequential counter (kontinyu)\n                \$next_num = get_next_global_surat_number(\$config);\n                \$pos_code = (string)\$next_num;\n                // Build nomor nota dinas\n                \$no_notdin = \$pos_code . '/' . \$bidang_code . '/' . \$year_notdin;",
"                \$pos_code = get_sequence_code_with_sisipan(\Dconfig, (int)\$year_notdin, \$bidang_code, 'nota_dinas', \$tgl_notdin);\n                \$no_notdin = \<pos_code . '/' . \$bidang_code . '/' . \$year_notdin;", $c);
file_put_contents($f, $c);
?>
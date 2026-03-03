<?php
require 'src/include/config.php';
$result = mysqli_query($config, "SHOW TABLES LIKE 'tbl_date_sequence'");
echo "tbl_date_sequence rows: ".mysqli_num_rows($result)."\n";
$result2 = mysqli_query($config, "SHOW INDEX FROM tbl_surat_keluar WHERE Key_name='uq_no_surat'");
echo "uq_no_surat rows: ".mysqli_num_rows($result2)."\n";
while($row=mysqli_fetch_assoc($result2)){
    print_r($row);
}

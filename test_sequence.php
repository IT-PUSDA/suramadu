<?php
require 'src/include/config.php';
require 'src/include/file_sequence.php';

date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');
for ($i=0; $i<5; $i++) {
    $code = get_sequence_code_with_sisipan($config, date('Y'), 'ignored', 'ignored', $today);
    echo "sequence: $code\n";
}

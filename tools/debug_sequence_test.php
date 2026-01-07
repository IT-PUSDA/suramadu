<?php
require_once __DIR__ . '/../src/include/config.php';
require_once __DIR__ . '/../src/include/file_sequence.php';

$tests = [
    ['year' => 2026, 'bidang' => '104.1', 'jenis' => 'umum'],
    ['year' => 2026, 'bidang' => '104.1', 'jenis' => 'nota_dinas'],
    ['year' => 2026, 'bidang' => '104.1', 'jenis' => 'keuangan'],
    ['year' => 2026, 'bidang' => '104.2', 'jenis' => 'umum'],
];

foreach ($tests as $t) {
    $s = next_position_sequence_for_year_and_bidang($config, $t['year'], $t['bidang'], $t['jenis']);
    $label = page_line_label_from_seq($s, 40);
    echo sprintf("year=%s bidang=%s jenis=%s -> seq=%s label=%s\n", $t['year'], $t['bidang'], $t['jenis'], $s, $label);
}

?>
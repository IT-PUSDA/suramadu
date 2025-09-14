<?php
// Utility functions for yearly file numbering (zero-padded, resets each year)

if (!function_exists('ensure_file_no_column')) {
    function ensure_file_no_column(mysqli $config): bool {
        $has = false;
        $res = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'file_no'");
        if ($res && mysqli_num_rows($res) === 1) {
            $has = true;
        } else {
            @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN file_no INT UNSIGNED NULL DEFAULT NULL");
            $res2 = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'file_no'");
            if ($res2 && mysqli_num_rows($res2) === 1) { $has = true; }
        }
        return $has;
    }
}

if (!function_exists('next_file_sequence_for_year')) {
    // Get next sequence number for given year across all surat_keluar records (regardless of jenis/user)
    function next_file_sequence_for_year(mysqli $config, int $year): int {
        // Prefer MAX(file_no) filtered by year of tgl_surat
        $year = (int)$year;
        $max = 0;
        $q = mysqli_query($config, "SELECT MAX(file_no) AS m FROM tbl_surat_keluar WHERE YEAR(tgl_surat) = '$year'");
        if ($q) {
            $r = mysqli_fetch_assoc($q);
            $max = (int)($r['m'] ?? 0);
        }
        $next = $max + 1;
        // Avoid zero or negative
        if ($next < 1) { $next = 1; }
        return $next;
    }
}

if (!function_exists('format_file_sequence_label')) {
    // Format sequence with at least 4 digits (0001, 0010, 10000, ...)
    function format_file_sequence_label(int $seq): string {
        return str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}

?>

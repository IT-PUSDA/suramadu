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
        $year = (int)$year;
        // Ensure supporting sequence table exists
        mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_file_sequence (
            `year` INT NOT NULL PRIMARY KEY,
            `seq` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Obtain the next sequence atomically using a transaction + SELECT ... FOR UPDATE
        $next = 1;
        // Turn off autocommit for transaction
        $autocommit = mysqli_get_server_info($config); // dummy read to keep code style; autocommit handled below
        mysqli_begin_transaction($config);
        try {
            $y = mysqli_real_escape_string($config, (string)$year);
            $qr = mysqli_query($config, "SELECT seq FROM tbl_file_sequence WHERE `year` = '$y' FOR UPDATE");
            if ($qr && mysqli_num_rows($qr) > 0) {
                $ro = mysqli_fetch_assoc($qr);
                $current = (int)($ro['seq'] ?? 0);
                $next = $current + 1;
                mysqli_query($config, "UPDATE tbl_file_sequence SET seq = " . intval($next) . " WHERE `year` = '$y'");
            } else {
                // initialize for this year
                $next = 1;
                mysqli_query($config, "INSERT INTO tbl_file_sequence(`year`, seq) VALUES ('$y', 1)");
            }
            mysqli_commit($config);
        } catch (Exception $e) {
            mysqli_rollback($config);
            // fallback: compute from MAX(file_no) to avoid blocking functionality
            $q = mysqli_query($config, "SELECT MAX(file_no) AS m FROM tbl_surat_keluar WHERE YEAR(tgl_surat) = '" . intval($year) . "'");
            $max = 0;
            if ($q) { $r = mysqli_fetch_assoc($q); $max = (int)($r['m'] ?? 0); }
            $next = $max + 1;
            if ($next < 1) { $next = 1; }
        }
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

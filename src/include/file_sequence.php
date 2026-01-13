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

if (!function_exists('next_position_sequence_for_year_and_bidang')) {
    // Maintain a sequential counter per (year, bidang, jenis) to derive page/line positions.
    function next_position_sequence_for_year_and_bidang(mysqli $config, int $year, string $bidang, string $jenis): int {
        $year = (int)$year;
        $bidang = mysqli_real_escape_string($config, (string)$bidang);
        $jenis = mysqli_real_escape_string($config, (string)$jenis);

        // Ensure table exists
        mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_file_position (
            `year` INT NOT NULL,
            `bidang` VARCHAR(50) NOT NULL,
            `jenis` VARCHAR(50) NOT NULL,
            `seq` INT NOT NULL,
            PRIMARY KEY (`year`, `bidang`, `jenis`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $next = 1;
        mysqli_begin_transaction($config);
        try {
            $qr = mysqli_query($config, "SELECT seq FROM tbl_file_position WHERE `year` = '" . intval($year) . "' AND bidang = '" . $bidang . "' AND jenis = '" . $jenis . "' FOR UPDATE");
            if ($qr && mysqli_num_rows($qr) > 0) {
                $ro = mysqli_fetch_assoc($qr);
                $current = (int)($ro['seq'] ?? 0);
                $next = $current + 1;
                mysqli_query($config, "UPDATE tbl_file_position SET seq = " . intval($next) . " WHERE `year` = '" . intval($year) . "' AND bidang = '" . $bidang . "' AND jenis = '" . $jenis . "'");
            } else {
                $next = 1;
                mysqli_query($config, "INSERT INTO tbl_file_position(`year`, bidang, jenis, seq) VALUES ('" . intval($year) . "', '" . $bidang . "', '" . $jenis . "', 1)");
            }
            mysqli_commit($config);
        } catch (Exception $e) {
            mysqli_rollback($config);
            // Fallback: estimate sequence by counting existing entries
            $q = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE YEAR(tgl_surat) = '" . intval($year) . "' AND bidang = '" . $bidang . "' AND jenis = '" . $jenis . "'");
            $c = 0; if ($q) { $r = mysqli_fetch_assoc($q); $c = (int)($r['c'] ?? 0); }
            $next = $c + 1;
        }
        return $next;
    }
}

if (!function_exists('page_line_label_from_seq')) {
    // Given a linear sequence and lines-per-page (default 40), return page+line label like "0101" (page=01 line=01)
    function page_line_label_from_seq(int $seq, int $perPage = 40): string {
        $seq = max(1, (int)$seq);
        $perPage = max(1, (int)$perPage);
        $page = intdiv($seq - 1, $perPage) + 1;
        $line = (($seq - 1) % $perPage) + 1;
        $page_str = str_pad((string)$page, 2, '0', STR_PAD_LEFT);
        $line_str = str_pad((string)$line, 2, '0', STR_PAD_LEFT);
        return $page_str . $line_str;
    }
}

if (!function_exists('next_position_sequence_for_year_and_bidang_global')) {
    // Maintain a sequential counter per (year, bidang) to derive page/line positions across all kinds.
    // This is used when the counter must be shared between all 'jenis' values (global per bidang).
    function next_position_sequence_for_year_and_bidang_global(mysqli $config, int $year, string $bidang): int {
        $year = (int)$year;
        $bidang = mysqli_real_escape_string($config, (string)$bidang);

        // Ensure table exists
        mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_file_position_bidang (
            `year` INT NOT NULL,
            `bidang` VARCHAR(50) NOT NULL,
            `seq` INT NOT NULL,
            PRIMARY KEY (`year`, `bidang`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $next = 1;
        mysqli_begin_transaction($config);
        try {
            $qr = mysqli_query($config, "SELECT seq FROM tbl_file_position_bidang WHERE `year` = '" . intval($year) . "' AND bidang = '" . $bidang . "' FOR UPDATE");
            if ($qr && mysqli_num_rows($qr) > 0) {
                $ro = mysqli_fetch_assoc($qr);
                $current = (int)($ro['seq'] ?? 0);
                $next = $current + 1;
                mysqli_query($config, "UPDATE tbl_file_position_bidang SET seq = " . intval($next) . " WHERE `year` = '" . intval($year) . "' AND bidang = '" . $bidang . "'");
            } else {
                $next = 1;
                mysqli_query($config, "INSERT INTO tbl_file_position_bidang(`year`, bidang, seq) VALUES ('" . intval($year) . "', '" . $bidang . "', 1)");
            }
            mysqli_commit($config);
        } catch (Exception $e) {
            mysqli_rollback($config);
            // Fallback: estimate sequence by counting existing entries across ALL jenis
            $q = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE YEAR(tgl_surat) = '" . intval($year) . "' AND bidang = '" . $bidang . "'");
            $c = 0; if ($q) { $r = mysqli_fetch_assoc($q); $c = (int)($r['c'] ?? 0); }
            $next = $c + 1;
        }
        return $next;
    }
}

if (!function_exists('get_sequence_code_with_sisipan')) {
    /**
     * REVISI: Menggunakan format HariKe+SequenceHarian (misal 1301 untuk Hari ke-13, urutan 01).
     * Format: [DayOfYear][Sequence2digit]
     * Logika ini menggantikan sistem halaman/baris dan sistem sisipan sebelumnya.
     * Jika backdated, otomatis urut karena hanya menghitung jumlah hari itu.
     */
    function get_sequence_code_with_sisipan(mysqli $config, int $year, string $bidang, string $jenis, string $tgl_surat): string {
        $year = (int)$year;
        $bidang = mysqli_real_escape_string($config, $bidang);
        $jenis = mysqli_real_escape_string($config, $jenis);
        $tgl_surat = mysqli_real_escape_string($config, $tgl_surat);

        // 1. Hitung Day Of Year (1-366)
        $ts = strtotime($tgl_surat);
        $dayOfYear = (int)date('z', $ts) + 1;

        // 2. Hitung jumlah surat yang SUDAH ADA pada tanggal tersebut untuk bidang & jenis yang sama
        //    Gunakan logika COUNT + 1 untuk mendapatkan nomor berikutnya.
        //    Ini otomatis menangani tanggal maju (0 surat -> no 1) maupun mundur (ada 5 surat -> no 6).
        $qCount = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar 
                                         WHERE tgl_surat = '$tgl_surat' 
                                         AND bidang = '$bidang' 
                                         AND jenis = '$jenis'");
        
        $nextSeq = 1;
        if ($qCount && mysqli_num_rows($qCount) > 0) {
            $d = mysqli_fetch_assoc($qCount);
            $nextSeq = (int)$d['c'] + 1;
        }

        // 3. Format Output: [DayOfYear][Sequence]
        //    DayOfYear: Tidak di-pad (1, 13, 365)
        //    Sequence: Pad 2 digit (01, 05, 10, 99, 100...)
        $seqStr = sprintf('%02d', $nextSeq);
        
        return $dayOfYear . $seqStr;
    }
}

?>

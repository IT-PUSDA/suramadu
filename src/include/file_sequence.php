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
     * Menghasilkan kode urut (misal 0101 atau 0101.A) berdasarkan tanggal surat.
     * Jika tanggal surat mundur (sebelum surat terakhir), gunakan logika sisipan.
     * Jika tanggal surat maju/terkini, gunakan logika counter normal.
     */
    function get_sequence_code_with_sisipan(mysqli $config, int $year, string $bidang, string $jenis, string $tgl_surat): string {
        $year = (int)$year;
        $bidang = mysqli_real_escape_string($config, $bidang);
        $jenis = mysqli_real_escape_string($config, $jenis);
        $tgl_surat = mysqli_real_escape_string($config, $tgl_surat);

        // 1. Cek apakah ini backdated (ada surat dengan tanggal > tgl_surat di tahun & bidang yg sama)
        // Kita cek MAX tgl_surat yang ada
        $qMax = mysqli_query($config, "SELECT MAX(tgl_surat) as max_date FROM tbl_surat_keluar WHERE YEAR(tgl_surat) = $year AND bidang = '$bidang'");
        
        $is_backdated = false;
        if ($qMax && mysqli_num_rows($qMax) > 0) {
            $dMax = mysqli_fetch_assoc($qMax);
            if (!empty($dMax['max_date']) && $dMax['max_date'] > $tgl_surat) {
                $is_backdated = true;
            }
        }

        // Jika backdated, cari induk untuk disisipkan
        if ($is_backdated) {
            // Cari surat terakhir yang terbit pada tanggal <= tgl_surat
            // Urutkan berdasarkan tanggal DESC, dan ID DESC (untuk dapat yang paling akhir diinput pada tgl itu)
            $qRef = mysqli_query($config, "SELECT no_surat FROM tbl_surat_keluar 
                                           WHERE YEAR(tgl_surat) = $year AND bidang = '$bidang' AND tgl_surat <= '$tgl_surat'
                                           ORDER BY tgl_surat DESC, id_surat DESC LIMIT 1");
            
            $baseCode = "0000"; // Default jika menyisip di paling awal (sebelum surat pertama)
            
            if ($qRef && mysqli_num_rows($qRef) > 0) {
                $dRef = mysqli_fetch_assoc($qRef);
                $refNo = $dRef['no_surat'];
                // Format asumsi: KODE/POS/BIDANG/TAHUN
                $parts = explode('/', $refNo);
                if (count($parts) >= 4) {
                    $baseCode = $parts[1]; // Ambil bagian POS (misal 0101 atau 0101.A)
                    // Ambil root utamanya (angka saja) agar konsisten
                    $baseCodeRaw = preg_replace('/[^0-9]/', '', $baseCode); 
                    $baseCode = str_pad($baseCodeRaw, 4, '0', STR_PAD_LEFT);
                }
            }
            
            // Sekarang cari pos terbesar yang menggunakan baseCode ini di tahun yg sama untuk menentukan suffix berikutnya
            // Pola: %/baseCode%/%
            $qSibs = mysqli_query($config, "SELECT no_surat FROM tbl_surat_keluar 
                                            WHERE YEAR(tgl_surat) = $year AND bidang = '$bidang' 
                                            AND no_surat LIKE '%/$baseCode%/$bidang/$year'");
            
            $usedSuffixes = [];
            while ($row = mysqli_fetch_assoc($qSibs)) {
                $p = explode('/', $row['no_surat']);
                if (count($p) >= 4) {
                    $codeVal = $p[1]; // misal 0101, 0101.A, 0101.B
                    if (strpos($codeVal, '.') !== false) {
                        $suf = substr($codeVal, strpos($codeVal, '.') + 1);
                        $usedSuffixes[] = strtoupper($suf);
                    } else {
                        // Jika exact match dengan baseCode, suffix dianggap kosong (nilai 0 / @)
                        if ($codeVal === $baseCode) {
                            $usedSuffixes[] = '@'; // ASCII 64, sebelum A (65)
                        }
                    } 
                }
            }
            
            // Tentukan suffix berikutnya
            // Logic: Cari max existing suffix (numeric), lalu +1
            $nextSuffix = '1';
            if (!empty($usedSuffixes)) {
                $maxVal = 0;
                foreach ($usedSuffixes as $sx) {
                    if (is_numeric($sx)) {
                        $v = (int)$sx;
                        if ($v > $maxVal) $maxVal = $v;
                    }
                }
                $nextSuffix = (string)($maxVal + 1);
            }
            
            return $baseCode . '.' . $nextSuffix;

        } else {
            // Normal - Generate Next Sequence
            // Panggil fungsi asli yang meng-increment counter DB
            $seq = next_position_sequence_for_year_and_bidang($config, $year, $bidang, $jenis);
            return page_line_label_from_seq($seq, 40);
        }
    }
}

?>

<?php
// Utility functions for yearly file numbering (zero-padded, resets each year)

if (!function_exists('get_next_global_surat_number')) {
    /**
     * Get next GLOBAL sequential surat number.
     * Finds the maximum number from all no_surat and increments by 1.
     * This ensures continuous numbering across all document types (umum, keuangan, produk_hukum, nota_dinas).
     * 
     * Format preserved: kode / [NUMBER] / bidang / year
     * Where [NUMBER] = sequential counter (10644, 10645, 120101, etc.)
     */
    function get_next_global_surat_number(mysqli $config): int {
        // Get all no_surat entries and extract the numeric part
        $query = mysqli_query($config, 
            "SELECT no_surat FROM tbl_surat_keluar ORDER BY id_surat DESC LIMIT 1000");
        
        $max_num = 0;
        if ($query && mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_assoc($query)) {
                $no_surat = $row['no_surat'];
                // Format: kode / nomor / bidang / tahun
                // Extract the numeric part after first slash and before next slash
                $parts = explode('/', $no_surat);
                if (count($parts) >= 2) {
                    $num_part = $parts[1];
                    // Remove non-numeric characters (letters, spaces, etc.)
                    $num_only = (int) preg_replace('/[^0-9]/', '', $num_part);
                    if ($num_only > $max_num) {
                        $max_num = $num_only;
                    }
                }
            }
        }
        
        // Return next number
        $next = $max_num + 1;
        return ($next < 1) ? 1 : $next;
    }
}

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

if (!function_exists('ensure_no_surat_unique_index')) {
    /**
     * Checks that a unique index exists on tbl_surat_keluar.no_surat and creates it
     * if missing. This acts as a safety net to prevent duplicates during races.
     *
     * The function is intentionally idempotent; the ALTER statement will be
     * executed only once the first time it runs, thereafter it has no effect.
     */
    function ensure_no_surat_unique_index(mysqli $config): void {
        // MySQL does not have an "IF NOT EXISTS" clause for ALTER INDEX, so we
        // detect presence via INFORMATION_SCHEMA and create conditionally.
        $res = mysqli_query($config, "SELECT COUNT(*) AS c
            FROM information_schema.STATISTICS
            WHERE table_schema = DATABASE()
              AND table_name = 'tbl_surat_keluar'
              AND index_name = 'uq_no_surat'");
        if ($res) {
            $row = mysqli_fetch_assoc($res);
            if (((int)$row['c']) === 0) {
                mysqli_query($config, "ALTER TABLE tbl_surat_keluar
                    ADD CONSTRAINT uq_no_surat UNIQUE (no_surat)");
            }
        }
    }
}

if (!function_exists('ensure_date_sequence_table')) {
    function ensure_date_sequence_table(mysqli $config): void {
        $tableExists = false;
        $res = mysqli_query($config, "SHOW TABLES LIKE 'tbl_date_sequence'");
        if ($res && mysqli_num_rows($res) > 0) {
            $tableExists = true;
        }

        if (!$tableExists) {
            mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_date_sequence (
                tgl_surat DATE NOT NULL,
                jenis VARCHAR(50) NOT NULL,
                seq INT NOT NULL,
                PRIMARY KEY (tgl_surat, jenis)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            return;
        }

        $cols = mysqli_query($config, "SHOW COLUMNS FROM tbl_date_sequence");
        $hasJenis = false;
        $pkCols = [];

        if ($cols) {
            while ($col = mysqli_fetch_assoc($cols)) {
                if (($col['Field'] ?? '') === 'jenis') {
                    $hasJenis = true;
                }
            }
        }

        $keys = mysqli_query($config, "SHOW KEYS FROM tbl_date_sequence WHERE Key_name = 'PRIMARY'");
        if ($keys) {
            while ($key = mysqli_fetch_assoc($keys)) {
                $pkCols[] = $key['Column_name'] ?? '';
            }
        }

        $hasCompositePk = count(array_unique(array_filter($pkCols))) === 2
            && in_array('tgl_surat', $pkCols, true)
            && in_array('jenis', $pkCols, true);

        if (!$hasJenis || !$hasCompositePk) {
            $backupName = 'tbl_date_sequence_old_' . time();
            @mysqli_query($config, "RENAME TABLE tbl_date_sequence TO " . $backupName);
            mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_date_sequence (
                tgl_surat DATE NOT NULL,
                jenis VARCHAR(50) NOT NULL,
                seq INT NOT NULL,
                PRIMARY KEY (tgl_surat, jenis)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}

if (!function_exists('get_sequence_code_with_sisipan')) {
    /**
     * REVISI: Menggunakan format HariKe+SequenceHarian (misal 1301 untuk Hari ke-13, urutan 01).
     * Format: [DayOfYear][Sequence2digit]
     * Logika ini menggantikan sistem halaman/baris dan sistem sisipan sebelumnya.
     * Jika backdated, otomatis urut karena hanya menghitung jumlah hari itu.
     *
     * Untuk menjaga konsistensi ketika dua pengguna melakukan pencatatan
     * bersamaan, perhitungan sequence dilakukan secara atomik dengan memanfaatkan
     * tabel penampung (`tbl_date_sequence`) dan transaksi InnoDB. Fallback ke
     * perhitungan hitung() hanya terjadi jika terjadi error pada transaksi.
     */
    function get_sequence_code_with_sisipan(mysqli $config, int $year, string $bidang, string $jenis, string $tgl_surat): string {
        $year = (int)$year;
        $bidang = mysqli_real_escape_string($config, $bidang);
        $jenis = mysqli_real_escape_string($config, $jenis);
        $tgl_surat = mysqli_real_escape_string($config, $tgl_surat);

        // 1. Hitung Day Of Year (1-366)
        $ts = strtotime($tgl_surat);
        if ($ts === false) {
            $ts = strtotime(date('Y-m-d'));
        }
        $dayOfYear = (int)date('z', $ts) + 1;

        // ensure structural safety for the sequence table before using it
        ensure_date_sequence_table($config);
        ensure_no_surat_unique_index($config);

        // 2. Dapatkan sequence untuk tanggal+jenis secara atomik (per-jenis per-hari).
        //    Setiap jenis surat memiliki counter terpisah untuk hari yang sama.
        //    Menggunakan locking atomik agar tidak ada duplikat saat concurrent access.
        //
        //    Jika tabel lama (hanya tgl_surat PRIMARY KEY) ada, rename lalu buat tabel baru.
        $chk = mysqli_query($config, "SHOW COLUMNS FROM tbl_date_sequence LIKE 'jenis'");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            // old table structure (global per-date only) atau tabel kosong; migrate
            @mysqli_query($config, "RENAME TABLE tbl_date_sequence TO tbl_date_sequence_old");
        }
        mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_date_sequence (
            tgl_surat DATE NOT NULL,
            jenis VARCHAR(50) NOT NULL,
            seq INT NOT NULL,
            PRIMARY KEY (tgl_surat, jenis)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Count actual surat pada tanggal+jenis ini untuk reconciliation
        $actualCountQ = mysqli_query($config,
            "SELECT COUNT(*) AS c FROM tbl_surat_keluar 
             WHERE tgl_surat = '$tgl_surat' AND jenis = '$jenis'");
        $actualCount = 0;
        if ($actualCountQ && mysqli_num_rows($actualCountQ) > 0) {
            $rct = mysqli_fetch_assoc($actualCountQ);
            $actualCount = (int)($rct['c'] ?? 0);
        }

        $nextSeq = 1;
        mysqli_begin_transaction($config);
        try {
            // Atomic INSERT...ON DUPLICATE KEY: ensures no duplicate seq even with
            // concurrent requests. MySQL serializes these internally per primary key.
            $insSql = "INSERT INTO tbl_date_sequence(tgl_surat,jenis,seq)
                       VALUES ('$tgl_surat','$jenis',1)
                       ON DUPLICATE KEY UPDATE seq = seq + 1";
            $qr = mysqli_query($config, $insSql);
            if ($qr) {
                $qr2 = mysqli_query($config,
                    "SELECT seq FROM tbl_date_sequence
                       WHERE tgl_surat = '$tgl_surat' AND jenis = '$jenis'");
                if ($qr2 && mysqli_num_rows($qr2) > 0) {
                    $ro = mysqli_fetch_assoc($qr2);
                    $nextSeq = (int)($ro['seq'] ?? 1);
                }
            }
            mysqli_commit($config);
        } catch (Exception $e) {
            mysqli_rollback($config);
            // fallback: gunakan jumlah nyata
            $nextSeq = $actualCount + 1;
        }

        // Reconciliation: pastikan counter selalu >= actual count.
        // Menangani kasus migrasi/reset tabel.
        if ($nextSeq <= $actualCount) {
            $nextSeq = $actualCount + 1;
            mysqli_query($config,
                "INSERT INTO tbl_date_sequence(tgl_surat,jenis,seq)
                    VALUES('$tgl_surat','$jenis',".intval($nextSeq).")
                  ON DUPLICATE KEY UPDATE seq=".intval($nextSeq));
        }

        // 3. Format Output: [DayOfYear][Sequence]
        //    DayOfYear: Di-pad minimal 2 digit (01, 05, 13, 100, 365)
        //    Sequence: Pad 2 digit (01, 05, 10, 99, 100...)
        //    Hasil: 0101 (min 4 digit), 1001 (4 digit), 10001 (5 digit)
        $dayStr = sprintf('%02d', $dayOfYear);
        $seqStr = sprintf('%02d', $nextSeq);
        
        return $dayStr . $seqStr;
    }
}

?>

<?php
// Central mapping & helpers for bidang codes.
// Provides: resolve_bidang_code_from_session(), print_bidang_options($locked)
// Usage: Include where nomor surat / form bidang selection needed.

if (!function_exists('resolve_bidang_code_from_session')) {
    function resolve_bidang_code_from_session(): ?string {
        if (!isset($_SESSION)) { return null; }
        $u = strtoupper($_SESSION['username'] ?? '');
        $n = strtoupper($_SESSION['nama'] ?? '');
        // Map kode -> tokens (any token contained in username or nama matches)
        $MAP = [
            '104.1'    => ['SEKRETARIAT','TU'],
            '104.2'    => ['PSDA'],
            '104.3'    => ['SWP'],
            '104.4'    => ['IRIGASI'],
            '104.5'    => ['BINFAT'],
            '104.6.02' => ['KEDIRI'],
            '104.6.02' => ['MALANG'],
            '104.6.02' => ['SURABAYA'],
            '104.6.05' => ['BOJONEGORO'],
            '104.6.05' => ['MADIUN'],
            '104.6.06' => ['BONDOWOSO'],
            '104.6.07' => ['LUMAJANG'],
            '104.6.08' => ['PASURUAN'],
            '104.6.09' => ['MADURA'],
        ];
        foreach ($MAP as $code => $tokens) {
            foreach ($tokens as $tk) {
                $tkU = strtoupper($tk);
                if ($u === $tkU || $n === $tkU || strpos($u,$tkU) !== false || ($n && strpos($n,$tkU) !== false)) {
                    return $code;
                }
            }
        }
        return null;
    }
}

if (!function_exists('print_bidang_options')) {
    function print_bidang_options(?string $locked = null) {
        $options = [
            '104.1'    => 'Sekretariat',
            '104.2'    => 'PSDA',
            '104.3'    => 'SWP',
            '104.4'    => 'Irigasi',
            '104.5'    => 'Binfat',
            '104.6.02' => 'UPT Kediri',
            '104.6.02' => 'Korwil Malang',
            '104.6.02' => 'Korwil Surabaya',
            '104.6.05' => 'UPT Bojonegoro',
            '104.6.05' => 'Korwil Madiun',
            '104.6.06' => 'UPT Bondowoso',
            '104.6.07' => 'UPT Lumajang',
            '104.6.08' => 'UPT Pasuruan',
            '104.6.09' => 'UPT Madura',
        ];
        foreach ($options as $val => $label) {
            $sel = ($locked !== null && $locked === $val) ? ' selected' : '';
            echo '<option value="'.$val.'"'.$sel.'>'.$label.'</option>';
        }
    }
}
?>

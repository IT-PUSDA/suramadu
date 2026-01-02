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
        // Map individual tokens -> kode bidang. Use token keys so duplicates
        // (multiple UPTs with same base code) are allowed.
        $TOKEN_MAP = [
            'SEKRETARIAT'      => '104.1',
            'TU'               => '104.1',
            'ADMIN_SEKRETARIAT'=> '104.1',

            'PSDA'             => '104.2',
            'ADMIN_PSDA'       => '104.2',

            'SWP'              => '104.3',
            'ADMIN_SWP'        => '104.3',

            'IRIGASI'          => '104.4',
            'ADMIN_IRIGASI'    => '104.4',

            'BINFAT'           => '104.5',
            'ADMIN_BINFAT'     => '104.5',

            'KEDIRI'           => '104.6.02',
            'ADMIN_KEDIRI'     => '104.6.02',
            'MALANG'           => '104.6.02',
            'ADMIN_MALANG'     => '104.6.02',
            'SURABAYA'         => '104.6.02',
            'ADMIN_SURABAYA'   => '104.6.02',

            'BOJONEGORO'       => '104.6.05',
            'ADMIN_BOJONEGORO' => '104.6.05',
            'MADIUN'           => '104.6.05',
            'ADMIN_MADIUN'     => '104.6.05',

            'BONDOWOSO'        => '104.6.06',
            'ADMIN_BONDOWOSO'  => '104.6.06',

            'LUMAJANG'         => '104.6.07',
            'ADMIN_LUMAJANG'   => '104.6.07',

            'PASURUAN'         => '104.6.08',
            'ADMIN_PASURUAN'   => '104.6.08',

            'MADURA'           => '104.6.09',
            'ADMIN_MADURA'     => '104.6.09',
        ];

        // Check username and display name tokens against the token map.
        // Prefer matching more specific / longer tokens first (e.g. LUMAJANG
        // should match before PSDA when both appear in the username).
        $tokens = array_keys($TOKEN_MAP);
        usort($tokens, function($a, $b) { return strlen($b) - strlen($a); });
        foreach ($tokens as $tk) {
            $code = $TOKEN_MAP[$tk];
            if ($u === $tk || $n === $tk || strpos($u, $tk) !== false || ($n && strpos($n, $tk) !== false)) {
                return $code;
            }
        }
        return null;
    }
}

if (!function_exists('print_bidang_options')) {
    function print_bidang_options(?string $locked = null) {
        // Use a list of pairs so we can emit multiple options with the same
        // nilai (kode bidang) but different labels (for UPT / Korwil).
        $options = [
            ['value' => '104.1',    'label' => 'Sekretariat'],
            ['value' => '104.2',    'label' => 'PSDA'],
            ['value' => '104.3',    'label' => 'SWP'],
            ['value' => '104.4',    'label' => 'Irigasi'],
            ['value' => '104.5',    'label' => 'Binfat'],

            ['value' => '104.6.02', 'label' => 'UPT Kediri'],
            ['value' => '104.6.02', 'label' => 'Korwil Malang'],
            ['value' => '104.6.02', 'label' => 'Korwil Surabaya'],

            ['value' => '104.6.05', 'label' => 'UPT Bojonegoro'],
            ['value' => '104.6.05', 'label' => 'Korwil Madiun'],
            ['value' => '104.6.06', 'label' => 'UPT Bondowoso'],
            ['value' => '104.6.07', 'label' => 'UPT Lumajang'],
            ['value' => '104.6.08', 'label' => 'UPT Pasuruan'],
            ['value' => '104.6.09', 'label' => 'UPT Madura'],
        ];
        foreach ($options as $opt) {
            $val = $opt['value'];
            $label = $opt['label'];
            $sel = ($locked !== null && $locked === $val) ? ' selected' : '';
            echo '<option value="'.$val.'"'.$sel.'>'.$label.'</option>';
        }
    }
}
?>

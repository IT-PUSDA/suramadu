<?php
// Helper untuk konsistensi deteksi hak akses Operator per bidang
// Penggunaan:
//   include '../include/operator_access.php';
//   $info = operator_access_info($config, $_SESSION, $owner_id_optional);
//   $info['is_operator'];              // bool
//   $info['allowed_ids'];              // array id_user yang termasuk kelompok operator tsb
//   $info['operator_group_access'];    // bool: owner_id (jika diberikan) termasuk dalam kelompok
//   $info['group_key'];                // key kelompok yang terdeteksi (misal 'irigasi')

if (!function_exists('operator_access_info')) {
    function operator_access_info($config, $session, $owner_id = null) {
        $result = [
            'is_operator' => false,
            'allowed_ids' => [],
            'group_key' => null,
            'operator_group_access' => false,
        ];

        if (empty($session['admin']) || (int)$session['admin'] !== 3) {
            return $result; // bukan operator
        }

        $result['is_operator'] = true;
        $username = strtoupper($session['username'] ?? '');
        $display  = strtoupper($session['nama'] ?? '');
        $normUser = str_replace([' ', '_'], '', $username);
        $normDisp = str_replace([' ', '_'], '', $display);

        // Definisi kelompok operator -> daftar token pencarian
        $groups = [
            'sekretariat'=>['SEKRETARIAT','TU'],
            'psda'=>['PSDA'],
            'irigasi'=>['IRIGASI'],
            'swp'=>['SWP'],
            'binfat'=>['BINFAT'],
            'upt-kediri'=>['KEDIRI'],
            'korwil-malang'=>['MALANG'],
            'korwil-surabaya'=>['SURABAYA'],
            'upt-bojonegoro'=>['BOJONEGORO'],
            'korwil-madiun'=>['MADIUN'],
            'upt-bondowoso'=>['BONDOWOSO'],
            'upt-lumajang'=>['LUMAJANG'],
            'upt-pasuruan'=>['PASURUAN'],
            'upt-madura'=>['MADURA'],
        ];

        $matchedGroup = null;
        $matchedTokens = [];
        foreach ($groups as $key => $tokens) {
            foreach ($tokens as $tokRaw) {
                $tok = strtoupper($tokRaw);
                $tokNorm = str_replace([' ', '_'], '', $tok);
                if (
                    $username === $tok ||
                    strpos($username, $tok) !== false ||
                    ($display && ($display === $tok || strpos($display, $tok) !== false)) ||
                    strpos($normUser, $tokNorm) !== false ||
                    ($normDisp && strpos($normDisp, $tokNorm) !== false)
                ) {
                    $matchedGroup = $key;
                    $matchedTokens = $tokens;
                    break 2; // keluar dari kedua loop
                }
            }
        }

        if ($matchedGroup !== null) {
            $result['group_key'] = $matchedGroup;
            // Bangun kondisi LIKE (lebih longgar daripada exact IN)
            $conds = [];
            foreach ($matchedTokens as $t) {
                $esc = mysqli_real_escape_string($config, strtoupper($t));
                $conds[] = "UPPER(username) LIKE '%$esc%'";
                $conds[] = "UPPER(nama) LIKE '%$esc%'";
            }
            if (!empty($conds)) {
                $sql = 'SELECT id_user FROM tbl_user WHERE (' . implode(' OR ', $conds) . ')';
                $res = mysqli_query($config, $sql);
                if ($res) {
                    while ($r = mysqli_fetch_assoc($res)) {
                        $idInt = (int)$r['id_user'];
                        if (!in_array($idInt, $result['allowed_ids'], true)) {
                            $result['allowed_ids'][] = $idInt;
                        }
                    }
                }
            }
        }

        // Tambah diri sendiri sebagai fallback
        if (!empty($session['id_user'])) {
            $selfId = (int)$session['id_user'];
            if (!in_array($selfId, $result['allowed_ids'], true)) {
                $result['allowed_ids'][] = $selfId;
            }
        }

        // Evaluasi akses ke owner_id (jika diberikan)
        if ($owner_id !== null) {
            $owner_id = (int)$owner_id;
            if (in_array($owner_id, $result['allowed_ids'], true)) {
                $result['operator_group_access'] = true;
            }
        }

        return $result;
    }
}

?>

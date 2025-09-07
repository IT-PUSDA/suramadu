<?php
// Live search AJAX endpoint for Surat Keluar
session_start();

// Include DB config
require_once __DIR__ . '/../include/config.php';

header('Content-Type: text/html; charset=utf-8');

if (empty($_SESSION['admin'])) {
    http_response_code(401);
    echo '<tr><td colspan="6" class="center-align">Sesi berakhir. Silakan login kembali.</td></tr>';
    exit;
}

// Helper: Indo date
if (!function_exists('indoDate')) {
    function indoDate($date)
    {
        $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $exp = explode('-', $date);
        return count($exp) == 3 ? $exp[2] . ' ' . $bulan[(int)$exp[1]] . ' ' . $exp[0] : $date;
    }
}

$id_user = $_SESSION['id_user'];
$is_admin_user = ($_SESSION['admin'] == 4); // Level Bidang
$is_operator   = ($_SESSION['admin'] == 3); // Level Operator

// Pagination size from settings
$limit = 10; // fallback
$qs = mysqli_query($config, "SELECT surat_keluar FROM tbl_sett");
if ($qs) {
    list($limit_from_db) = mysqli_fetch_array($qs);
    if (!empty($limit_from_db)) $limit = (int)$limit_from_db;
}

$pg = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
if ($pg < 1) $pg = 1;
$curr = ($pg - 1) * $limit;

// Base and where clause
$base_query = "FROM tbl_surat_keluar";
$where_clause = '';

// Scoping data:
//  - Bidang (4): hanya data miliknya
//  - Operator (3): data seluruh anggota bidangnya (mapping username -> grup)
$operator_allowed_ids = [];
if ($is_operator) {
    $username_current = strtoupper($_SESSION['username'] ?? '');
    $map_for_operator = [
        'sekretariat'   => ['SEKRETARIAT', 'TU'],
        'psda'          => ['PSDA'],
        'irigasi'       => ['IRIGASI'],
        'swp'           => ['SWP'],
        'binfat'        => ['BINFAT'],
        'upt-kediri'    => ['KEDIRI'],
        'korwil-malang' => ['MALANG'],
        'korwil-surabaya'=> ['SURABAYA'],
        'upt-bojonegoro'=> ['BOJONEGORO'],
        'korwil-madiun' => ['MADIUN'],
        'upt-bondowoso' => ['BONDOWOSO'],
        'upt-lumajang'  => ['LUMAJANG'],
        'upt-pasuruan'  => ['PASURUAN'],
        'upt-madura'    => ['MADURA'],
    ];
    foreach ($map_for_operator as $grp => $names) {
        $upperNames = array_map('strtoupper', $names);
        if (in_array($username_current, $upperNames, true)) {
            $in = "'" . implode("','", array_map(function($s) use ($config){ return mysqli_real_escape_string($config, strtoupper($s)); }, $upperNames)) . "'";
            $resOp = mysqli_query($config, "SELECT id_user FROM tbl_user WHERE UPPER(username) IN ($in)");
            if ($resOp) { while($rOp = mysqli_fetch_assoc($resOp)) { $operator_allowed_ids[] = (int)$rOp['id_user']; } }
            break;
        }
    }
    if (empty($operator_allowed_ids)) { $operator_allowed_ids[] = (int)$id_user; }
    $idListAllowed = implode(',', array_map('intval', $operator_allowed_ids));
    $where_clause .= " WHERE id_user IN ($idListAllowed)";
} elseif ($is_admin_user) {
    $where_clause .= " WHERE id_user='" . mysqli_real_escape_string($config, $id_user) . "'";
}

// Optional: filter bidang
$map = [
    'sekretariat'   => ['SEKRETARIAT', 'TU'],
    'psda'          => ['PSDA'],
    'irigasi'       => ['IRIGASI'],
    'swp'           => ['SWP'],
    'binfat'        => ['BINFAT'],
    'upt-kediri'    => ['KEDIRI'],
    'korwil-malang' => ['MALANG'],
    'korwil-surabaya'=> ['SURABAYA'],
    'upt-bojonegoro'=> ['BOJONEGORO'],
    'korwil-madiun' => ['MADIUN'],
    'upt-bondowoso' => ['BONDOWOSO'],
    'upt-lumajang'  => ['LUMAJANG'],
    'upt-pasuruan'  => ['PASURUAN'],
    'upt-madura'    => ['MADURA'],
];

if (!empty($_GET['filter_bidang'])) {
    $filterKey = $_GET['filter_bidang'];
    if (isset($map[$filterKey])) {
        $usernames = array_map('strtoupper', $map[$filterKey]);
        $in = "'" . implode("','", array_map(function ($s) use ($config) {
            return mysqli_real_escape_string($config, $s);
        }, $usernames)) . "'";
        $res = mysqli_query($config, "SELECT id_user, UPPER(username) AS uname FROM tbl_user WHERE UPPER(username) IN ($in)");
        $ids = [];
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $ids[] = (int)$r['id_user'];
            }
        }
        if (!empty($ids)) {
            $idList = implode(',', array_map('intval', $ids));
            $where_clause .= ($where_clause ? ' AND ' : ' WHERE ') . " id_user IN ($idList)";
        }
    }
}

// Search condition (by 'cari')
if (!empty($_GET['cari'])) {
    $cari = mysqli_real_escape_string($config, $_GET['cari']);
    $search_condition = "(isi LIKE '%$cari%' OR perihal LIKE '%$cari%' OR tujuan LIKE '%$cari%' OR no_surat LIKE '%$cari%' OR kode LIKE '%$cari%' OR no_agenda LIKE '%$cari%')";
    $where_clause .= ($where_clause ? ' AND ' : ' WHERE ') . $search_condition;
}

// Query and print rows only
$q = mysqli_query($config, "SELECT * " . $base_query . " " . $where_clause . " ORDER BY id_surat DESC LIMIT $curr, $limit");

if ($q && mysqli_num_rows($q) > 0) {
    while ($row = mysqli_fetch_array($q)) {
        echo '<tr style="vertical-align: top;">';
        echo '<td class="center-align">' . $row['no_agenda'] . '<hr class="grey lighten-3" style="margin: 4px 0;"/>' . $row['kode'] . '</td>';
        echo '<td>' . $row['isi'];
        if (!empty($row['file'])) {
            echo '<br/><br/><strong>File : </strong>';
            $is_operator_file = $is_operator && !empty($operator_allowed_ids) && in_array((int)$row['id_user'], $operator_allowed_ids, true);
            if ($_SESSION['admin'] == 1 || $is_operator_file) {
                echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat=' . $row['id_surat'] . '" target="_blank" rel="noopener" style="text-decoration: underline;">' . $row['file'] . '</a>';
            } else {
                echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat=' . $row['id_surat'] . '" class="pin-trigger" data-action-type="view" data-id-surat="' . $row['id_surat'] . '" style="text-decoration: underline;">' . $row['file'] . '</a>';
            }
            if (!empty($_SESSION['pinResetIds'][$row['id_surat']])) {
                echo ' <span class="new badge blue" data-badge-caption="PIN diubah" title="PIN direset oleh admin"></span>';
            }
        }
        echo '</td>';
        echo '<td class="center-align">' . $row['tujuan'] . '<br/><small class="grey-text text-darken-1">' . $row['perihal'] . '</small></td>';
        echo '<td class="center-align">' . $row['no_surat'] . '<br/><small class="grey-text text-darken-1 nowrap">' . indoDate($row['tgl_surat']) . '</small></td>';
        echo '<td class="center-align">' . $row['nama_pembuat'] . '<br/><small class="grey-text text-darken-1 nowrap">' . (isset($row['tgl_dibuat']) ? date('d M Y, H:i', strtotime($row['tgl_dibuat'])) : '') . '</small></td>';

    $can_manage = in_array($_SESSION['admin'], [1, 2]);
    $is_owner = ($row['id_user'] == $_SESSION['id_user']);
    $is_operator_owner = $is_operator && !empty($operator_allowed_ids) && in_array((int)$row['id_user'], $operator_allowed_ids, true);
        echo '<td class="center-align">';
        if ($can_manage || $is_owner || $is_operator_owner) {
            echo '<div class="actions-compact" style="display: flex; justify-content: center; gap: 0px; padding-top: 5px;">';
            if ($_SESSION['admin'] == 1 || $is_operator_owner) {
                echo '<a class="btn small blue waves-effect waves-light" style="color:white;" href="?page=admin&act=tsk&sub=edit&id_surat=' . $row['id_surat'] . '"><i class="material-icons" style="color:white;">edit</i> EDIT</a>';
                echo '<a class="btn small deep-orange waves-effect waves-light" style="color:white;" href="?page=admin&act=tsk&sub=del&id_surat=' . $row['id_surat'] . '" onclick="return confirm(\'Yakin ingin menghapus surat ini?\');"><i class="material-icons" style="color:white;">delete</i> DEL</a>';
            } else {
                echo '<a class="btn small blue waves-effect waves-light pin-trigger" style="color:white;" href="?page=admin&act=tsk&sub=edit&id_surat=' . $row['id_surat'] . '" data-action-type="edit" data-id-surat="' . $row['id_surat'] . '"><i class="material-icons" style="color:white;">edit</i> EDIT</a>';
                echo '<a class="btn small deep-orange waves-effect waves-light pin-trigger" style="color:white;" href="?page=admin&act=tsk&sub=del&id_surat=' . $row['id_surat'] . '" data-action-type="delete" data-id-surat="' . $row['id_surat'] . '"><i class="material-icons" style="color:white;">delete</i> DEL</a>';
            }
            echo '</div>';
        } else {
            echo '<div class="grey-text" style="padding-top: 15px;">-</div>';
        }
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="center-align"><div class="card-panel grey lighten-4" style="margin: 20px;">';
    if (!empty($_GET['cari'])) {
        echo '<i class="material-icons large grey-text">search</i><p class="grey-text">Tidak ada data yang ditemukan untuk pencarian "<strong>' . htmlspecialchars($_GET['cari']) . '</strong>"</p>';
    } else {
        echo '<i class="material-icons large grey-text">inbox</i><p class="grey-text">Tidak ada data untuk ditampilkan.</p>';
    }
    echo '</div></td></tr>';
}

exit;
?>

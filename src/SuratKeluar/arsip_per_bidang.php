<?php
// Arsip per Bidang View for Super Admin (1) and Pimpinan (2)
if (!isset($_SESSION)) { session_start(); }
require_once(BASE_PATH . '/src/include/config.php');

if (!in_array((int)$_SESSION['admin'], [1,2], true)) {
    $_SESSION['err'] = '<center>Akses ditolak.</center>';
    header('Location: index.php?page=admin');
    exit;
}

// Ensure status column exists; if not, show friendly message
$hasStatus = false;
$resStatus = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'status'");
if ($resStatus && mysqli_num_rows($resStatus) === 1) { $hasStatus = true; }

$groups = [
    'sekretariat'   => ['SEKRETARIAT','TU'],
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

$labels = [
    'sekretariat' => 'SEKRETARIAT',
    'psda' => 'PSDA',
    'irigasi' => 'IRIGASI',
    'swp' => 'SWP',
    'binfat' => 'BINFAT',
    'upt-kediri' => 'UPT KEDIRI',
    'korwil-malang' => 'KORWIL MALANG',
    'korwil-surabaya' => 'KORWIL SURABAYA',
    'upt-bojonegoro' => 'UPT BOJONEGORO',
    'korwil-madiun' => 'KORWIL MADIUN',
    'upt-bondowoso' => 'UPT BONDOWOSO',
    'upt-lumajang' => 'UPT LUMAJANG',
    'upt-pasuruan' => 'UPT PASURUAN',
    'upt-madura' => 'UPT MADURA',
];

$color = [
    'sekretariat' => 'teal',
    'psda' => 'light-blue darken-1',
    'irigasi' => 'green',
    'swp' => 'deep-purple',
    'binfat' => 'orange darken-2',
    'upt-kediri' => 'indigo',
    'korwil-malang' => 'red',
    'korwil-surabaya' => 'deep-orange',
    'upt-bojonegoro' => 'brown',
    'korwil-madiun' => 'blue-grey',
    'upt-bondowoso' => 'cyan darken-1',
    'upt-lumajang' => 'purple',
    'upt-pasuruan' => 'lime darken-1',
    'upt-madura' => 'pink darken-1',
];

// Resolve usernames -> ids once
$all = [];
foreach ($groups as $list) { foreach ($list as $u) { $all[] = strtoupper($u); } }
$all = array_values(array_unique($all));
$in = "'" . implode("','", array_map(function($s) use ($config){ return mysqli_real_escape_string($config, $s); }, $all)) . "'";
$resUsers = mysqli_query($config, "SELECT id_user, UPPER(username) AS uname FROM tbl_user WHERE UPPER(username) IN ($in)");
$unameToId = [];
if ($resUsers) {
    while ($r = mysqli_fetch_assoc($resUsers)) { $unameToId[$r['uname']] = (int)$r['id_user']; }
}

$counts = [];
foreach ($groups as $key => $usernames) {
    $ids = [];
    foreach ($usernames as $u) { $uUp = strtoupper($u); if (isset($unameToId[$uUp])) { $ids[] = $unameToId[$uUp]; } }
    if (!$hasStatus || count($ids) === 0) { $counts[$key] = 0; continue; }
    $idList = implode(',', array_map('intval', $ids));
    $sql = "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE status='finished' AND id_user IN ($idList)";
    $res = mysqli_query($config, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : ['c' => 0];
    $counts[$key] = (int)$row['c'];
}
?>
<div class="row">
    <div class="col s12">
        <div class="card" style="border-radius:10px;">
            <div class="card-content">
                <h5 style="margin:0 0 16px; display:flex; align-items:center; gap:8px;">
                    <i class="material-icons" style="color:#546e7a;">archive</i>
                    Arsip per Bidang/UPT
                </h5>
                <?php if (!$hasStatus): ?>
                    <div class="card-panel yellow lighten-4" style="border-radius:8px;">Kolom <code>status</code> belum tersedia. Arsip dihitung ketika kolom ini ada dan bernilai <code>finished</code>.</div>
                <?php endif; ?>
                <div class="row" style="margin-bottom:0;">
                    <?php foreach ($groups as $key => $terms): ?>
                        <div class="col s12 m6 l4 xl3">
                            <a href="index.php?page=admin&act=tsk&filter_bidang=<?php echo urlencode($key); ?>&filter_status=finished" class="block-link" style="text-decoration:none;">
                                <div class="card <?php echo $color[$key] ?? 'blue-grey'; ?>" style="border-radius:12px;">
                                    <div class="card-content white-text" style="min-height:110px;">
                                        <span class="card-title" style="display:flex; align-items:center; gap:8px;"><i class="material-icons md-36">drafts</i> <?php echo $labels[$key]; ?></span>
                                        <h5 class="white-text" style="margin-top:6px; letter-spacing:.2px;"><?php echo number_format($counts[$key]); ?> SURAT TERARSIP</h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="right-align" style="margin-top:-10px;">
                    <small class="grey-text">Klik kartu untuk membuka daftar surat terarsip (finished) milik bidang/UPT.</small>
                </div>
            </div>
        </div>
    </div>
</div>

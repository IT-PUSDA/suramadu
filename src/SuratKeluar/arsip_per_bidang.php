<?php
// Arsip per Bidang View for Super Admin (1) and Pimpinan (2)
if (!isset($_SESSION)) { session_start(); }
require_once(BASE_PATH . '/src/include/config.php');

if (!in_array((int)$_SESSION['admin'], [1,2], true)) {
    $_SESSION['err'] = '<center>Akses ditolak.</center>';
    header('Location: index.php?page=admin');
    exit;
}
    // Helper: mapping grup -> username untuk resolusi id_user per-bidang
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

    // Deteksi ketersediaan kolom relasi arsip pada tabel surat_keluar
    $hasRel = false;
    $resRel = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'id_arsip_berkas'");
    if ($resRel && mysqli_num_rows($resRel) === 1) { $hasRel = true; }
    if (!$hasRel) {
        @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL, ADD INDEX idx_arsip_rel (id_arsip_berkas)");
        $hasRel = true;
    }

    // Mode: Daftar berkas arsip untuk sebuah bidang (khusus level 1/2)
    if (isset($_GET['sub']) && $_GET['sub'] === 'berkas') {
        $key = isset($_GET['bidang']) ? $_GET['bidang'] : '';
        if (!isset($groups[$key])) { echo '<div class="card"><div class="card-content">Bidang tidak dikenali.</div></div>'; return; }
        // Resolve id_user anggota bidang
        $all = array_map('strtoupper', $groups[$key]);
        $in = "'" . implode("','", array_map(function($s) use ($config){ return mysqli_real_escape_string($config,$s); }, $all)) . "'";
        $resUsers = mysqli_query($config, "SELECT id_user, username FROM tbl_user WHERE UPPER(username) IN ($in)");
        $ids = []; $idToName = [];
        if ($resUsers) { while($r=mysqli_fetch_assoc($resUsers)){ $ids[]=(int)$r['id_user']; $idToName[(int)$r['id_user']]=$r['username']; } }
        if (empty($ids)) { echo '<div class="card"><div class="card-content">Belum ada pengguna pada bidang ini.</div></div>'; return; }
        $idList = implode(',', array_map('intval',$ids));
        // Pastikan tabel arsip dan relasi tersedia
        @mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_arsip_berkas (id INT AUTO_INCREMENT PRIMARY KEY, id_user INT NOT NULL, kode_klasifikasi VARCHAR(50) NULL, nama_berkas VARCHAR(255) NOT NULL, uraian TEXT NULL, file_path VARCHAR(255) NULL, tgl_buat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_user (id_user)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL");
        // Ambil daftar berkas + hitung jumlah surat di tiap berkas (berdasarkan relasi id_arsip_berkas)
        $sql = "SELECT a.id,a.id_user,a.kode_klasifikasi,a.nama_berkas,a.uraian,a.tgl_buat, (SELECT COUNT(1) FROM tbl_surat_keluar s WHERE s.id_arsip_berkas=a.id) AS jml FROM tbl_arsip_berkas a WHERE a.id_user IN ($idList) ORDER BY a.tgl_buat DESC, a.id DESC";
        $res = mysqli_query($config,$sql);
        echo '<div class="row"><div class="col s12"><div class="card" style="border-radius:10px;"><div class="card-content">';
        echo '<h5 style="margin:0 0 16px; display:flex; align-items:center; gap:8px;"><i class="material-icons" style="color:#546e7a;">folder</i> Arsip Berkas Bidang</h5>';
        echo '<div class="right-align" style="margin:-8px 0 8px;"><a href="index.php?page=admin&act=arsip" class="btn-flat" style="color:#1565c0;">&laquo; Kembali</a></div>';
        echo '<table class="striped highlight"><thead><tr><th>#</th><th>Nama Berkas</th><th>Kode</th><th>Operator</th><th>Jumlah Surat</th><th>Aksi</th></tr></thead><tbody>';
        $i=1; if ($res && mysqli_num_rows($res)>0) {
            while($r=mysqli_fetch_assoc($res)){
                $op = isset($idToName[(int)$r['id_user']]) ? htmlspecialchars($idToName[(int)$r['id_user']],ENT_QUOTES,'UTF-8') : '-';
                $nama = htmlspecialchars($r['nama_berkas']??'',ENT_QUOTES,'UTF-8');
                $kode = htmlspecialchars($r['kode_klasifikasi']??'',ENT_QUOTES,'UTF-8');
                $jml = (int)$r['jml'];
                $idb = (int)$r['id'];
                echo '<tr>'
                    .'<td>'.($i++).'</td>'
                    .'<td>'.$nama.'</td>'
                    .'<td>'.$kode.'</td>'
                    .'<td>'.$op.'</td>'
                    .'<td>'.$jml.'</td>'
                    .'<td><a class="btn-flat" style="color:#0277bd;" href="index.php?page=admin&act=arsip&sub=berkas_detail&bidang='.urlencode($key).'&id='.$idb.'">Lihat Surat</a></td>'
                    .'</tr>';
            }
        } else {
            echo '<tr><td colspan="6" class="center-align" style="padding:18px; color:#777;">Belum ada berkas arsip pada bidang ini.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div></div></div></div>';
        return;
    }

    // Mode: Detail isi berkas (khusus level 1/2)
    if (isset($_GET['sub']) && $_GET['sub'] === 'berkas_detail') {
        $key = isset($_GET['bidang']) ? $_GET['bidang'] : '';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!isset($groups[$key]) || $id<1) { echo '<div class="card"><div class="card-content">Parameter tidak valid.</div></div>'; return; }
        // Resolve anggota bidang
        $all = array_map('strtoupper', $groups[$key]);
        $in = "'" . implode("','", array_map(function($s) use ($config){ return mysqli_real_escape_string($config,$s); }, $all)) . "'";
        $resUsers = mysqli_query($config, "SELECT id_user FROM tbl_user WHERE UPPER(username) IN ($in)");
        $ids = []; if ($resUsers) { while($r=mysqli_fetch_assoc($resUsers)){ $ids[]=(int)$r['id_user']; } }
        if (empty($ids)) { echo '<div class="card"><div class="card-content">Bidang tanpa anggota.</div></div>'; return; }
        $idList = implode(',', array_map('intval',$ids));
        // Validasi berkas milik salah satu operator bidang
        $cek = mysqli_query($config, "SELECT a.*, u.username FROM tbl_arsip_berkas a LEFT JOIN tbl_user u ON a.id_user=u.id_user WHERE a.id=$id AND a.id_user IN ($idList) LIMIT 1");
        if (!$cek || mysqli_num_rows($cek)!==1) { echo '<div class="card"><div class="card-content">Berkas tidak ditemukan.</div></div>'; return; }
        $bk = mysqli_fetch_assoc($cek);
        // Ambil surat dalam berkas ini (apapun jenisnya, hanya yang ter-relasi)
        @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL");
        $qs = mysqli_query($config, "SELECT id_surat,no_surat,isi,perihal,tgl_surat,file FROM tbl_surat_keluar WHERE id_arsip_berkas=$id ORDER BY id_surat DESC LIMIT 500");
        echo '<div class="row"><div class="col s12"><div class="card" style="border-radius:10px;"><div class="card-content">';
        echo '<h5 style="margin:0 0 8px; display:flex; align-items:center; gap:8px;"><i class="material-icons" style="color:#546e7a;">folder_open</i> Isi Berkas: '.htmlspecialchars($bk['nama_berkas']??('Berkas #'.$id),ENT_QUOTES,'UTF-8').'</h5>';
        echo '<div class="right-align" style="margin:-8px 0 8px;"><a href="index.php?page=admin&act=arsip&sub=berkas&bidang='.urlencode($key).'" class="btn-flat" style="color:#1565c0;">&laquo; Kembali ke Daftar Berkas</a></div>';
        echo '<table class="striped highlight"><thead><tr><th>#</th><th>No. Surat</th><th>Isi/Perihal</th><th>Tanggal</th><th>File</th></tr></thead><tbody>';
        $i=1; if ($qs && mysqli_num_rows($qs)>0) {
            while($s=mysqli_fetch_assoc($qs)){
                $no = htmlspecialchars($s['no_surat']??'',ENT_QUOTES,'UTF-8');
                $isi = htmlspecialchars(($s['perihal']?:$s['isi']?:'-'),ENT_QUOTES,'UTF-8');
                $tgl = htmlspecialchars($s['tgl_surat']??'',ENT_QUOTES,'UTF-8');
                $file = trim((string)($s['file']??''));
                $view = $file!==''? '<a class="btn-flat" style="color:#0277bd;" target="_blank" href="src/SuratKeluar/lihat_file_sk.php?id_surat='.(int)$s['id_surat'].'">Lihat</a>' : '<span class="grey-text">-</span>';
                echo '<tr><td>'.($i++).'</td><td>'.$no.'</td><td>'.$isi.'</td><td>'.$tgl.'</td><td>'.$view.'</td></tr>';
            }
        } else {
            echo '<tr><td colspan="5" class="center-align" style="padding:18px; color:#777;">Belum ada surat dalam berkas ini.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div></div></div></div>';
        return;
    }

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
    if (!$hasRel || count($ids) === 0) { $counts[$key] = 0; continue; }
    $idList = implode(',', array_map('intval', $ids));
    // Hanya hitung surat yang sudah ditempatkan di berkas arsip
    $sql = "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_arsip_berkas IS NOT NULL AND id_user IN ($idList)";
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
                <?php if (!$hasRel): ?>
                    <div class="card-panel yellow lighten-4" style="border-radius:8px;">Kolom relasi <code>id_arsip_berkas</code> belum tersedia. Sistem akan menambahkannya otomatis untuk menghitung surat yang benar-benar terarsip.</div>
                <?php endif; ?>
                <div class="row" style="margin-bottom:0;">
                    <?php foreach ($groups as $key => $terms): ?>
                        <div class="col s12 m6 l4 xl3">
                            <!-- Untuk level 1/2: klik menuju daftar berkas arsip per-bidang -->
                            <?php if (in_array((int)$_SESSION['admin'], [1,2], true)) { $href = 'index.php?page=admin&act=arsip&sub=berkas&bidang='.urlencode($key); } else { $href = 'index.php?page=admin&act=tsk&filter_bidang='.urlencode($key); } ?>
                            <a href="<?php echo $href; ?>" class="block-link" style="text-decoration:none;">
                                <div class="card <?php echo $color[$key] ?? 'blue-grey'; ?>" style="border-radius:12px;">
                                    <div class="card-content white-text" style="min-height:110px;">
                                        <span class="card-title" style="display:flex; align-items:center; gap:8px;"><i class="material-icons md-36">drafts</i> <?php echo $labels[$key]; ?></span>
                                        <h5 class="white-text" style="margin-top:6px; letter-spacing:.2px;"><?php echo number_format((int)$counts[$key]); ?> SURAT TERARSIP</h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="right-align" style="margin-top:-10px;">
                    <small class="grey-text">Klik kartu untuk membuka daftar surat yang benar-benar sudah diarsipkan milik bidang/UPT.</small>
                </div>
            </div>
        </div>
    </div>
</div>

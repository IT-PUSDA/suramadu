<?php
// Arsip per Bidang View for Super Admin (1) and Pimpinan (2)
if (!isset($_SESSION)) { session_start(); }
// Pastikan BASE_PATH ada agar require_* tidak gagal bila file dipanggil dari konteks berbeda
if (!defined('BASE_PATH')) {
    $bp = realpath(__DIR__ . '/../../');
    if ($bp) { define('BASE_PATH', $bp); }
}
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

    // Pemetaan code bidang sebagaimana pola pada no_surat (contoh: "/104.1/" untuk Sekretariat)
    $bidangCodes = [
        'sekretariat'   => '104.1',
        'psda'          => '104.2',
        'swp'           => '104.3',
        'irigasi'       => '104.4',
        'binfat'        => '104.5',
        'upt-kediri'    => '104.6.02',
        'korwil-malang' => '104.6.02',
        'korwil-surabaya'=> '104.6.02',
        'upt-bojonegoro'=> '104.6.05',
        'korwil-madiun' => '104.6.05',
        'upt-bondowoso' => '104.6.06',
        'upt-lumajang'  => '104.6.07',
        'upt-pasuruan'  => '104.6.08',
        'upt-madura'    => '104.6.09',
    ];

    // Helper: ambil daftar id_user untuk sebuah key bidang menggunakan pencocokan LIKE
    if (!function_exists('arsip_get_user_ids_for_group')) {
        function arsip_get_user_ids_for_group($config, $key, $groups) {
            if (!isset($groups[$key])) return [];
            $tokens = array_map('strtoupper', (array)$groups[$key]);
            $conds = [];
            foreach ($tokens as $t) {
                $esc = mysqli_real_escape_string($config, $t);
                $conds[] = "UPPER(username) LIKE '%$esc%'";
                $conds[] = "UPPER(nama) LIKE '%$esc%'";
            }
            if (empty($conds)) return [];
            $sql = 'SELECT id_user FROM tbl_user WHERE (' . implode(' OR ', $conds) . ')';
            $res = mysqli_query($config, $sql);
            $out = [];
            if ($res) { while($r=mysqli_fetch_assoc($res)){ $id=(int)$r['id_user']; if(!in_array($id,$out,true)) $out[]=$id; } }
            return $out;
        }
    }

    // Helper: ambil id_user berdasarkan pola kode bidang pada no_surat
    if (!function_exists('arsip_get_user_ids_by_code')) {
        function arsip_get_user_ids_by_code($config, $key, $bidangCodes) {
            if (!isset($bidangCodes[$key])) return [];
            $code = mysqli_real_escape_string($config, $bidangCodes[$key]);
            $sql = "SELECT DISTINCT id_user FROM tbl_surat_keluar WHERE no_surat LIKE '%$code%'";
            $res = mysqli_query($config, $sql);
            $out = [];
            if ($res) { while($r=mysqli_fetch_assoc($res)){ $out[]=(int)$r['id_user']; } }
            return $out;
        }
    }
    // Helper: ambil id_user berdasarkan username exact sesuai mapping (tanpa LIKE agar tidak melebar)
    if (!function_exists('arsip_get_user_ids_by_username_exact')) {
        function arsip_get_user_ids_by_username_exact($config, $key, $groups) {
            if (!isset($groups[$key]) || empty($groups[$key])) return [];
            $tokens = array_map('strtoupper', (array)$groups[$key]);
            $esc = [];
            foreach ($tokens as $t) { $esc[] = "'" . mysqli_real_escape_string($config, $t) . "'"; }
            $sql = 'SELECT id_user FROM tbl_user WHERE UPPER(username) IN (' . implode(',', $esc) . ')';
            $res = mysqli_query($config, $sql);
            $out = [];
            if ($res) { while($r=mysqli_fetch_assoc($res)){ $out[]=(int)$r['id_user']; } }
            return $out;
        }
    }

    // Helper: where-clause scope bidang (tokens pada user OR fallback berdasarkan kode no_surat)
    if (!function_exists('arsip_where_user_scope')) {
        function arsip_where_user_scope($config, $key, $groups, $bidangCodes) {
            if (!isset($groups[$key])) return '1=0';
            $tokens = array_map('strtoupper', (array)$groups[$key]);
            $ors = [];
            foreach ($tokens as $t) {
                $esc = mysqli_real_escape_string($config, $t);
                $ors[] = "UPPER(u.username) LIKE '%$esc%'";
                $ors[] = "UPPER(u.nama) LIKE '%$esc%'";
            }
            // Fallback via kode pada no_surat -> id_user
            if (isset($bidangCodes[$key]) && $bidangCodes[$key] !== '') {
                $code = mysqli_real_escape_string($config, $bidangCodes[$key]);
                $ors[] = "u.id_user IN (SELECT DISTINCT id_user FROM tbl_surat_keluar WHERE no_surat LIKE '%$code%')";
            }
            return empty($ors) ? '1=0' : '(' . implode(' OR ', $ors) . ')';
        }
    }

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
    // Sementara: redirect ke sub=list yang menampilkan tabel Berkas dengan UI sama
    header('Location: index.php?page=admin&act=arsip&sub=list&bidang=' . urlencode($key));
    exit;
    $invalidBidang = !isset($groups[$key]);
    // Resolve id_user anggota bidang
        // Ambil id_user dengan pencocokan LIKE (username/nama mengandung token)
        // Union: id_user via token-match + via kode bidang pada no_surat
        $ids = [];
        if (!$invalidBidang) {
            $byToken = arsip_get_user_ids_for_group($config, $key, $groups);
            $byCode  = arsip_get_user_ids_by_code($config, $key, $bidangCodes);
            $ids = array_values(array_unique(array_merge($byToken, $byCode)));
        }
        // Siapkan idList aman walau kosong (gunakan '0' agar IN (0) valid dan hasil kosong)
        $idList = !empty($ids) ? implode(',', array_map('intval',$ids)) : '0';
        // Pastikan tabel arsip dan relasi tersedia
        @mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_arsip_berkas (id INT AUTO_INCREMENT PRIMARY KEY, id_user INT NOT NULL, kode_klasifikasi VARCHAR(50) NULL, nama_berkas VARCHAR(255) NOT NULL, uraian TEXT NULL, file_path VARCHAR(255) NULL, tgl_buat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_user (id_user)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL");
        // Filter parameters
        $qkode = isset($_GET['qkode']) ? trim($_GET['qkode']) : '';
        $qnama = isset($_GET['qnama']) ? trim($_GET['qnama']) : '';
        // Scope via JOIN user tokens agar lebih robust + terapkan filter
        $userWhere = $invalidBidang ? '1=0' : arsip_where_user_scope($config, $key, $groups, $bidangCodes);
        $cond = "WHERE $userWhere";
        if ($qkode !== '') { $esc = mysqli_real_escape_string($config, $qkode); $cond .= " AND a.kode_klasifikasi LIKE '%$esc%'"; }
        if ($qnama !== '') { $esc = mysqli_real_escape_string($config, $qnama); $cond .= " AND a.nama_berkas LIKE '%$esc%'"; }
        // Ambil daftar berkas + hitung jumlah surat di tiap berkas (berdasarkan relasi id_arsip_berkas)
        $sql = "SELECT a.id,a.id_user,a.kode_klasifikasi,a.nama_berkas,a.tgl_buat, (SELECT COUNT(1) FROM tbl_surat_keluar s WHERE s.id_arsip_berkas=a.id) AS jml FROM tbl_arsip_berkas a LEFT JOIN tbl_user u ON u.id_user=a.id_user $cond ORDER BY a.tgl_buat DESC, a.id DESC";
        $res = mysqli_query($config,$sql);
        if ($res === false) {
            echo '<div class="card-panel red lighten-4" style="border-radius:8px;">Gagal memuat data berkas. Detail: '.htmlspecialchars(mysqli_error($config),ENT_QUOTES,'UTF-8').'</div>';
        }
        // UI wrapper
        echo '<div class="row"><div class="col s12"><div class="card" style="border-radius:10px;"><div class="card-content">';
        echo '<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px;">';
        echo '<h5 style="margin:0; display:flex; align-items:center; gap:8px;"><i class="material-icons" style="color:#546e7a;">folder</i> Arsip Berkas Bidang</h5>';
    // (Optional) tombol tambah dinonaktifkan untuk level 1/2
        echo '</div>';
        echo '<form method="get" action="" style="margin-bottom:8px;">'
            .'<input type="hidden" name="page" value="admin">'
            .'<input type="hidden" name="act" value="arsip">'
            .'<input type="hidden" name="sub" value="berkas">'
            .'<input type="hidden" name="bidang" value="'.htmlspecialchars($key,ENT_QUOTES,'UTF-8').'">'
            .'<div class="row" style="margin-bottom:0;">'
            .  '<div class="col s12 m6" style="margin-bottom:8px;">'
            .    '<label style="font-size:.85rem; color:#607d8b;">Cari Kode Klasifikasi</label>'
            .    '<input type="text" name="qkode" value="'.htmlspecialchars($qkode,ENT_QUOTES,'UTF-8').'" placeholder="Ketik kode..." class="browser-default" style="height:36px; padding:0 10px; border:1px solid #cfd8dc; border-radius:6px;">'
            .  '</div>'
            .  '<div class="col s12 m6" style="margin-bottom:8px;">'
            .    '<label style="font-size:.85rem; color:#607d8b;">Cari Nama Berkas</label>'
            .    '<div style="display:flex; gap:8px;">'
            .      '<input type="text" name="qnama" value="'.htmlspecialchars($qnama,ENT_QUOTES,'UTF-8').'" placeholder="Ketik nama..." class="browser-default" style="flex:1; height:36px; padding:0 10px; border:1px solid #cfd8dc; border-radius:6px;">'
            .      '<button type="submit" class="btn blue" style="height:36px; line-height:36px; border-radius:20px; padding:0 18px;">FILTER</button>'
            .    '</div>'
            .  '</div>'
            .'</div>'
        .'</form>';
        echo '<div class="right-align" style="margin:-8px 0 8px;"><a href="index.php?page=admin&act=arsip" class="btn-flat" style="color:#1565c0;">&laquo; Kembali</a></div>';
        echo '<table class="striped highlight"><thead>'
            .'<tr>'
            .'<th style="width:60px;">No</th>'
            .'<th style="width:160px;">Kode Klasifikasi</th>'
            .'<th>Nama Berkas</th>'
            .'<th style="width:200px;">Tanggal Buat Berkas</th>'
            .'<th style="width:100px;">Total</th>'
            .'<th style="width:120px;">Aksi</th>'
            .'</tr>'
            .'</thead><tbody>';
        if ($invalidBidang) {
            echo '<tr><td colspan="6" class="center-align" style="padding:18px; color:#777;">Bidang tidak dikenali.</td></tr>';
        }
        $i=1; if (!$invalidBidang && $res && mysqli_num_rows($res)>0) {
            while($r=mysqli_fetch_assoc($res)){
                $nama = htmlspecialchars($r['nama_berkas']??'',ENT_QUOTES,'UTF-8');
                $kode = htmlspecialchars($r['kode_klasifikasi']??'',ENT_QUOTES,'UTF-8');
                $tgl  = htmlspecialchars($r['tgl_buat']? date('d M Y', strtotime($r['tgl_buat'])) : '',ENT_QUOTES,'UTF-8');
                $jml = (int)$r['jml'];
                $idb = (int)$r['id'];
                echo '<tr>'
                    .'<td>'.($i++).'</td>'
                    .'<td>'.$kode.'</td>'
                    .'<td>'.$nama.'</td>'
                    .'<td>'.$tgl.'</td>'
                    .'<td>'.$jml.'</td>'
                    .'<td>'
                    .  '<a class="btn-flat" title="Lihat" style="color:#1565c0;" href="index.php?page=admin&act=arsip&sub=berkas_detail&bidang='.urlencode($key).'&id='.$idb.'"><i class="material-icons">visibility</i></a>'
                    .  '<span class="btn-flat disabled" title="Edit (khusus operator)"><i class="material-icons grey-text">edit</i></span>'
                    .  '<span class="btn-flat disabled" title="Hapus (khusus operator)"><i class="material-icons grey-text">delete</i></span>'
                    .'</td>'
                    .'</tr>';
            }
        } elseif (!$invalidBidang) {
            echo '<tr><td colspan="6" class="center-align" style="padding:18px; color:#777;">Belum ada berkas arsip pada bidang ini.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div></div></div></div>';
        return;
    }

    // Mode: Daftar BERKAS arsip per-bidang (fallback yang pasti tampil untuk level 1/2)
    if (isset($_GET['sub']) && $_GET['sub'] === 'list') {
        $key = isset($_GET['bidang']) ? $_GET['bidang'] : '';
    if (!isset($groups[$key])) { echo '<div class="card"><div class="card-content">Bidang tidak dikenali.</div></div>'; return; }
    // Resolve id_user anggota bidang (LIKE)
    $byToken = arsip_get_user_ids_for_group($config, $key, $groups);
    $byCode  = arsip_get_user_ids_by_code($config, $key, $bidangCodes);
    $ids = array_values(array_unique(array_merge($byToken, $byCode)));
        $idList = !empty($ids) ? implode(',', array_map('intval',$ids)) : '0';
        // Pastikan tabel arsip & kolom relasi tersedia
        @mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_arsip_berkas (id INT AUTO_INCREMENT PRIMARY KEY, id_user INT NOT NULL, kode_klasifikasi VARCHAR(50) NULL, nama_berkas VARCHAR(255) NOT NULL, uraian TEXT NULL, file_path VARCHAR(255) NULL, tgl_buat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_user (id_user)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $rc = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'id_arsip_berkas'");
        if(!$rc || mysqli_num_rows($rc)==0){ @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL"); }
        // Filter
        $qkode = isset($_GET['qkode']) ? trim($_GET['qkode']) : '';
        $qnama = isset($_GET['qnama']) ? trim($_GET['qnama']) : '';
    // Gunakan JOIN user + token bidang
    $userWhere = arsip_where_user_scope($config, $key, $groups, $bidangCodes);
    $cond = "WHERE $userWhere";
        if ($qkode !== '') { $esc = mysqli_real_escape_string($config, $qkode); $cond .= " AND a.kode_klasifikasi LIKE '%$esc%'"; }
        if ($qnama !== '') { $esc = mysqli_real_escape_string($config, $qnama); $cond .= " AND a.nama_berkas LIKE '%$esc%'"; }
    $sql = "SELECT a.id,a.kode_klasifikasi,a.nama_berkas,a.tgl_buat,(SELECT COUNT(1) FROM tbl_surat_keluar s WHERE s.id_arsip_berkas=a.id) AS jml FROM tbl_arsip_berkas a LEFT JOIN tbl_user u ON u.id_user=a.id_user $cond ORDER BY a.tgl_buat DESC, a.id DESC";
        $res = mysqli_query($config,$sql);
        if ($res === false) {
            echo '<div class="card-panel red lighten-4" style="border-radius:8px;">Gagal memuat data berkas. Detail: '.htmlspecialchars(mysqli_error($config),ENT_QUOTES,'UTF-8').'</div>';
        }
        // UI + style (samakan dengan operator: dark header, filters inline, fullscreen within container)
        echo '<div class="row" style="margin-bottom:0;">'
           .'<div class="col s12">'
           .'<div class="card" style="border-radius:12px;">'
           .'<div class="card-content">';
        echo '<h5 style="margin:0 0 16px; display:flex; align-items:center; gap:8px;"><i class="material-icons" style="color:#546e7a;">archive</i> Arsip Berkas Bidang</h5>';
        echo '<style>
            .filters-inline{display:flex;flex-wrap:wrap;gap:18px 28px;margin:4px 0 12px;align-items:flex-end}
            .filters-inline .field{display:flex;flex-direction:column}
            .filters-inline label{font-size:11px;font-weight:600;color:#455a64;margin-bottom:5px;letter-spacing:.5px}
            .filters-inline input{background:#fafafa;border:1px solid #d0d7de;border-radius:8px;height:40px;padding:0 12px;min-width:230px}
            .btn-pill{border-radius:20px}
            table.table-arsip thead th{background:#263238;color:#fff}
            table.table-arsip thead th, table.table-arsip tbody td{padding:12px 16px}
            table.table-arsip tbody tr:nth-child(even){background:#f5f8fa}
            .right-actions{margin-top:-6px}
            @media(max-width:600px){.filters-inline input{min-width:150px}}
        </style>';
        echo '<form method="get" action="" class="filters-inline">'
            .'<input type="hidden" name="page" value="admin">'
            .'<input type="hidden" name="act" value="arsip">'
            .'<input type="hidden" name="sub" value="list">'
            .'<input type="hidden" name="bidang" value="'.htmlspecialchars($key,ENT_QUOTES,'UTF-8').'">'
            .'<div class="field">'
            .    '<label>Cari Kode Klasifikasi</label>'
            .    '<input type="text" name="qkode" value="'.htmlspecialchars($qkode,ENT_QUOTES,'UTF-8').'" placeholder="Ketik kode..." class="browser-default">'
            .'</div>'
            .'<div class="field">'
            .    '<label>Cari Nama Berkas</label>'
            .    '<input type="text" name="qnama" value="'.htmlspecialchars($qnama,ENT_QUOTES,'UTF-8').'" placeholder="Ketik nama..." class="browser-default">'
            .'</div>'
            .'<button type="submit" class="btn blue btn-pill" style="height:40px;line-height:40px;">FILTER</button>'
        .'</form>';
        echo '<div class="right-align right-actions"><a href="index.php?page=admin&act=arsip" class="btn-flat" style="color:#1565c0;">&laquo; KEMBALI</a></div>';
        echo '<table class="striped highlight table-arsip"><thead>'
            .'<tr>'
            .'<th style="width:60px;">No</th>'
            .'<th style="width:160px;">Kode Klasifikasi</th>'
            .'<th>Nama Berkas</th>'
            .'<th style="width:200px;">Tanggal Buat Berkas</th>'
            .'<th style="width:100px;">Total</th>'
            .'<th style="width:120px;">Aksi</th>'
            .'</tr>'
            .'</thead><tbody>';
        $i=1; if ($res && mysqli_num_rows($res)>0) {
            while($r=mysqli_fetch_assoc($res)){
                $nama = htmlspecialchars($r['nama_berkas']??'',ENT_QUOTES,'UTF-8');
                $kode = htmlspecialchars($r['kode_klasifikasi']??'',ENT_QUOTES,'UTF-8');
                $tgl  = htmlspecialchars($r['tgl_buat']? date('d M Y', strtotime($r['tgl_buat'])) : '',ENT_QUOTES,'UTF-8');
                $jml = (int)$r['jml'];
                $idb = (int)$r['id'];
                echo '<tr>'
                    .'<td>'.($i++).'</td>'
                    .'<td>'.$kode.'</td>'
                    .'<td>'.$nama.'</td>'
                    .'<td>'.$tgl.'</td>'
                    .'<td>'.$jml.'</td>'
                    .'<td>'
                    .  '<a class="btn-flat" title="Lihat" style="color:#1565c0;" href="index.php?page=admin&act=arsip&sub=berkas_detail&bidang='.urlencode($key).'&id='.$idb.'"><i class="material-icons">visibility</i></a>'
                    .  '<span class="btn-flat disabled" title="Edit (khusus operator)"><i class="material-icons grey-text">edit</i></span>'
                    .  '<span class="btn-flat disabled" title="Hapus (khusus operator)"><i class="material-icons grey-text">delete</i></span>'
                    .'</td>'
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
    // Validasi berkas milik salah satu operator bidang via JOIN + token bidang
    $userWhere = arsip_where_user_scope($config, $key, $groups, $bidangCodes);
    $cek = mysqli_query($config, "SELECT a.*, u.username FROM tbl_arsip_berkas a LEFT JOIN tbl_user u ON a.id_user=u.id_user WHERE a.id=$id AND ($userWhere) LIMIT 1");
        if (!$cek || mysqli_num_rows($cek)!==1) { echo '<div class="card"><div class="card-content">Berkas tidak ditemukan.</div></div>'; return; }
        $bk = mysqli_fetch_assoc($cek);
        // Ambil surat dalam berkas ini (apapun jenisnya, hanya yang ter-relasi)
        @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL");
        $qs = mysqli_query($config, "SELECT id_surat,no_surat,isi,perihal,tgl_surat,file FROM tbl_surat_keluar WHERE id_arsip_berkas=$id ORDER BY id_surat DESC LIMIT 500");
        echo '<div class="row"><div class="col s12"><div class="card" style="border-radius:10px;"><div class="card-content">';
        echo '<h5 style="margin:0 0 8px; display:flex; align-items:center; gap:8px;"><i class="material-icons" style="color:#546e7a;">folder_open</i> Isi Berkas: '.htmlspecialchars($bk['nama_berkas']??('Berkas #'.$id),ENT_QUOTES,'UTF-8').'</h5>';
    echo '<div class="right-align" style="margin:-8px 0 8px;"><a href="index.php?page=admin&act=arsip&sub=list&bidang='.urlencode($key).'" class="btn-flat" style="color:#1565c0;">&laquo; Kembali ke Daftar Berkas</a></div>';
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

// Hitung jumlah terarsip per bidang menggunakan scope yang sama seperti list (tokens + fallback kode),
// dihitung berdasarkan surat yang terikat ke berkas (berkas milik user bidang), dan hanya 'finished' jika kolom status ada
$hasStatusCol = false;
$resStat = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'status'");
if ($resStat && mysqli_num_rows($resStat) === 1) { $hasStatusCol = true; }

$counts = [];
foreach ($groups as $key => $_unused) {
    if (!$hasRel) { $counts[$key] = 0; continue; }
    $whereU = arsip_where_user_scope($config, $key, $groups, $bidangCodes); // uses alias u
    $extra = $hasStatusCol ? " AND s.status='finished'" : '';
    $sql = "SELECT COUNT(*) AS c
            FROM tbl_surat_keluar s
            JOIN tbl_arsip_berkas a ON s.id_arsip_berkas=a.id
            JOIN tbl_user u ON a.id_user=u.id_user
            WHERE s.id_arsip_berkas IS NOT NULL$extra AND ($whereU)";
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
                    Arsip Per Bidang/UPT
                </h5>
                <?php if (!$hasRel): ?>
                    <div class="card-panel yellow lighten-4" style="border-radius:8px;">Kolom relasi <code>id_arsip_berkas</code> belum tersedia. Sistem akan menambahkannya otomatis untuk menghitung surat yang benar-benar terarsip.</div>
                <?php endif; ?>
                <div class="row" style="margin-bottom:0;">
                    <?php foreach ($groups as $key => $terms): ?>
                        <div class="col s12 m6 l4 xl3">
                            <!-- Untuk level 1/2: gunakan sub=list yang sekarang menampilkan tabel Berkas -->
                            <?php if (in_array((int)$_SESSION['admin'], [1,2], true)) { $href = 'index.php?page=admin&act=arsip_op&view_as=su&bidang='.urlencode($key); } else { $href = 'index.php?page=admin&act=tsk&filter_bidang='.urlencode($key); } ?>
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
                    <small class="grey-text">Klik kartu untuk membuka daftar berkas arsip bidang/UPT (tampilan sama seperti halaman operator).</small>
                </div>
            </div>
        </div>
    </div>
</div>

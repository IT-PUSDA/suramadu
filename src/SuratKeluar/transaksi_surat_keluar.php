<?php
// filepath: c:\laragon\www\ams\src\SuratKeluar\transaksi_surat_keluar.php
//cek session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: index.php");
    die();
} else {
    $id_user = $_SESSION['id_user'];
    if ($_SESSION['admin'] == 5) {
        echo '<script language="javascript">
                    window.alert("ERROR! Anda tidak memiliki hak akses untuk membuka halaman ini");
                    window.location.href="index.php?page=logout";
                  </script>';
    } else {

        // Bagian ini menangani sub-halaman seperti tambah, edit, hapus
        if (isset($_REQUEST['sub'])) {
            $sub = $_REQUEST['sub'];
            switch ($sub) {
                case 'add':
                    include 'tambah_surat_keluar.php';
                    break;
                case 'add_nota_dinas':
                    include 'tambah_surat_keluar_nota_dinas.php';
                    break;
                case 'add_produk_hukum':
                    include 'tambah_surat_keluar_produk_hukum.php';
                    break;
                case 'add_keuangan':
                    include 'tambah_surat_keluar_keuangan.php';
                    break;
                case 'edit':
                    include 'edit_surat_keluar.php';
                    break;
                case 'del':
                    include 'hapus_surat_keluar.php';
                    break;
                case 'proses_tambah':
                    include 'proses_tambah_surat_keluar.php';
                    break;
                case 'proses_tambah_nota_dinas':
                    include 'proses_tambah_surat_keluar_nota_dinas.php';
                    break;
                case 'proses_tambah_produk_hukum':
                    include 'proses_tambah_surat_keluar_produk_hukum.php';
                    break;
                case 'proses_tambah_keuangan':
                    include 'proses_tambah_surat_keluar_keuangan.php';
                    break;
            }
        } else {

            // Pengaturan untuk paginasi (jumlah data per halaman)
            $query_sett = mysqli_query($config, "SELECT surat_keluar FROM tbl_sett");
            list($surat_keluar) = mysqli_fetch_array($query_sett);
            $limit = $surat_keluar;
            $pg = @$_GET['pg'];
            if (empty($pg)) {
                $curr = 0;
                $pg = 1;
            } else {
                $curr = ($pg - 1) * $limit;
            }

            // Fungsi untuk mengubah format tanggal ke format Indonesia
            if (!function_exists('indoDate')) {
                function indoDate($date)
                {
                    $bulan = array(1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember');
                    $exp = explode('-', $date);
                    return count($exp) == 3 ? $exp[2] . ' ' . $bulan[(int)$exp[1]] . ' ' . $exp[0] : $date;
                }
            }
?>

            <!-- Tampilan Header Halaman -->
            <!-- Style rapi full-width khusus halaman Surat Keluar -->
            <script>document.body.classList.add('page-surat-keluar');</script>
            <style>
                /* Buat container global pada halaman ini penuh */
                body.page-surat-keluar .container {max-width:100% !important;width:100% !important;padding-left:18px;padding-right:18px;}
                /* Baris utama agar tidak ada offset kiri/kanan */
                .full-bleed {width:100%;max-width:100%;margin:0 auto;}
                .full-bleed.row {margin-left:0;margin-right:0;}
                /* Kartu header & tabel rata penuh */
                .full-bleed .z-depth-1, .full-bleed .card {margin:0 0 20px 0;}
                /* Nav secondary dibulatkan sedikit */
                .secondary-nav .nav-wrapper {border-radius:6px;}
                /* Header tabel menempel rapi */
                #tbl {margin:0;}
                /* Perbaiki scroll horizontal jika ada banyak kolom */
                .table-responsive {width:100%;overflow-x:auto;}
                /* Responsif kecil */
                @media (max-width:600px){body.page-surat-keluar .container{padding-left:10px;padding-right:10px}}
            </style>

            <div class="row full-bleed">
                <div class="col s12">
                    <div class="z-depth-1">
                        <nav class="secondary-nav">
                            <div class="nav-wrapper blue-grey darken-1">
                                <div class="col m7">
                                    <ul class="left">
                                        <li class="waves-effect waves-light hide-on-small-only"><a href="index.php?page=admin&act=tsk" class="judul"><i class="material-icons">drafts</i> Surat Keluar</a></li>
                                        <li class="waves-effect waves-light">
                                            <!-- Tombol Tambah Data: Tampil untuk semua level admin -->
                                            <a href="index.php?page=admin&act=tsk&sub=add"><i class="material-icons md-24">add_circle</i> Tambah Data</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col m5 hide-on-med-and-down">
                                    <form method="post" action="index.php?page=admin&act=tsk">
                                        <div class="input-field round-in-box">
                                            <input id="search" type="search" name="cari" placeholder="Ketik untuk mencari data..." autocomplete="off" required>
                                            <label for="search"><i class="material-icons">search</i></label>
                                            <input type="submit" name="submit" class="hidden">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Notifikasi Sukses -->
            <?php
            if (isset($_SESSION['succAdd'])) {
                $succAdd = $_SESSION['succAdd'];
                echo '<div id="alert-message" class="row"><div class="col m12"><div class="card green lighten-5"><div class="card-content notif"><span class="card-title green-text"><i class="material-icons md-36">done</i> ' . $succAdd . '</span></div></div></div></div>';
                unset($_SESSION['succAdd']);
            }
            if (isset($_SESSION['succEdit'])) {
                $succEdit = $_SESSION['succEdit'];
                echo '<div id="alert-message" class="row"><div class="col m12"><div class="card green lighten-5"><div class="card-content notif"><span class="card-title green-text"><i class="material-icons md-36">done</i> ' . $succEdit . '</span></div></div></div></div>';
                unset($_SESSION['succEdit']);
            }
            if (isset($_SESSION['succDel'])) {
                $succDel = $_SESSION['succDel'];
                echo '<div id="alert-message" class="row"><div class="col m12"><div class="card green lighten-5"><div class="card-content notif"><span class="card-title green-text"><i class="material-icons md-36">done</i> ' . $succDel . '</span></div></div></div></div>';
                unset($_SESSION['succDel']);
            }
            ?>

            <!-- Tampilan Tabel Data -->
            <div class="row jarak-form full-bleed">
                <div class="col m12" id="colres">
                    <div class="card">
                        <div class="card-content">
                            <!-- Panel info hasil pencarian (live) -->
                            <div id="search-info-panel" class="card-panel blue-grey lighten-5" style="margin-bottom: 20px; display: none;">
                                <p class="blue-grey-text">Hasil pencarian untuk: <strong class="black-text" id="search-info-text"></strong></p>
                            </div>
                            <?php
                            if (isset($_REQUEST['submit'])) {
                                $cari = mysqli_real_escape_string($config, $_REQUEST['cari']);
                                echo '<div class="card-panel blue-grey lighten-5" style="margin-bottom: 20px;"><p class="blue-grey-text">Hasil pencarian untuk: <strong class="black-text">' . stripslashes($cari) . '</strong></p></div>';
                            }
                            ?>
                            <div class="table-responsive">
                                <table class="striped highlight responsive-table" id="tbl">
                    <thead class="blue lighten-4" id="head">
                                        <tr>
                                            <th width="1%" class="center-align no-wrap" style="padding:6px 2px;">No</th>
                                            <th width="15%">Isi Ringkas<br /><small>File</small></th>
                                            <th width="18%" class="center-align">Tujuan<br /><small>Perihal</small></th>
                                            <th width="14%" class="center-align">No. Surat<br /><small>Tgl Surat</small></th>
                                            <th width="14%" class="center-align">Pembuat<br /><small>Tgl Dibuat</small></th>
                                            <th width="8%" class="center-align">Status</th>
                                            <th width="14%" class="center-align">
                                                <div style="display: flex; justify-content: center; align-items: center; gap: 4px;">
                                                    Aksi
                                                    <a class="modal-trigger tooltipped" href="#modal" data-position="left" data-tooltip="Atur jumlah data"><i class="material-icons" style="color: #333; font-size:20px;">settings</i></a>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-data">
                                        <?php
                                        // PENERAPAN LOGIKA HAK AKSES DIMULAI DI SINI
                                        $is_admin_user = ($_SESSION['admin'] == 4); // level Bidang
                                        $is_operator   = ($_SESSION['admin'] == 3); // level Operator
                                        $base_query = "FROM tbl_surat_keluar";
                                        $where_clause = "";
                                        // Mapping bidang (sama dengan di dashboard)
                                        // Map grup -> username uploader (untuk filter berbasis id_user)
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
                                        $labels = [
                                            'sekretariat' => 'Sekretariat',
                                            'psda' => 'PSDA',
                                            'irigasi' => 'Irigasi',
                                            'swp' => 'SWP',
                                            'binfat' => 'Binfat',
                                            'upt-kediri' => 'UPT Kediri',
                                            'korwil-malang' => 'Korwil Malang',
                                            'korwil-surabaya' => 'Korwil Surabaya',
                                            'upt-bojonegoro' => 'UPT Bojonegoro',
                                            'korwil-madiun' => 'Korwil Madiun',
                                            'upt-bondowoso' => 'UPT Bondowoso',
                                            'upt-lumajang' => 'UPT Lumajang',
                                            'upt-pasuruan' => 'UPT Pasuruan',
                                            'upt-madura' => 'UPT Madura',
                                        ];

                                        // 1. Filter Data:
                                        //    - Level Bidang (4): hanya data miliknya sendiri
                                        //    - Level Operator (3): hanya data dalam kelompok bidang operator (berdasarkan username mapping)
                                        // Gunakan helper terpusat untuk konsistensi
                                        if (!function_exists('operator_access_info')) { @include_once __DIR__ . '/../include/operator_access.php'; }
                                        $operator_allowed_ids = [];
                                        if ($is_operator) {
                                            $opInfo = operator_access_info($config, $_SESSION);
                                            $operator_allowed_ids = $opInfo['allowed_ids'];
                                            if (empty($operator_allowed_ids)) { $operator_allowed_ids[] = (int)$id_user; }
                                            $idListAllowed = implode(',', array_map('intval', $operator_allowed_ids));
                                            $where_clause .= ($where_clause ? ' AND ' : ' WHERE ') . " id_user IN ($idListAllowed)";
                                        } elseif ($is_admin_user) {
                                            $where_clause .= ($where_clause ? ' AND ' : ' WHERE ') . " id_user='" . intval($id_user) . "'"; // Level Bidang hanya dirinya
                                        }

                                        // Jika Super Admin mengklik kartu bidang di beranda, terapkan filter bidang
                                        $filter_id_list = '';
                                        if (isset($_GET['filter_bidang']) && $_GET['filter_bidang'] !== '') {
                                            $filterKey = $_GET['filter_bidang'];
                                            if (isset($map[$filterKey])) {
                                                // Ambil id_user berdasarkan username grup
                                                $usernames = array_map('strtoupper', $map[$filterKey]);
                                                $in = "'" . implode("','", array_map(function($s) use ($config){ return mysqli_real_escape_string($config, $s); }, $usernames)) . "'";
                                                $res = mysqli_query($config, "SELECT id_user, UPPER(username) AS uname FROM tbl_user WHERE UPPER(username) IN ($in)");
                                                $ids = [];
                                                if ($res) { while ($r = mysqli_fetch_assoc($res)) { $ids[] = (int)$r['id_user']; } }
                                                if (!empty($ids)) {
                                                    $idList = implode(',', array_map('intval', $ids));
                                                    $where_clause .= ($where_clause ? ' AND ' : ' WHERE ') . " id_user IN ($idList)";
                                                    $filter_id_list = $idList;
                                                }
                                            }
                                        }

                                        // Tambahkan filter pencarian jika ada
                                        if (isset($_REQUEST['submit'])) {
                                            $cari = mysqli_real_escape_string($config, $_REQUEST['cari']);
                                            $search_condition = "(isi LIKE '%$cari%' OR perihal LIKE '%$cari%' OR tujuan LIKE '%$cari%' OR No_Surat LIKE '%$cari%')";
                                            $where_clause .= (($is_admin_user || $is_operator) ? " AND " : " WHERE ") . $search_condition;
                                        }

                                        // Pastikan kolom jenis ada; jika belum, buat (default 'umum')
                                        $hasJenisCol = false;
                                        $chkJenis = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
                                        if ($chkJenis && mysqli_num_rows($chkJenis) === 1) {
                                            $hasJenisCol = true;
                                        } else {
                                            mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN jenis VARCHAR(20) NOT NULL DEFAULT 'umum'");
                                            $chkJenis2 = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
                                            if ($chkJenis2 && mysqli_num_rows($chkJenis2) === 1) { $hasJenisCol = true; }
                                        }

                                        // Halaman umum hanya menampilkan jenis 'umum' saja (bukan nota_dinas, produk_hukum, keuangan)
                                        if ($hasJenisCol) {
                                            $where_clause .= ($where_clause ? ' AND ' : ' WHERE ') . " jenis='umum'";
                                        }

                                        // Query untuk mengambil data sesuai hak akses + filter jenis umum
                                        $query = mysqli_query($config, "SELECT * " . $base_query . $where_clause . " ORDER BY id_surat DESC LIMIT $curr, $limit");

                                        // Jika ada filter bidang aktif, tampilkan quick-switch per jenis beserta hitungannya
                                        if (!empty($filter_id_list)) {
                                            $countUmum = $countND = $countPH = $countKEU = 0;
                                            $qU = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_user IN ($filter_id_list) AND jenis='umum'");
                                            $qN = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_user IN ($filter_id_list) AND jenis='nota_dinas'");
                                            $qP = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_user IN ($filter_id_list) AND jenis='produk_hukum'");
                                            $qK = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_user IN ($filter_id_list) AND jenis='keuangan'");
                                            if ($qU) { $countUmum = (int)mysqli_fetch_assoc($qU)['c']; }
                                            if ($qN) { $countND = (int)mysqli_fetch_assoc($qN)['c']; }
                                            if ($qP) { $countPH = (int)mysqli_fetch_assoc($qP)['c']; }
                                            if ($qK) { $countKEU = (int)mysqli_fetch_assoc($qK)['c']; }
                                            echo '<div class="row" style="margin: 6px 0 12px;">'
                                                . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card lime darken-1 hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">label</i> Umum</span><h6 class="white-text hs-sub">' . number_format($countUmum) . ' SURAT</h6></div></div></a></div>'
                                                . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk_nd&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card teal hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">assignment</i> Nota Dinas</span><h6 class="white-text hs-sub">' . number_format($countND) . ' SURAT</h6></div></div></a></div>'
                                                . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk_ph&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card deep-orange hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">gavel</i> Produk Hukum</span><h6 class="white-text hs-sub">' . number_format($countPH) . ' SURAT</h6></div></div></a></div>'
                                                . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk_keu&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card indigo hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">attach_money</i> Keuangan</span><h6 class="white-text hs-sub">' . number_format($countKEU) . ' SURAT</h6></div></div></a></div>'
                                                . '</div>';
                                        }

                                        // --- Inisialisasi kolom baru bila belum ada (sekali jalan aman) ---
                                        $needCheckCols = ['status','updated_by','updated_at'];
                                        $existingCols = [];
                                        $colRes = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar");
                                        if($colRes){
                                            while($c = mysqli_fetch_assoc($colRes)) { $existingCols[] = $c['Field']; }
                                        }
                                        $toAdd = [];
                                        if(!in_array('status',$existingCols)) $toAdd[] = "ADD COLUMN status ENUM('draft','finished') NOT NULL DEFAULT 'draft'";
                                        if(!in_array('updated_by',$existingCols)) $toAdd[] = "ADD COLUMN updated_by VARCHAR(50) NULL";
                                        if(!in_array('updated_at',$existingCols)) $toAdd[] = "ADD COLUMN updated_at DATETIME NULL";
                                        if(!empty($toAdd)) {
                                            if(@mysqli_query($config, "ALTER TABLE tbl_surat_keluar " . implode(', ',$toAdd))){
                                                // Set seluruh data awal sebagai draft (default) jika kolom baru ditambah
                                                @mysqli_query($config, "UPDATE tbl_surat_keluar SET status='draft' WHERE status IS NULL OR status=''" );
                                            }
                                        }

                                        // Jalankan query data setelah (potensi) alter
                                        $query = mysqli_query($config, "SELECT * " . $base_query . $where_clause . " ORDER BY id_surat DESC LIMIT $curr, $limit");
                                        if (mysqli_num_rows($query) > 0) {
                                            $seq = $curr + 1; // nomor urut halaman
                                            while ($row = mysqli_fetch_array($query)) {
                                                echo '
                                                <tr style="vertical-align: top;">
                                                    <td class="center-align"><strong>' . $seq . '</strong></td>
                                                    <td>' . $row['isi'];

                                                if (!empty($row['file'])) {
                                                    echo '<br/><br/><strong>File : </strong>';
                                                    $is_operator_file = $is_operator && !empty($operator_allowed_ids) && in_array((int)$row['id_user'], $operator_allowed_ids, true);
                                                    if ($_SESSION['admin'] == 1 || $_SESSION['admin'] == 2 || $is_operator_file) {
                                                        // Super Admin & Operator (data bidangnya) langsung buka
                                                        echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat=' . $row['id_surat'] . '" target="_blank" rel="noopener" style="text-decoration: underline;">' . $row['file'] . '</a>';
                                                    } else {
                                                        echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat=' . $row['id_surat'] . '" class="pin-trigger" data-action-type="view" data-id-surat="' . $row['id_surat'] . '" style="text-decoration: underline;">' . $row['file'] . '</a>';
                                                    }
                                                    if (!empty($_SESSION['pinResetIds'][$row['id_surat']])) {
                                                        echo ' <span class="new badge blue" data-badge-caption="PIN diubah" title="PIN direset oleh admin"></span>';
                                                    }
                                                }

                                                echo '</td>
                                                    <td class="center-align">' . $row['tujuan'] . '<br/><small class="grey-text text-darken-1">' . $row['perihal'] . '</small></td>
                                                    <td class="center-align">' . $row['no_surat'] . '<br/><small class="grey-text text-darken-1 nowrap">' . indoDate($row['tgl_surat']) . '</small></td>
                                                    <td class="center-align">' . $row['nama_pembuat'] . '<br/><small class="grey-text text-darken-1 nowrap">' . (isset($row['tgl_dibuat']) ? date('d M Y, H:i', strtotime($row['tgl_dibuat'])) : '') . '</small></td>';

                                                // Kolom Status (contoh logika: jika ada file -> Selesai, else Draft)
                                                // Status dari DB (fallback: jika kolom lama, gunakan file presence)
                                                $status_raw = isset($row['status']) ? $row['status'] : (!empty($row['file']) ? 'finished' : 'draft');
                                                $icon_file = ($status_raw == 'finished') ? 'finished.png' : 'draft.png';
                                                if(empty($__printedStatusStyle)){
                                                    echo '<style>
                                                        .status-cell{padding:2px 0!important;}
                                                        .status-cell .status-wrap{display:flex;align-items:center;justify-content:center;height:50px;}
                                                        /* Perbesar & turunkan lagi ikon status */
                                                        .status-cell img.status-icon{height:48px;max-height:48px;width:auto;display:block;position:relative;top:8px;filter:drop-shadow(0 1px 2px rgba(0,0,0,.15));}
                                                        .actions-compact{align-items:center;min-height:46px;}
                                                        .action-round{background:#1976d2!important;border-radius:50%;width:46px;height:46px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.15);transition:.25s}
                                                        .action-round i{line-height:46px;font-size:22px;color:#fff}
                                                        .action-round.delete{background:#e64a19!important;}
                                                        .action-round.toggle{background:#2e7d32!important;}
                                                        .action-round:hover{filter:brightness(1.08);} .action-round:active{transform:scale(.92);}
                                                        @media (max-width:600px){
                                                            .status-cell .status-wrap{height:46px;}
                                                            .status-cell img.status-icon{height:44px;top:6px;}
                                                            .action-round{width:40px;height:40px;}
                                                            .action-round i{font-size:20px;line-height:40px;}
                                                        }
                                                    </style>';
                                                    $__printedStatusStyle = true;
                                                }
                                                echo '<td class="center-align status-cell">'
                                                    . '<div class="status-wrap">'
                                                    . '<img class="status-icon status-icon-' . $row['id_surat'] . '" src="asset/img/' . $icon_file . '" alt="status" />'
                                                    . '</div></td>';

                                                echo '<td class="center-align">';

                                                // 2. Batasi Tombol: Super Admin & Operator (bidangnya), Bidang (milik sendiri)
                                                $can_manage = in_array($_SESSION['admin'], [1]);
                                                $is_owner = ($row['id_user'] == $_SESSION['id_user']);
                                                $is_operator_owner = $is_operator && !empty($operator_allowed_ids) && in_array((int)$row['id_user'], $operator_allowed_ids, true);
                                                if ($_SESSION['admin'] == 2) {
                                                    echo '<div class="grey-text" style="padding-top: 15px;">-</div>';
                                                } elseif ($can_manage || $is_owner || $is_operator_owner) {
                                                    echo '<div class="actions-compact" style="display:flex;justify-content:center;gap:10px;padding-top:2px;flex-wrap:wrap;">';
                                                    $btnBase = 'data-position="top"';
                                                    // Inject style untuk tombol bulat hanya sekali (gunakan flag)
                                                    if(empty($__printedActionStyle)){
                                                        echo '<style>.action-round{background:#1976d2;border-radius:50%;width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.15);transition:.25s} .action-round i{line-height:44px;font-size:22px;color:#fff} .action-round.delete{background:#e64a19} .action-round.toggle{background:#2e7d32} .action-round:hover{filter:brightness(1.08);} .action-round:active{transform:scale(.92);} @media (max-width:600px){.action-round{width:40px;height:40px;} .action-round i{font-size:20px;line-height:40px;}} </style>';
                                                        $__printedActionStyle = true;
                                                    }
                                                    $is_operator_level = ($_SESSION['admin'] == 3);
                                                    // Tombol toggle status hanya operator
                                                    if($is_operator_level) {
                                                        $toggleTitle = ($status_raw=='finished') ? 'Set Draft' : 'Set Finished';
                                                        echo '<a class="waves-effect waves-light tooltipped action-round toggle" ' . $btnBase . ' data-tooltip="' . $toggleTitle . '" href="#" onclick="return toggleStatus(' . $row['id_surat'] . ', event);"><i class="material-icons">autorenew</i></a>';
                                                    }
                                                    if ($_SESSION['admin'] == 1 || $is_operator_owner) {
                                                        echo '<a class="waves-effect waves-light tooltipped action-round" ' . $btnBase . ' data-tooltip="Edit" href="?page=admin&act=tsk&sub=edit&id_surat=' . $row['id_surat'] . '"><i class="material-icons">edit</i></a>';
                                                        echo '<a class="waves-effect waves-light tooltipped action-round delete" ' . $btnBase . ' data-tooltip="Hapus" href="?page=admin&act=tsk&sub=del&id_surat=' . $row['id_surat'] . '" onclick="return confirm(\'Yakin ingin menghapus surat ini?\');"><i class="material-icons">delete</i></a>';
                                                    } else {
                                                        echo '<a class="waves-effect waves-light tooltipped action-round pin-trigger" ' . $btnBase . ' data-tooltip="Edit (PIN)" href="?page=admin&act=tsk&sub=edit&id_surat=' . $row['id_surat'] . '" data-action-type="edit" data-id-surat="' . $row['id_surat'] . '"><i class="material-icons">edit</i></a>';
                                                        echo '<a class="waves-effect waves-light tooltipped action-round delete pin-trigger" ' . $btnBase . ' data-tooltip="Hapus (PIN)" href="?page=admin&act=tsk&sub=del&id_surat=' . $row['id_surat'] . '" data-action-type="delete" data-id-surat="' . $row['id_surat'] . '"><i class="material-icons">delete</i></a>';
                                                    }
                                                    echo '</div>';
                                                } else {
                                                    echo '<div class="grey-text" style="padding-top: 15px;">-</div>';
                                                }

                                                echo '</td></tr>';
                                                $seq++;
                                            }
                                        } else {
                                            echo '<tr><td colspan="7" class="center-align"><div class="card-panel grey lighten-4" style="margin: 20px;">';
                                            if (isset($_REQUEST['submit'])) {
                                                echo '<i class="material-icons large grey-text">search</i><p class="grey-text">Tidak ada data yang ditemukan untuk pencarian "<strong>' . stripslashes($cari) . '</strong>"</p>';
                                            } else {
                                                echo '<i class="material-icons large grey-text">inbox</i><p class="grey-text">Tidak ada data untuk ditampilkan.</p>';
                                            }
                                            echo '</div></td></tr>';
                                        }
                                                                                // Banner filter aktif (jika ada)
                                                                                if (!empty($filterKey) && isset($labels[$filterKey])) {
                                                                                        echo '<div class="card-panel blue-grey lighten-5" style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">'
                                                                                                . '<span class="blue-grey-text">Filter Bidang: <strong class="black-text">' . htmlspecialchars($labels[$filterKey]) . '</strong></span>'
                                                                                                . '<a class="btn-flat waves-effect" href="index.php?page=admin&act=tsk"><i class="material-icons left">clear</i>Hapus Filter</a>'
                                                                                            . '</div>';
                                                                                }

                                                                                ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Pengaturan Paginasi -->
            <div id="modal" class="modal">
                <div class="modal-content white">
                    <h5>Jumlah data yang ditampilkan per halaman</h5>
                    <?php
                    $query_sett_modal = mysqli_query($config, "SELECT id_sett, surat_keluar FROM tbl_sett");
                    list($id_sett, $surat_keluar_sett) = mysqli_fetch_array($query_sett_modal);
                    ?>
                    <div class="row">
                        <form method="post" action="">
                            <div class="input-field col s12">
                                <input type="hidden" value="<?php echo $id_sett; ?>" name="id_sett">
                                <div class="input-field col s1" style="float: left;"><i class="material-icons prefix md-prefix">looks_one</i></div>
                                <div class="input-field col s11 right" style="margin: -5px 0 20px;">
                                    <select class="browser-default validate" name="surat_keluar" required>
                                        <option value="<?php echo $surat_keluar_sett; ?>"><?php echo $surat_keluar_sett; ?></option>
                                        <option value="5">5</option>
                                        <option value="10">10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <div class="modal-footer white">
                                    <button type="submit" class="modal-action waves-effect waves-green btn-flat" name="simpan">Simpan</button>
                                    <?php
                                    if (isset($_REQUEST['simpan'])) {
                                        $id_sett_upd = "1";
                                        $surat_keluar_upd = $_REQUEST['surat_keluar'];
                                        $id_user_upd = $_SESSION['id_user'];
                                        $query_upd = mysqli_query($config, "UPDATE tbl_sett SET surat_keluar='$surat_keluar_upd', id_user='$id_user_upd' WHERE id_sett='$id_sett_upd'");
                                        if ($query_upd) {
                                            header("Location: index.php?page=admin&act=tsk");
                                            die();
                                        }
                                    }
                                    ?>
                                    <a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Batal</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Paginasi -->
<?php
            $query_pg = mysqli_query($config, "SELECT 1 " . $base_query . $where_clause);
            $cdata = mysqli_num_rows($query_pg);
            $cpg = ceil($cdata / $limit);

                        echo '<br/><!-- Pagination START -->
                                    <div class="center-align" style="margin: 12px 0 8px;">
                                        <ul class="pagination pager">';
        $extra = '';
        if (!empty($_GET['filter_bidang'])) { $extra .= '&filter_bidang=' . urlencode($_GET['filter_bidang']); }
        if ($cdata > $limit) {
                if ($pg > 1) {
                    $prev = $pg - 1;
            echo '<li><a href="index.php?page=admin&act=tsk&pg=1' . $extra . '"><i class="material-icons md-48">first_page</i></a></li><li><a href="index.php?page=admin&act=tsk&pg=' . $prev . $extra . '"><i class="material-icons md-48">chevron_left</i></a></li>';
                } else {
                    echo '<li class="disabled"><a><i class="material-icons md-48">first_page</i></a></li><li class="disabled"><a><i class="material-icons md-48">chevron_left</i></a></li>';
                }

                if ($pg < $cpg) {
                    $next = $pg + 1;
            echo '<li><a href="index.php?page=admin&act=tsk&pg=' . $next . $extra . '"><i class="material-icons md-48">chevron_right</i></a></li><li><a href="index.php?page=admin&act=tsk&pg=' . $cpg . $extra . '"><i class="material-icons md-48">last_page</i></a></li>';
                } else {
                    echo '<li class="disabled"><a><i class="material-icons md-48">chevron_right</i></a></li><li class="disabled"><a><i class="material-icons md-48">last_page</i></a></li>';
                }
            }
            echo '  </ul></div><!-- Pagination END -->';
        }
    }
}
?>

<style>
    /* Utility: prevent wrapping for specific header text */
    th.no-wrap { white-space: nowrap; }
    /* Utility: no wrap inline elements */
    .nowrap { white-space: nowrap; }
    /* Compact actions: remove spacing between buttons */
    .actions-compact a.btn { margin-left: 0 !important; margin-right: 6px !important; }
    .actions-compact a.btn:last-child { margin-right: 0 !important; }

    /* Cross-browser table stability (Chrome/Edge/Firefox) */
    #tbl { table-layout: fixed; width: 100%; border-collapse: collapse; }
    #tbl thead th, #tbl tbody td { box-sizing: border-box; }
    /* Enforce column widths via CSS to be consistent across browsers */
    #tbl thead th:nth-child(1) { width: 3%; }
    #tbl thead th:nth-child(2) { width: 30%; }
    #tbl thead th:nth-child(3) { width: 14%; }
    #tbl thead th:nth-child(4) { width: 18%; }
    #tbl thead th:nth-child(5) { width: 12%; }
    #tbl thead th:nth-child(6) { width: 10%; }
    #tbl thead th:nth-child(7) { width: 17%; }
    /* Make second-line text consistent across browsers */
    #tbl small { display: block; margin-top: 2px; line-height: 1.2; font-size: 0.9rem; }
    /* Better wrapping for long content like numbers/paths */
    #tbl td { overflow-wrap: anywhere; word-break: break-word; }
    /* CSS untuk Modal PIN */
    .pin-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1002;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }
    .pin-modal-container {
        background-color: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        padding: 30px;
        width: 100%;
        max-width: 450px;
        text-align: center;
        position: relative;
        transform: scale(0.9);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    .pin-modal-overlay.active .pin-modal-container {
        transform: scale(1);
        opacity: 1;
    }
    .pin-modal-close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 2rem;
        color: #9e9e9e;
        cursor: pointer;
        transition: color 0.2s;
    }
    .pin-modal-close:hover {
        color: #616161;
    }
    .pin-modal-title {
        font-size: 1.8rem;
        font-weight: 500;
        margin-bottom: 10px;
        color: #424242;
    }
    .pin-code-container {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin: 30px 0;
    }
    .pin-code-input {
        width: 45px !important;
        height: 55px !important;
        font-size: 24px !important;
        text-align: center !important;
        border: 2px solid #bdbdbd !important;
        border-radius: 8px !important;
        box-shadow: none !important;
        padding: 0 !important;
        background-color: #fff !important;
    }
    .pin-code-input:focus {
        border-color: #2196F3 !important;
        box-shadow: 0 0 8px 0 rgba(33, 150, 243, 0.5) !important;
    }
    .pin-modal-btn {
        border-radius: 25px;
        height: 45px;
        line-height: 45px;
    }
    .pin-error-message {
        color: #f44336;
        margin-top: 15px;
        font-weight: 500;
        min-height: 21px;
    }
</style>

<style>
/* Pagination styles (Surat Keluar) */
.pagination.pager { display:inline-flex; align-items:center; }
.pagination.pager li { margin: 0 3px; }
.pagination.pager li a {
    display:inline-flex; align-items:center; justify-content:center; gap:4px;
    border:1px solid rgba(0,0,0,.12); border-radius:10px; background:#fff; color:#455a64;
    height:36px; min-width:36px; padding:0 10px; box-shadow:0 1px 2px rgba(0,0,0,.08);
}
.pagination.pager li.active a { background:#1e88e5; color:#fff; border-color:#1e88e5; }
.pagination.pager li.disabled a { background:#f5f5f5; color:#bdbdbd; border-color:rgba(0,0,0,.08); pointer-events:none; }
.pagination.pager i.material-icons { font-size:20px; line-height:36px; height:36px; }
.pagination.pager i.material-icons.md-48 { font-size:20px; }
</style>

<!-- HTML untuk Modal PIN -->
<div id="pinModal" class="pin-modal-overlay">
    <div class="pin-modal-container">
        <span class="pin-modal-close">&times;</span>
        <i class="material-icons large blue-grey-text text-darken-1">https</i>
        <h5 class="pin-modal-title">Verifikasi PIN</h5>
        <p class="grey-text text-darken-1">Aksi ini memerlukan izin. Silakan masukkan 6 digit PIN.</p>
        
        <form id="pinForm" method="POST" action="#">
            <input type="hidden" name="pin" id="fullPin">
            <div class="pin-code-container">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="tel" class="pin-code-input" maxlength="1" pattern="[0-9]" required>
                <?php endfor; ?>
            </div>
            <p id="pinErrorMessage" class="pin-error-message"></p>
            <div class="row">
                <div class="input-field col s12">
                    <button type="submit" class="btn waves-effect waves-light blue darken-1 pin-modal-btn">Verifikasi & Lanjutkan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Live search setup
    const searchInput = document.getElementById('search');
    const tbody = document.getElementById('tbody-data');
    const infoPanel = document.getElementById('search-info-panel');
    const infoText = document.getElementById('search-info-text');
    let searchTimer;
    // Simpan HTML awal untuk restore saat pencarian dikosongkan
    const originalTableHTML = tbody ? tbody.innerHTML : '';

    function debounce(fn, wait) {
        let t;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function getFilterBidangParam() {
        const params = new URLSearchParams(window.location.search);
        const v = params.get('filter_bidang');
        return v ? `&filter_bidang=${encodeURIComponent(v)}` : '';
    }

    function updateInfoPanel(q) {
        if (q && q.trim() !== '') {
            infoText.textContent = q;
            infoPanel.style.display = 'block';
        } else {
            infoPanel.style.display = 'none';
            infoText.textContent = '';
        }
    }

    function rebindPinTriggers() {
        // Remove previous to avoid duplicate handlers
        document.querySelectorAll('.pin-trigger').forEach(el => {
            const newEl = el.cloneNode(true);
            el.parentNode.replaceChild(newEl, el);
        });
        // Reattach handler using the same logic below
        attachPinHandlers();
    }

    const doLiveSearch = debounce(() => {
        const q = searchInput.value.trim();
        // Jika kosong -> kembalikan ke tampilan awal (hindari 'halaman acak')
        if(q === '') {
            if(tbody) { tbody.innerHTML = originalTableHTML; }
            updateInfoPanel('');
            return; // tidak panggil AJAX
        }
        updateInfoPanel(q);
        const url = `src/SuratKeluar/ajax_search_surat_keluar.php?cari=${encodeURIComponent(q)}${getFilterBidangParam()}`;
        fetch(url, { credentials: 'same-origin' })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(html => {
                tbody.innerHTML = html;
                rebindPinTriggers();
            })
            .catch(err => {
                console.error('Live search error:', err);
            });
    }, 300);

    if (searchInput && tbody) {
        searchInput.addEventListener('input', doLiveSearch);
    }

    const modal = document.getElementById('pinModal');
    const modalClose = modal.querySelector('.pin-modal-close');
    const pinForm = document.getElementById('pinForm');
    const pinInputs = [...pinForm.querySelectorAll('.pin-code-input')];
    const fullPinInput = document.getElementById('fullPin');
    const errorMessage = document.getElementById('pinErrorMessage');
    
    let targetUrl = '';
    let actionType = '';
    let suratId = '';

    // Fungsi untuk membuka modal
    function openModal() {
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
        pinInputs[0].focus();
    }

    // Fungsi untuk menutup modal
    function closeModal() {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
            resetModal();
        }, 300);
    }

    // Fungsi untuk mereset modal
    function resetModal() {
        pinForm.reset();
        pinInputs.forEach(input => input.value = '');
        errorMessage.textContent = '';
        targetUrl = '';
        actionType = '';
        suratId = '';
    }

    function attachPinHandlers() {
        document.querySelectorAll('.pin-trigger').forEach(trigger => {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                targetUrl = this.getAttribute('href');
                actionType = this.dataset.actionType;
                suratId = this.dataset.idSurat;
                openModal();
            });
        });
    }

    // Initial bind for existing content
    attachPinHandlers();

    // Event listener untuk tombol close
    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Toggle Status (Operator Only)
    window.toggleStatus = function(id, ev){
        if(!id) return false;
        const e = ev || window.event;
        const btn = e && e.currentTarget ? e.currentTarget : document.activeElement;
        if(btn) btn.classList.add('disabled');
        fetch('src/SuratKeluar/update_status.php',{
            method:'POST',
            credentials:'same-origin',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'id='+encodeURIComponent(id)
        })
        .then(async r=>{ const t = await r.text(); try { return JSON.parse(t); } catch(e){ console.error('Raw response toggleStatus:', t); throw new Error('Response bukan JSON valid'); } })
        .then(j=>{
            if(!j.ok){ throw new Error(j.msg||'Gagal'); }
            const img = document.querySelector('.status-icon-'+id);
            if(img){ img.src = 'asset/img/'+(j.status==='finished'?'finished.png':'draft.png'); }
            // Update tooltip text
            if(btn && btn.getAttribute && btn.getAttribute('data-tooltip')){
                btn.setAttribute('data-tooltip', j.status==='finished' ? 'Set Draft' : 'Set Finished');
            }
            if(typeof M !== 'undefined' && M.Tooltip && btn){
                // re-init tooltip
                const instance = M.Tooltip.getInstance(btn);
                if(instance){ instance.destroy(); }
                M.Tooltip.init(btn, {});
            }
        })
        .catch(err=>{ alert('Gagal toggle status: '+err.message); })
        .finally(()=>{ if(btn) btn.classList.remove('disabled'); });
        return false;
    }

    // Logika input PIN
    pinInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value && index < pinInputs.length - 1) {
                pinInputs[index + 1].focus();
            }
            updateFullPin();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && index > 0) {
                pinInputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/\s/g, '').slice(0, 6);
            pasteData.split('').forEach((char, i) => {
                if (pinInputs[i]) pinInputs[i].value = char;
            });
            const lastInputIndex = Math.min(pasteData.length, 6) - 1;
            if (lastInputIndex >= 0) pinInputs[lastInputIndex].focus();
            updateFullPin();
        });
    });

    function updateFullPin() {
        fullPinInput.value = pinInputs.map(i => i.value).join('');
    }

    // Submit form PIN
    pinForm.addEventListener('submit', function (e) {
        e.preventDefault();
        updateFullPin();

        if (fullPinInput.value.length !== 6) {
            errorMessage.textContent = 'PIN harus terdiri dari 6 digit.';
            return;
        }

        errorMessage.textContent = '';
        const formData = new FormData();
        formData.append('id_surat', suratId);
        formData.append('pin', fullPinInput.value);

        fetch('src/Utils/verifikasi_pin_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                handleSuccessfulVerification();
            } else {
                errorMessage.textContent = data.message || 'PIN salah. Coba lagi.';
                pinInputs.forEach(input => input.value = '');
                pinInputs[0].focus();
            }
        })
        .catch(error => {
            errorMessage.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
            console.error('Error:', error);
        });
    });

    function handleSuccessfulVerification() {
        closeModal();
        
        // Sedikit penundaan agar modal sempat tertutup
        setTimeout(() => {
            if (actionType === 'delete') {
                if (confirm('PIN terverifikasi. Apakah Anda yakin ingin menghapus data ini?')) {
                    window.location.href = targetUrl;
                }
            } else if (actionType === 'view') {
                window.open(targetUrl, '_blank');
            } else { // 'edit' atau lainnya
                window.location.href = targetUrl;
            }
        }, 200);
    }
});
</script>

<?php
// ... existing code ...
// ... (rest of the file)
?>
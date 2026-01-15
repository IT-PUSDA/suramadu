<?php
ob_start();
// TAMBAHKAN BARIS INI UNTUK MEMUAT KONFIGURASI DATABASE
require(BASE_PATH . '/src/include/config.php');

//cek session
// session_start(); // Dihapus karena sudah dimulai di public/index.php

if (!isset($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: index.php");
    die();
} else {
    // Sinkronkan level peran dari database agar perubahan langsung efektif tanpa perlu logout/login
    if (isset($_SESSION['id_user'])) {
        $sid = (int) $_SESSION['id_user'];
        $resRole = mysqli_query($config, "SELECT admin FROM tbl_user WHERE id_user='$sid' LIMIT 1");
        if ($resRole && mysqli_num_rows($resRole) === 1) {
            list($dbAdmin) = mysqli_fetch_array($resRole);
            if ((string)$dbAdmin !== (string)$_SESSION['admin']) {
                $_SESSION['admin'] = (int)$dbAdmin;
            }
        }
    }
?>

    <!doctype html>
    <html lang="en">

    <!-- Include Head START -->
    <?php require(BASE_PATH . '/src/include/head.php'); ?>
    <!-- Include Head END -->

    <!-- Body START -->

    <body class="bg">

        <!-- Header START -->
        <header>

            <!-- Include Navigation START -->
            <?php require(BASE_PATH . '/src/include/menu.php'); ?>
            <!-- Include Navigation END -->

        </header>
        <!-- Header END -->

        <!-- Main START -->
        <main>

            <!-- container START -->
            <div class="container">

                <?php
                if (isset($_REQUEST['act'])) {
                    $act = $_REQUEST['act'];
                    switch ($act) {
                        case 'tsm':
                            include(BASE_PATH . '/src/SuratMasuk/transaksi_surat_masuk.php');
                            break;
                        case 'ctk':
                            include(BASE_PATH . '/src/Disposisi/cetak_disposisi.php');
                            break;
                        case 'tsk':
                            include(BASE_PATH . '/src/SuratKeluar/transaksi_surat_keluar.php');
                            break;
                        case 'tsk_nd':
                            include(BASE_PATH . '/src/SuratKeluar/transaksi_surat_keluar_nota_dinas.php');
                            break;
                        case 'tsk_ph':
                            include(BASE_PATH . '/src/SuratKeluar/transaksi_surat_keluar_produk_hukum.php');
                            break;
                        case 'tsk_keu':
                            include(BASE_PATH . '/src/SuratKeluar/transaksi_surat_keluar_keuangan.php');
                            break;
                        case 'not':
                            include(BASE_PATH . '/src/NotaDinas/transaksi_nota_dinas.php');
                            break;
                        case 'asm':
                            include(BASE_PATH . '/src/SuratMasuk/agenda_surat_masuk.php');
                            break;
                        case 'ask':
                            include(BASE_PATH . '/src/SuratKeluar/agenda_surat_keluar.php');
                            break;
                        case 'ask_nd':
                            include(BASE_PATH . '/src/SuratKeluar/agenda_surat_keluar_nota_dinas.php');
                            break;
                        case 'ask_ph':
                            include(BASE_PATH . '/src/SuratKeluar/agenda_surat_keluar_produk_hukum.php');
                            break;
                        case 'ask_keu':
                            include(BASE_PATH . '/src/SuratKeluar/agenda_surat_keluar_keuangan.php');
                            break;
                        case 'tdl':
                            include(BASE_PATH . '/src/Utils/transaksi_tindak_lanjut.php');
                            break;
                        case 'ref':
                            include(BASE_PATH . '/src/Pengaturan/referensi.php');
                            break;
                        case 'sett':
                            include(BASE_PATH . '/src/Pengaturan/pengaturan.php');
                            break;
                        case 'pro':
                            include(BASE_PATH . '/src/User/profil.php');
                            break;
                        case 'gsm':
                            include(BASE_PATH . '/src/SuratMasuk/galeri_sm.php');
                            break;
                        case 'gsk':
                            include(BASE_PATH . '/src/SuratKeluar/galeri_sk.php');
                            break;
                        case 'arsip':
                            include(BASE_PATH . '/src/SuratKeluar/arsip_per_bidang.php');
                            break;
                        case 'arsip_op':
                            include(BASE_PATH . '/src/SuratKeluar/arsip_operator.php');
                            break;
                        case 'activity_log':
                            if ((int)$_SESSION['admin'] !== 1) {
                                $_SESSION['err'] = 'Anda tidak memiliki akses ke log aktivitas!';
                                echo '<div class="card red lighten-5"><div class="card-content"><span class="red-text">' . htmlspecialchars($_SESSION['err']) . '</span></div></div>';
                                unset($_SESSION['err']);
                            } else {
                                include(BASE_PATH . '/src/Utils/activity_log.php');
                            }
                            break;
                        case 'bank_dok':
                            // Bank Dokumen - Super Admin (1), Operator Sekretariat (3), User Bidang (4) [Read Only]
                            if (!in_array((int)$_SESSION['admin'], [1, 3, 4])) {
                                $_SESSION['err'] = 'Anda tidak memiliki akses ke fitur ini!';
                                echo '<div class="card red lighten-5"><div class="card-content"><span class="red-text">' . $_SESSION['err'] . '</span></div></div>';
                                unset($_SESSION['err']);
                            } else {
                                $sub = isset($_GET['sub']) ? $_GET['sub'] : '';
                                if ($sub === '') {
                                    include(BASE_PATH . '/src/Utils/bank_dokumen.php');
                                } elseif ($sub === 'list_jenis') {
                                    include(BASE_PATH . '/src/Utils/bank_dokumen_list_jenis.php');
                                } elseif ($sub === 'list_dokumen') {
                                    include(BASE_PATH . '/src/Utils/bank_dokumen_list_dokumen.php');
                                } else {
                                    include(BASE_PATH . '/src/Utils/bank_dokumen_process.php');
                                }
                            }
                            break;
                    }
                } else {
                ?>
                    <!-- Row START -->
                    <div class="row">

                        <!-- Include Header Instansi START -->
                        <?php require(BASE_PATH . '/src/include/header_instansi.php'); ?>
                        <!-- Include Header Instansi END -->

                        <!-- Welcome Message START -->
                        <div class="col s12">
                            <div class="card">
                                <div class="card-content">
                                    <h4>Selamat Datang <?php echo $_SESSION['nama']; ?></h4>
                                    <p class="description">Anda login sebagai
                                
                                        <?php
                                        if ($_SESSION['admin'] == 1) {
                                            echo "<strong>Super Admin</strong>. Anda memiliki akses penuh terhadap sistem.";
                                        } elseif ($_SESSION['admin'] == 2) {
                                            echo "<strong>Pimpinan</strong>. Berikut adalah statistik data yang tersimpan dalam sistem.";
                                        } elseif ($_SESSION['admin'] == 3) {
                                            echo "<strong>Admin</strong>. Berikut adalah statistik data yang tersimpan dalam sistem.";
                                        } elseif ($_SESSION['admin'] == 4) {
                                            echo "<strong>User</strong>. Berikut adalah statistik data yang tersimpan dalam sistem.";
                                        }?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Welcome Message END -->

                        <?php
                        $kode_bidang = $_SESSION['kode_bidang'];
                        //menghitung jumlah surat masuk
                        if ($_SESSION['admin'] == 4) {
                            $id_user = $_SESSION['id_user'];
                            $count1 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_surat_masuk join tbl_disposisi on tbl_surat_masuk.id_surat=tbl_disposisi.id_surat
    					where tbl_disposisi.id_tujuan='$id_user' "));
                        } else {
                            $count1 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_surat_masuk"));
                        }
                        // Menghitung jumlah Surat Keluar (scoping khusus operator & bidang)
                        $operator_id_list_sql = '';
                        if ($_SESSION['admin'] == 4) { // level Bidang: hanya dirinya sendiri
                            $id_user = (int)$_SESSION['id_user'];
                            $count2 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE bidang='$kode_bidang'"));
                        } elseif ($_SESSION['admin'] == 3) { // level Operator: semua user dalam 1 kelompok bidang
                            // Peta grup -> username (samakan dengan file transaksi_surat_keluar*)
                            $BIDANG_USERNAMES_DASH = [
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
                            $uname = strtoupper($_SESSION['username']);
                            // Tambahan: coba juga nama lengkap bila ada
                            if (isset($_SESSION['nama'])) {
                                $namaUpper = strtoupper($_SESSION['nama']);
                            } else { $namaUpper = ''; }
                            $foundGroup = null;
                            foreach ($BIDANG_USERNAMES_DASH as $gKey => $arrU) {
                                foreach ($arrU as $uChk) {
                                    $token = strtoupper($uChk);
                                    // cocokkan penuh atau sebagai substring di username / nama tampil
                                    if ($uname === $token || strpos($uname, $token) !== false || ($namaUpper && (strpos($namaUpper, $token) !== false))) {
                                        $foundGroup = $gKey; break 2;
                                    }
                                }
                            }
                            // Fallback: ganti spasi/underscore lalu ulang substring sederhana
                            if ($foundGroup === null) {
                                $unameFlat = str_replace(['_', ' '], '', $uname);
                                foreach ($BIDANG_USERNAMES_DASH as $gKey => $arrU) {
                                    foreach ($arrU as $uChk) {
                                        $tokenFlat = str_replace(['_', ' '], '', strtoupper($uChk));
                                        if (strpos($unameFlat, $tokenFlat) !== false) { $foundGroup = $gKey; break 2; }
                                    }
                                }
                            }
                            // Prefer operator_access_info() for consistent scoping with other pages
                            $idsOperator = [];
                            @include_once __DIR__ . '/include/operator_access.php';
                            if (function_exists('operator_access_info')) {
                                $info = operator_access_info($config, $_SESSION);
                                $idsOperator = !empty($info['allowed_ids']) ? $info['allowed_ids'] : [];
                            }
                            // Fallback: try the previous exact-match method if no result
                            if (empty($idsOperator) && $foundGroup !== null) {
                                $names = array_map('strtoupper', $BIDANG_USERNAMES_DASH[$foundGroup]);
                                $esc = [];
                                foreach ($names as $n) { $esc[] = "'" . mysqli_real_escape_string($config, $n) . "'"; }
                                $sqlUsers = "SELECT id_user FROM tbl_user WHERE UPPER(username) IN (" . implode(',', $esc) . ")";
                                $ru = mysqli_query($config, $sqlUsers);
                                if ($ru) { while ($r = mysqli_fetch_assoc($ru)) { $idsOperator[] = (int)$r['id_user']; } }
                            }
                            if (empty($idsOperator)) { // final fallback: dirinya sendiri
                                $idsOperator[] = (int)$_SESSION['id_user'];
                            }
                            $operator_id_list_sql = implode(',', array_map('intval', $idsOperator));
                            $sqlCountOp = "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_user IN ($operator_id_list_sql)";
                            $rOp = mysqli_query($config, $sqlCountOp);
                            $count2 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE bidang='$kode_bidang'"));
                        } else { // selain operator & bidang: total
                            $count2 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_surat_keluar"));
                        }
                        // cek apakah kolom jenis ada
                        $hasJenis = false;
                        $resJenis = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
                        if ($resJenis && mysqli_num_rows($resJenis) === 1) { $hasJenis = true; }
                        // hitung jumlah per jenis mengikuti scope
                        if ($_SESSION['admin'] == 4) {
                            $whereUser = " AND id_user='" . (int)$_SESSION['id_user'] . "'";
                        } elseif ($_SESSION['admin'] == 3 && $operator_id_list_sql !== '') {
                            $whereUser = " AND id_user IN (" . $operator_id_list_sql . ")";
                        } else {
                            $whereUser = '';
                        }
                        if ($hasJenis) {
                            $qND = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE jenis='nota_dinas'" . $whereUser);
                            $qPH = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE jenis='produk_hukum'" . $whereUser);
                            $qKEU = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE jenis='keuangan'" . $whereUser);
                            $countND = ($qND ? (int)mysqli_fetch_assoc($qND)['c'] : 0);
                            $countPH = ($qPH ? (int)mysqli_fetch_assoc($qPH)['c'] : 0);
                            $countKEU = ($qKEU ? (int)mysqli_fetch_assoc($qKEU)['c'] : 0);
                        } else {
                            $countND = $countPH = $countKEU = 0; // kolom belum ada
                        }
                        // Hitung jumlah Arsip: hanya surat yang benar-benar sudah dimasukkan ke berkas arsip
                        // yaitu memiliki relasi id_arsip_berkas (bukan sekadar status finished)
                        $countArsip = 0;
                        // Pastikan kolom relasi tersedia agar query aman
                        $hasRel = false;
                        $resRel = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'id_arsip_berkas'");
                        if ($resRel && mysqli_num_rows($resRel) === 1) { $hasRel = true; }
                        if (!$hasRel) { @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL, ADD INDEX idx_arsip_rel (id_arsip_berkas)"); $hasRel = true; }
                        if ($hasRel) {
                            $qArsip = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_arsip_berkas IS NOT NULL" . $whereUser);
                            $countArsip = ($qArsip ? (int)mysqli_fetch_assoc($qArsip)['c'] : 0);
                        }
                        //menghitung jumlah surat masuk
                        if ($_SESSION['admin'] == 4) {
                            $id_user = $_SESSION['id_user'];
                            $count3 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_disposisi where id_tujuan='$id_user' "));
                        } else {
                            $count3 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_disposisi"));
                        }
                        //menghitung jumlah klasifikasi
                        $count4 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_klasifikasi"));

                        //menghitung jumlah pengguna
                        $count5 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_user"));
                        //menghitung jumlah Nota dinas
                        $count6 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_notdin"));
                        ?>

                        <!-- Info Statistic START
                <div class="col s12 m4">
                    <div class="card cyan">
                        <div class="card-content">
                            <span class="card-title white-text"><i class="material-icons md-36">mail</i> Jumlah Surat Masuk</span>
                            <a href="?page=tsm"><?php echo '<h5 class="white-text link">' . $count1 . ' Surat Masuk</h5>'; ?></a>
                        </div>
                    </div>
                </div> -->

                        <div class="col s12">
                            <div class="row home-stats" style="margin-bottom: 0;">
                            <div class="col s12 m6 l3">
                                <div class="card lime darken-1 hs-card">
                                    <div class="card-content">
                                        <span class="card-title white-text"><i class="material-icons md-36">drafts</i> Surat Keluar</span>
                                        <a href="index.php?page=admin&act=tsk" class="white-text" style="text-decoration:none;"><h5 class="white-text link" style="margin:8px 0 0;"><?php echo number_format((int)$count2); ?> SURAT KELUAR</h5></a>
                                    </div>
                                </div>
                            </div>

                            <!-- Kartu shortcut jenis Surat Keluar -->
                            <div class="col s12 m6 l3">
                                <a href="index.php?page=admin&act=tsk_nd" class="hs-link">
                                    <div class="card teal hs-card">
                                        <div class="card-content">
                                            <span class="card-title white-text" style="display:flex;align-items:center;gap:8px;"><i class="material-icons md-36">assignment</i> Nota Dinas</span>
                                            <h5 class="white-text link" style="margin:8px 0 0;"><?php echo number_format((int)$countND); ?> SURAT NOTA DINAS</h5>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col s12 m6 l3">
                                <a href="index.php?page=admin&act=tsk_ph" class="hs-link">
                                    <div class="card deep-orange hs-card">
                                        <div class="card-content">
                                            <span class="card-title white-text" style="display:flex;align-items:center;gap:8px;"><i class="material-icons md-36">gavel</i> Produk Hukum</span>
                                            <h5 class="white-text link" style="margin:8px 0 0;"><?php echo number_format((int)$countPH); ?> SURAT PRODUK HUKUM</h5>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col s12 m6 l3">
                                <a href="index.php?page=admin&act=tsk_keu" class="hs-link">
                                    <div class="card indigo hs-card">
                                        <div class="card-content">
                                            <span class="card-title white-text" style="display:flex;align-items:center;gap:8px;"><i class="material-icons md-36">attach_money</i> Keuangan</span>
                                            <h5 class="white-text link" style="margin:8px 0 0;"><?php echo number_format((int)$countKEU); ?> SURAT KEUANGAN</h5>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php if (in_array((int)$_SESSION['admin'], [1, 2, 3, 4], true)) { ?>
                            <div class="col s12 m6 l3">
                                    <?php if (in_array((int)$_SESSION['admin'], [1, 2], true)) { ?>
                                        <a href="index.php?page=admin&act=arsip" class="hs-link">
                                    <?php } elseif ((int)$_SESSION['admin'] === 3) { ?>
                                        <a href="index.php?page=admin&act=arsip_op" class="hs-link">
                                    <?php } ?>
                                <div class="card black hs-card">
                                    <div class="card-content white-text">
                                        <span class="card-title" style="display:flex;align-items:center;gap:8px;"><i class="material-icons md-36">archive</i> Arsip</span>
                                        <h5 class="white-text link" style="margin:8px 0 0;"><?php echo number_format((int)$countArsip); ?> SURAT TERARSIP</h5>
                                    </div>
                                </div>
                                    </a>
                                </div>
                                <!-- Card: Bank Dokumen (Super Admin, Admin Sekretariat, Operator Sekretariat) -->
                                <?php if (in_array((int)$_SESSION['admin'], [1, 3, 4], true)) { ?>
                                <div class="col s12 m6 l3">
                                    <a href="index.php?page=admin&act=bank_dok" class="hs-link">
                                        <div class="card amber darken-2 hs-card" style="background: linear-gradient(135deg, #FFD700 0%, #FFC700 100%); border: 2px solid #DAA520;">
                                            <div class="card-content">
                                                <span class="card-title" style="color: #333; display:flex;align-items:center;gap:8px;"><i class="material-icons md-36">folder_special</i> Bank Dokumen</span>
                                                <h5 style="color: #333; margin:8px 0 0;">📦 KELOLA DOKUMEN</h5>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <?php } ?>
                                <?php if ((int)$_SESSION['admin'] === 1) { ?>
                                <!-- Card: Jumlah Pengguna (Super Admin) diletakkan di sebelah Bank Dokumen) -->
                                <div class="col s12 m6 l3">
                                    <a href="index.php?page=admin&act=sett&sub=usr" class="hs-link">
                                        <div class="card blue accent-2 hs-card">
                                            <div class="card-content">
                                                <span class="card-title white-text" style="display:flex;align-items:center;gap:8px;"><i class="material-icons md-36">people</i> Jumlah Pengguna</span>
                                                <h5 class="white-text link" style="margin:8px 0 0;"><?php echo number_format((int)$count5); ?> PENGGUNA</h5>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <?php } ?>
                                <?php } ?>
                            </div>
                            </div>
                        </div>
                        
                        <!--
    			<div class="col s12 m4">
                    <div class="card yellow darken-1">
                        <div class="card-content">
                            <span class="card-title white-text"><i class="material-icons md-36">drafts</i> Jumlah Nota Dinas</span>
                            <a href="index.php?page=admin&act=not"><?php echo '<h5 class="white-text link">' . $count6 . ' Nota Dinas</h5>'; ?></a>
                        </div>
                    </div>
                </div>
    
              
                <div class="col s12 m4">
                    <div class="card deep-orange">
                        <div class="card-content">
                            <span class="card-title white-text"><i class="material-icons md-36">class</i> Jumlah Klasifikasi Surat</span>
                            <a href="index.php?page=admin&act=ref"><?php echo '<h5 class="white-text link">' . $count4 . ' Klasifikasi Surat</h5>'; ?></a>
                        </div>
                    </div>
                </div>
    			-->

                        <?php /* Card 'Jumlah Pengguna' dipindahkan ke baris utama (sebelah Arsip) khusus Super Admin */ ?>

                        <?php if ((int)$_SESSION['admin'] === 1) { ?>
                        <!-- Surat Keluar per Bidang/UPT -->
                        <div class="col s12">
                            <div class="card" style="border-radius:10px;">
                                <div class="card-content">
                                    <h5 style="margin:0 0 16px; display:flex; align-items:center; gap:8px;">
                                        <i class="material-icons" style="color:#546e7a;">dashboard</i>
                                        Ringkasan Surat Keluar per Bidang/UPT
                                    </h5>
                                    <?php
                                    // Peta grup -> daftar username uploader (berdasarkan tbl_user.username)
                                    $BIDANG_USERNAMES = [
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
                                        'upt-pasuruan'  => ['PASURUAN','PASURUAN','PASURUAN'], /* placeholder if later added */
                                        'upt-madura'    => ['MADURA'],
                                    ];

                                    $BIDANG_LABELS = [
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

                                    $COLOR_CLASS = [
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

                                    // Build counts per bidang using the same scope as arsip_per_bidang:
                                    // match tokens against UPPER(username) or UPPER(nama) via LIKE,
                                    // and fallback to detecting kode bidang inside no_surat.
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

                                    $counts = [];
                                    foreach ($BIDANG_USERNAMES as $key => $usernames) {
                                        $conds = [];
                                        foreach ((array)$usernames as $tok) {
                                            $t = strtoupper($tok);
                                            $esc = mysqli_real_escape_string($config, $t);
                                            $conds[] = "UPPER(u.username) LIKE '%$esc%'";
                                            $conds[] = "UPPER(u.nama) LIKE '%$esc%'";
                                        }
                                        // fallback: also match kode di no_surat
                                        if (isset($bidangCodes[$key]) && $bidangCodes[$key] !== '') {
                                            $codeEsc = mysqli_real_escape_string($config, $bidangCodes[$key]);
                                            $conds[] = "s.no_surat LIKE '%$codeEsc%'";
                                        }
                                        if (empty($conds)) { $counts[$key] = 0; continue; }
                                        $where = '(' . implode(' OR ', $conds) . ')';
                                        $sql = "SELECT COUNT(*) AS c FROM tbl_surat_keluar s LEFT JOIN tbl_user u ON s.id_user=u.id_user WHERE $where";
                                        $res = mysqli_query($config, $sql);
                                        $row = $res ? mysqli_fetch_assoc($res) : ['c' => 0];
                                        $counts[$key] = (int)$row['c'];
                                    }
                                    ?>

                                    <div class="row" style="margin-bottom:0;">
                                        <?php foreach ($BIDANG_USERNAMES as $key => $terms): ?>
                                            <div class="col s12 m6 l4 xl3">
                                                <a href="index.php?page=admin&act=tsk&filter_bidang=<?php echo urlencode($key); ?>" class="block-link" style="text-decoration:none;">
                                                    <div class="card <?php echo $COLOR_CLASS[$key] ?? 'blue-grey'; ?>" style="border-radius:12px;">
                                                        <div class="card-content white-text" style="min-height:110px;">
                                                            <span class="card-title" style="display:flex; align-items:center; gap:8px;"><i class="material-icons md-36">drafts</i> <?php echo $BIDANG_LABELS[$key]; ?></span>
                                                            <h5 class="white-text" style="margin-top:6px; letter-spacing:.2px;"><?php echo number_format((int)$counts[$key]); ?> SURAT KELUAR</h5>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="right-align" style="margin-top:-10px;">
                                        <small class="grey-text">Klik salah satu kartu untuk melihat daftar terfilter.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                    </div>
                    <!-- Row END -->
                <?php
                }
                ?>
            </div>
            <!-- container END -->

        </main>
        <!-- Main END -->

        <!-- Include Footer START -->
        <?php require(BASE_PATH . '/src/include/footer.php'); ?>
        <!-- Include Footer END -->

    </body>
    <!-- Body END -->

    <style>
        /* Home cards: uniform size + rounded corners */
    .home-stats .card.hs-card { border-radius: 14px; height: 120px; display:flex; }
    .home-stats .card.hs-card .card-content { border-radius: 14px; display:flex; flex-direction:column; justify-content:center; height:100%; padding: 16px 18px; }
        .home-stats .hs-link { text-decoration: none; display:block; }
        .home-stats .hs-sub { margin-top: 8px; opacity: .95; font-weight: 500; }
        /* Make icons and titles align nicely */
        .home-stats .card-title { margin: 0; line-height: 1.1; }
        /* Smaller, cleaner count text on home cards */
        .home-stats .card.hs-card h5.link { font-size: 1.15rem; font-weight: 600; letter-spacing: .2px; margin: 6px 0 0; }
    /* Align with default Materialize gutters to match rows above/below */
    .row.home-stats { margin-left: -0.75rem; margin-right: -0.75rem; }
    .home-stats .col { padding-left: 0.75rem; padding-right: 0.75rem; }
    .home-stats .card.hs-card { margin: 8px 0; }
    @media (max-width: 992px) { .home-stats .card.hs-card { height: 115px; } }
        @media (max-width: 600px) {
            .home-stats .card.hs-card { height: auto; }
            .home-stats .card.hs-card h5.link { font-size: 1.05rem; }
            .row.home-stats { margin-left: -0.75rem; margin-right: -0.75rem; }
            .home-stats .col { padding-left: 0.75rem; padding-right: 0.75rem; }
        }
    </style>

    </html>

<?php
// Tutup blok else besar yang dimulai setelah pengecekan session
}
// Selesaikan output buffering jika digunakan
if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_end_flush(); }
?>
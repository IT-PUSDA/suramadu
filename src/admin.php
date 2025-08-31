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
                        case 'ctk_ter':
                            include(BASE_PATH . '/src/Utils/cetak_terusan.php');
                            break;
                        case 'tsk':
                            include(BASE_PATH . '/src/SuratKeluar/transaksi_surat_keluar.php');
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
                                        } else {
                                            echo "<strong>Operator</strong>. Berikut adalah statistik data yang tersimpan dalam sistem.";
                                        } ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Welcome Message END -->

                        <?php
                        //menghitung jumlah surat masuk
                        if ($_SESSION['admin'] == 4) {
                            $id_user = $_SESSION['id_user'];
                            $count1 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_surat_masuk join tbl_disposisi on tbl_surat_masuk.id_surat=tbl_disposisi.id_surat
    					where tbl_disposisi.id_tujuan='$id_user' "));
                        } else {
                            $count1 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_surat_masuk"));
                        }
                        //menghitung jumlah surat masuk
                        if ($_SESSION['admin'] == 4) {
                            $id_user = $_SESSION['id_user'];
                            $count2 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_surat_keluar where id_user='$id_user' "));
                        } else {
                            $count2 = mysqli_num_rows(mysqli_query($config, "SELECT * FROM tbl_surat_keluar"));
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

                        <div class="col s12 m4">
                            <div class="card lime darken-1">
                                <div class="card-content">
                                    <span class="card-title white-text"><i class="material-icons md-36">drafts</i> Jumlah Surat Keluar</span>
                                    <a href="index.php?page=admin&act=tsk"><?php echo '<h5 class="white-text link">' . $count2 . ' Surat Keluar</h5>'; ?></a>
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

                        <?php
                        if ($_SESSION['id_user'] == 1 || $_SESSION['admin'] == 3) { ?>
                            <div class="col s12 m4">
                                <div class="card blue accent-2">
                                    <div class="card-content">
                                        <span class="card-title white-text"><i class="material-icons md-36">people</i> Jumlah Pengguna</span>
                                        <a><?php echo '<h5 class="white-text link">' . $count5 . ' Pengguna</h5>'; ?></a>
                                    </div>
                                </div>
                            </div>
                            <!-- Info Statistic START -->
                        <?php
                        }
                        ?>

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

                                    // Kumpulkan semua username unik dan ambil id_user-nya
                                    $allUsernames = [];
                                    foreach ($BIDANG_USERNAMES as $list) { foreach ($list as $u) { $allUsernames[] = strtoupper($u); } }
                                    $allUsernames = array_values(array_unique($allUsernames));
                                    $in = "'" . implode("','", array_map(function($s) use ($config){ return mysqli_real_escape_string($config, $s); }, $allUsernames)) . "'";
                                    $resUsers = mysqli_query($config, "SELECT id_user, UPPER(username) AS uname FROM tbl_user WHERE UPPER(username) IN ($in)");
                                    $unameToId = [];
                                    if ($resUsers) {
                                        while ($r = mysqli_fetch_assoc($resUsers)) { $unameToId[$r['uname']] = (int)$r['id_user']; }
                                    }

                                    // Ambil jumlah per bidang
                                    $counts = [];
                                    foreach ($BIDANG_USERNAMES as $key => $usernames) {
                                        $ids = [];
                                        foreach ($usernames as $u) {
                                            $uUp = strtoupper($u);
                                            if (isset($unameToId[$uUp])) { $ids[] = $unameToId[$uUp]; }
                                        }
                                        if (count($ids) === 0) { $counts[$key] = 0; continue; }
                                        $idList = implode(',', array_map('intval', $ids));
                                        $sql = "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_user IN ($idList)";
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
                                                            <h5 class="white-text" style="margin-top:6px; letter-spacing:.2px;"><?php echo number_format($counts[$key]); ?> SURAT KELUAR</h5>
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

    </html>

<?php
}
?>
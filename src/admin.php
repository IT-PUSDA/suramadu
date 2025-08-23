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
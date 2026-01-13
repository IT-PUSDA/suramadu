<?php
// Pengecekan session sudah dilakukan di admin.php, jadi tidak perlu di sini.
?>
<nav class="blue darken-1">
    <div class="nav-wrapper">
        <a href="index.php?page=admin" class="brand-logo center hide-on-large-only"><i class="material-icons md-36"></i>SURAMADU</a>
        <ul id="slide-out" class="side-nav" data-simplebar-direction="vertical">
            <li class="no-padding">
                <div class="logo-side center blue darken-3">
                    <?php
                    $query = mysqli_query($config, "SELECT * FROM tbl_instansi");
                    while ($data = mysqli_fetch_array($query)) {
                        if (!empty($data['logo'])) {
                            echo '<img class="logoside" src="upload/' . $data['logo'] . '"/>';
                        } else {
                            echo '<img class="logoside" src="asset/img/logo.png"/>';
                        }
                        if (!empty($data['nama'])) {
                            echo '<h5 class="instansi-side">' . $data['nama'] . '</h5>';
                        } else {
                            echo '<h5 class="instansi-side">instansi</h5>';
                        }
                        if (!empty($data['alamat'])) {
                            echo '<p class="description-side">' . $data['alamat'] . '</p>';
                        } else {
                            echo '<p class="description-side">Surabaya</p>';
                        }
                    }
                    ?>
                </div>
            </li>
            <li class="no-padding blue darken-4">
                <ul class="collapsible collapsible-accordion">
                    <li>
                        <a class="collapsible-header"><i class="material-icons">account_circle</i><?php echo $_SESSION['nama']; ?></a>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="index.php?page=admin&act=pro">Profil</a></li>
                                <li><a href="index.php?page=admin&act=pro&sub=pass">Ubah Password</a></li>
                                <li><a href="index.php?page=Auth/logout">Logout</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </li>
            <li><a href="index.php?page=admin"><i class="material-icons middle">dashboard</i> Beranda</a></li>
            <li class="no-padding">

                <ul class="collapsible collapsible-accordion">
                    <li>
                        <a class="collapsible-header"><i class="material-icons">repeat</i> Transaksi Surat</a>
                        <div class="collapsible-body">
                            <ul>
                                <!--<li><a href="index.php?page=admin&act=tsm">Surat Masuk</a></li>-->
                                <li><a href="index.php?page=admin&act=tsk">Surat Keluar</a></li>
                                <li><a href="index.php?page=admin&act=tsk_nd">Nota Dinas</a></li>
                                <li><a href="index.php?page=admin&act=tsk_ph">Produk Hukum</a></li>
                                <li><a href="index.php?page=admin&act=tsk_keu">Keuangan</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>

            </li>
            <li><a href="index.php?page=admin&act=ask"><i class="material-icons middle">assignment</i> Buku Agenda</a></li>
            <!--
			<li class="no-padding">
                <ul class="collapsible collapsible-accordion">
                    <li>
                        <a class="collapsible-header"><i class="material-icons">image</i> Galeri File</a>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="index.php?page=admin&act=gsm">Surat Masuk</a></li>
                                <li><a href="index.php?page=admin&act=gsk">Surat Keluar</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </li>
			-->
            <li><a href="index.php?page=admin&act=ref"><i class="material-icons middle">class</i> Klasifikasi</a></li>
            <?php if (isset($_SESSION['admin']) && in_array((int)$_SESSION['admin'], [1, 3, 4], true)) { ?>
            <li><a href="index.php?page=admin&act=bank_dok"><i class="material-icons middle">folder_special</i> Bank Dokumen</a></li>
            <?php } ?>
            <li class="no-padding">
                <?php
                if ($_SESSION['admin'] == 1) { ?>
                    <ul class="collapsible collapsible-accordion">
                        <li>
                            <a class="collapsible-header"><i class="material-icons">settings</i> Pengaturan</a>
                            <div class="collapsible-body">
                                <ul>
                                    <li><a href="index.php?page=admin&act=sett">Instansi</a></li>
                                    <li><a href="index.php?page=admin&act=sett&sub=usr">User</a></li>
                                    <li><a href="index.php?page=admin&act=ref">Klasifikasi Surat</a></li>
                                    <li><a href="index.php?page=admin&act=sett&sub=back">Backup Database</a></li>
                                    <li><a href="index.php?page=admin&act=sett&sub=rest">Restore Database</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                <?php
                }
                ?>
                <?php
                if ($_SESSION['admin'] == 2) { ?>
                    <ul class="collapsible collapsible-accordion">
                        <li>
                            <a class="collapsible-header"><i class="material-icons">settings</i> Pengaturan</a>
                            <div class="collapsible-body">
                                <ul>
                                    <li><a href="index.php?page=admin&act=sett">Instansi</a></li>
                                    <li><a href="index.php?page=admin&act=sett&sub=usr">User</a></li>
                                    <li><a href="index.php?page=admin&act=ref">Klasifikasi Surat</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                <?php
                }
                ?>
            <?php if (isset($_SESSION['admin']) && (int)$_SESSION['admin'] === 1) { ?>
            <li><a href="index.php?page=admin&act=activity_log"><i class="material-icons middle">timeline</i> Log Aktivitas</a></li>
            <?php } ?>
            <?php if (isset($_SESSION['admin']) && in_array((int)$_SESSION['admin'], [1,2], true)) { ?>
            <li><a href="index.php?page=admin&act=arsip"><i class="material-icons middle">archive</i> Arsip</a></li>
            <?php } elseif (isset($_SESSION['admin']) && (int)$_SESSION['admin']===3) { ?>
            <li><a href="index.php?page=admin&act=arsip_op"><i class="material-icons middle">archive</i> Arsip</a></li>
            <?php } ?>
            </li>
        </ul>
        <!-- Menu on medium and small screen END -->

        <!-- Menu on large screen START -->
        <ul class="center hide-on-med-and-down" id="nv">
            <li><a href="index.php?page=admin" class="ams hide-on-med-and-down"><i class="material-icons md-36">mail</i>SURAMADU</a></li>
            <li>
                <div class="grs">
                    </>
            </li>
            <li><a href="index.php?page=admin"><i class="material-icons"></i>&nbsp; Beranda</a></li>

            <li><a class="dropdown-button" href="#!" data-activates="transaksi">Transaksi Surat <i class="material-icons md-18">arrow_drop_down</i></a></li>
            <ul id='transaksi' class='dropdown-content'>
                <!--<li><a href="index.php?page=admin&act=tsm">Surat Masuk</a></li>-->
                <li><a href="index.php?page=admin&act=tsk">Surat Keluar</a></li>
                <li><a href="index.php?page=admin&act=tsk_nd">Nota Dinas</a></li>
                <li><a href="index.php?page=admin&act=tsk_ph">Produk Hukum</a></li>
                <li><a href="index.php?page=admin&act=tsk_keu">Keuangan</a></li>
            </ul>


            <li><a href="index.php?page=admin&act=ask">Buku Agenda</a></li>
            <?php if(isset($_SESSION['admin']) && in_array((int)$_SESSION['admin'], [1, 3, 4], true)) { ?>
            <li><a href="index.php?page=admin&act=bank_dok">Bank Dokumen</a></li>
            <?php } ?>
            <?php if(isset($_SESSION['admin']) && in_array((int)$_SESSION['admin'], [1,2], true)) { ?>
            <li><a href="index.php?page=admin&act=arsip">Arsip</a></li>
            <?php } ?>
            <?php if(isset($_SESSION['admin']) && (int)$_SESSION['admin']===3){ ?>
                <li><a href="index.php?page=admin&act=arsip_op">Arsip</a></li>
            <?php } ?>
            <!--<li><a class="dropdown-button" href="#!" data-activates="agenda">Galeri File <i class="material-icons md-18">arrow_drop_down</i></a></li>
                <ul id='agenda' class='dropdown-content'>
                    <li><a href="index.php?page=admin&act=gsm">Surat Masuk</a></li>
                    <li><a href="index.php?page=admin&act=gsk">Surat Keluar</a></li>
                </ul>
			-->
            <!--<li><a href="?page=ref">Klasifikasi Surat</a></li>-->
            <?php
            if ($_SESSION['admin'] == 1) { ?>
                <li><a class="dropdown-button" href="#!" data-activates="pengaturan">Pengaturan <i class="material-icons md-18">arrow_drop_down</i></a></li>
                <ul id='pengaturan' class='dropdown-content'>
                    <li><a href="index.php?page=admin&act=sett">Instansi</a></li>
                    <li><a href="index.php?page=admin&act=sett&sub=usr">User</a></li>
                    <li><a href="index.php?page=admin&act=ref">Klasifikasi Surat</a></li>
                    <li class="divider"></li>
                    <li><a href="index.php?page=admin&act=sett&sub=back">Backup Database</a></li>
                    <li><a href="index.php?page=admin&act=sett&sub=rest">Restore Database</a></li>
                </ul>
            <?php
            }
            ?>
            <?php if (isset($_SESSION['admin']) && (int)$_SESSION['admin'] === 1) { ?>
                <li><a href="index.php?page=admin&act=activity_log">Log Aktivitas</a></li>
            <?php } ?>
            <?php if ($_SESSION['admin'] == 2) { ?>
                <li><a class="dropdown-button" href="#!" data-activates="pengaturan2">Pengaturan <i class="material-icons md-18">arrow_drop_down</i></a></li>
                <ul id='pengaturan2' class='dropdown-content'>
                    <li><a href="index.php?page=admin&act=sett">Instansi</a></li>
                    <li><a href="index.php?page=admin&act=sett&sub=usr">User</a></li>
                    <li><a href="index.php?page=admin&act=ref">Klasifikasi Surat</a></li>
                </ul>
            <?php } ?>

            <li class="right" style="margin-right: 10px;"><a class="dropdown-button" href="#!" data-activates="logout"><i class="material-icons">account_circle</i> <?php echo $_SESSION['nama']; ?><i class="material-icons md-18">arrow_drop_down</i></a></li>
            <ul id='logout' class='dropdown-content'>
                <li><a href="index.php?page=admin&act=pro">Profil</a></li>
                <li><a href="index.php?page=admin&act=pro&sub=pass">Ubah Password</a></li>
                <li class="divider"></li>
                <li><a href="index.php?page=Auth/logout"><i class="material-icons">settings_power</i> Logout</a></li>
            </ul>
        </ul>
        <!-- Menu on large screen END -->
        <a href="#" data-activates="slide-out" class="button-collapse" id="menu"><i class="material-icons">menu</i></a>
    </div>
</nav>
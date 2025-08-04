<?php
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

        if (isset($_REQUEST['sub'])) {
            $sub = $_REQUEST['sub'];
            switch ($sub) {
                case 'add':
                    include(BASE_PATH . "/src/SuratKeluar/tambah_surat_keluar.php");
                    break;
                case 'edit':
                    include(BASE_PATH . "/src/SuratKeluar/edit_surat_keluar.php");
                    break;
                case 'del':
                    include(BASE_PATH . "/src/SuratKeluar/hapus_surat_keluar.php");
                    break;
            }
        } else {

            $query = mysqli_query($config, "SELECT surat_keluar FROM tbl_sett");
            list($surat_keluar) = mysqli_fetch_array($query);

            //pagging
            $limit = $surat_keluar;
            $pg = @$_GET['pg'];
            if (empty($pg)) {
                $curr = 0;
                $pg = 1;
            } else {
                $curr = ($pg - 1) * $limit;
            } ?>

            <!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <div class="z-depth-1">
                        <nav class="secondary-nav">
                            <div class="nav-wrapper blue-grey darken-1">
                                <div class="col m7">
                                    <ul class="left">
                                        <li class="waves-effect waves-light hide-on-small-only"><a href="index.php?page=admin&act=tsk" class="judul"><i class="material-icons">drafts</i> Surat Keluar</a></li>
                                        <li class="waves-effect waves-light">
                                            <?php
                                            if ($_SESSION['admin'] != 2) {
                                                echo '<a href="index.php?page=admin&act=tsk&sub=add"><i class="material-icons md-24">add_circle</i> Tambah Data</a>';
                                            }
                                            ?>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col m5 hide-on-med-and-down">
                                    <form method="post" action="index.php?page=admin&act=tsk">
                                        <div class="input-field round-in-box">
                                            <input id="search" type="search" name="cari" placeholder="Ketik dan tekan enter mencari data..." required>
                                            <label for="search"><i class="material-icons">search</i></label>
                                            <input type="submit" name="submit" class="hidden">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
                <!-- Secondary Nav END -->
            </div>
            <!-- Row END -->

            <?php
            if (isset($_SESSION['succAdd'])) {
                $succAdd = $_SESSION['succAdd'];
                echo '<div id="alert-message" class="row">
                                <div class="col m12">
                                    <div class="card green lighten-5">
                                        <div class="card-content notif">
                                            <span class="card-title green-text"><i class="material-icons md-36">done</i> ' . $succAdd . '</span>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                unset($_SESSION['succAdd']);
            }
            if (isset($_SESSION['succEdit'])) {
                $succEdit = $_SESSION['succEdit'];
                echo '<div id="alert-message" class="row">
                                <div class="col m12">
                                    <div class="card green lighten-5">
                                        <div class="card-content notif">
                                            <span class="card-title green-text"><i class="material-icons md-36">done</i> ' . $succEdit . '</span>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                unset($_SESSION['succEdit']);
            }
            if (isset($_SESSION['succDel'])) {
                $succDel = $_SESSION['succDel'];
                echo '<div id="alert-message" class="row">
                                <div class="col m12">
                                    <div class="card green lighten-5">
                                        <div class="card-content notif">
                                            <span class="card-title green-text"><i class="material-icons md-36">done</i> ' . $succDel . '</span>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                unset($_SESSION['succDel']);
            }
            ?>

            <!-- Row form Start -->
            <div class="row jarak-form">

    <?php
            if (isset($_REQUEST['submit'])) {
                $cari = mysqli_real_escape_string($config, $_REQUEST['cari']);
                echo '
                        <div class="col s12" style="margin-top: -18px;">
                            <div class="card blue lighten-5">
                                <div class="card-content">
                                <p class="description">Hasil pencarian untuk kata kunci <strong>"' . stripslashes($cari) . '"</strong><span class="right"><a href="index.php?page=admin&act=tsk"><i class="material-icons md-36" style="color: #333;">clear</i></a></span></p>
                                </div>
                            </div>
                        </div>

                        <div class="col m12" id="colres">
                            <div class="card">
                                <div class="card-content">
                                    <div class="table-responsive">
                                        <table class="striped highlight responsive-table" id="tbl">
                                            <thead class="blue lighten-4" id="head">
                                                <tr>
                                                    <th class="center-align" width="10%">
                                                        <i class="material-icons tiny">assignment</i><br/>
                                                        <span class="table-header">No. Agenda</span><br/>
                                                        <small>Kode</small>
                                                    </th>
                                                    <th width="31%">
                                                        <i class="material-icons tiny">description</i><br/>
                                                        <span class="table-header">Isi Ringkas</span><br/>
                                                        <small>File</small>
                                                    </th>
                                                    <th width="24%">
                                                        <i class="material-icons tiny">business</i><br/>
                                                        <span class="table-header">Tujuan</span><br/>
                                                        <small>Perihal</small>
                                                    </th>
                                                    <th class="center-align" width="19%">
                                                        <i class="material-icons tiny">date_range</i><br/>
                                                        <span class="table-header">No. Surat</span><br/>
                                                        <small>Tgl Surat</small>
                                                    </th>
                                                    <th class="center-align" width="16%">
                                                        <i class="material-icons tiny">settings</i><br/>
                                                        <span class="table-header">Tindakan</span>
                                                        <span class="right">
                                                            <i class="material-icons tooltipped" data-position="left" data-tooltip="Pengaturan tampilan" style="color: #333;">settings</i>
                                                        </span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                    <tr>';

                //script untuk mencari data
                if ($_SESSION['admin'] == 4) {
                    $id_user = $_SESSION['id_user'];
                    $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar where id_user='$id_user' and isi like '%$cari%' or perihal like '%$cari%'
									or tgl_surat like '%$cari%' or tujuan like '%$cari%' ORDER by id_surat DESC LIMIT $curr, $limit");
                } else {
                    $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar where isi LIKE '%$cari%' or tgl_surat like '%$cari%' or perihal like '%$cari%' 
									or tujuan like '%$cari%' ORDER by id_surat DESC LIMIT $curr, $limit");
                }

                if (mysqli_num_rows($query) > 0) {
                    $no = 1;
                    while ($row = mysqli_fetch_array($query)) {
                        echo '
                                        <td class="center-align">' . $row['no_agenda'] . '<br/><hr style="margin: 5px 0;"/><small class="grey-text">' . $row['kode'] . '</small></td>
                                        <td>' . substr($row['isi'], 0, 200) . '<br/><br/><strong>File:</strong>';

                        if (!empty($row['file'])) {
                            echo ' <strong><a href="index.php?page=admin&act=gsk&sub=fsk&id_surat=' . $row['id_surat'] . '" class="blue-text text-darken-2">' . $row['file'] . '</a></strong>';
                        } else {
                            echo ' <em class="grey-text">Tidak ada file yang diupload</em>';
                        }
                        echo '</td>
                                        <td>' . $row['tujuan'] . '<br/><strong class="blue-text text-darken-3">' . $row['perihal'] . '</strong></td>';

                        $y = substr($row['tgl_surat'], 0, 4);
                        $m = substr($row['tgl_surat'], 5, 2);
                        $d = substr($row['tgl_surat'], 8, 2);

                        if ($m == "01") {
                            $nm = "Januari";
                        } elseif ($m == "02") {
                            $nm = "Februari";
                        } elseif ($m == "03") {
                            $nm = "Maret";
                        } elseif ($m == "04") {
                            $nm = "April";
                        } elseif ($m == "05") {
                            $nm = "Mei";
                        } elseif ($m == "06") {
                            $nm = "Juni";
                        } elseif ($m == "07") {
                            $nm = "Juli";
                        } elseif ($m == "08") {
                            $nm = "Agustus";
                        } elseif ($m == "09") {
                            $nm = "September";
                        } elseif ($m == "10") {
                            $nm = "Oktober";
                        } elseif ($m == "11") {
                            $nm = "November";
                        } elseif ($m == "12") {
                            $nm = "Desember";
                        }
                        echo '

                                        <td class="center-align">
                                            <div class="card-panel grey lighten-5" style="margin: 5px 0; padding: 10px;">
                                                <strong>' . $row['no_surat'] . '</strong><br/>
                                                <small class="grey-text">' . $d . " " . $nm . " " . $y . '</small>
                                            </div>
                                        </td>
                                        <td class="center-align">';

                        if ($_SESSION['admin'] == 2) {
                            echo '<div class="action-buttons">
                                    <button class="btn blue-grey waves-effect waves-light tooltipped" data-position="top" data-tooltip="Tidak ada aksi tersedia">
                                        <i class="material-icons">block</i>
                                    </button>
                                  </div>';
                        } else {
                            echo '<div class="action-buttons">
                                    <a class="btn blue waves-effect waves-light tooltipped" data-position="top" data-tooltip="Edit Data" 
                                       href="index.php?page=admin&act=tsk&sub=edit&id_surat=' . $row['id_surat'] . '">
                                        <i class="material-icons">edit</i>
                                    </a>
                                    <a class="btn deep-orange waves-effect waves-light tooltipped" data-position="top" data-tooltip="Hapus Data" 
                                       href="index.php?page=admin&act=tsk&sub=del&id_surat=' . $row['id_surat'] . '" 
                                       onclick="return confirm(\'Apakah Anda yakin ingin menghapus data ini?\')">
                                        <i class="material-icons">delete</i>
                                    </a>
                                  </div>';
                        }
                        echo '
                                        </td>
                                    </tr>
                                </tbody>';
                    }
                } else {
                    echo '<tr><td colspan="5" class="center-align">
                            <div class="card-panel grey lighten-4" style="margin: 20px;">
                                <i class="material-icons large grey-text">search</i>
                                <p class="grey-text">Tidak ada data yang ditemukan untuk pencarian "<strong>' . stripslashes($cari) . '</strong>"</p>
                                <a href="index.php?page=admin&act=tsk" class="btn blue waves-effect waves-light">
                                    <i class="material-icons left">arrow_back</i>Kembali ke Daftar
                                </a>
                            </div>
                          </td></tr>';
                }
                echo '</table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Row form END -->';

                $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE id_user='$id_user'");
                $cdata = mysqli_num_rows($query);
                $cpg = ceil($cdata / $limit);

                echo '<!-- Pagination START -->
                              <ul class="pagination">';

                if ($cdata > $limit) {

                    //first and previous pagging
                    if ($pg > 1) {
                        $prev = $pg - 1;
                        echo '<li><a href="index.php?page=admin&act=tsk&pg=1"><i class="material-icons md-48">first_page</i></a></li>
                                      <li><a href="index.php?page=admin&act=tsk&pg=' . $prev . '"><i class="material-icons md-48">chevron_left</i></a></li>';
                    } else {
                        echo '<li class="disabled"><a href=""><i class="material-icons md-48">first_page</i></a></li>
                                      <li class="disabled"><a href=""><i class="material-icons md-48">chevron_left</i></a></li>';
                    }

                    //perulangan pagging
                    //for($i=1; $i <= $cpg; $i++)
                    //  if($i != $pg){
                    //    echo '<li class="waves-effect waves-dark"><a href="?page=tsk&pg='.$i.'"> '.$i.' </a></li>';
                    //} else {
                    //  echo '<li class="active waves-effect waves-dark"><a href="?page=tsk&pg='.$i.'"> '.$i.' </a></li>';
                    //}

                    //last and next pagging
                    if ($pg < $cpg) {
                        $next = $pg + 1;
                        echo '<li><a href="index.php?page=admin&act=tsk&pg=' . $next . '"><i class="material-icons md-48">chevron_right</i></a></li>
                                      <li><a href="index.php?page=admin&act=tsk&pg=' . $cpg . '"><i class="material-icons md-48">last_page</i></a></li>';
                    } else {
                        echo '<li class="disabled"><a href=""><i class="material-icons md-48">chevron_right</i></a></li>
                                      <li class="disabled"><a href=""><i class="material-icons md-48">last_page</i></a></li>';
                    }
                    echo '
                            </ul>
                            <!-- Pagination END -->';
                } else {
                    echo '';
                }
            } else {

                echo '
                        <div class="col m12" id="colres">
                            <div class="card">
                                <div class="card-content">
                                    <div class="table-responsive">
                                        <table class="striped highlight responsive-table" id="tbl">
                                            <thead class="blue lighten-4" id="head">
                                                <tr>
                                                    <th width="10%" class="center-align">
                                                        <i class="material-icons tiny">assignment</i><br/>
                                                        <span class="table-header">No. Agenda</span><br/>
                                                        <small>Kode</small>
                                                    </th>
                                                    <th width="31%">
                                                        <i class="material-icons tiny">description</i><br/>
                                                        <span class="table-header">Isi Ringkas</span><br/>
                                                        <small>File</small>
                                                    </th>
                                                    <th width="24%">
                                                        <i class="material-icons tiny">business</i><br/>
                                                        <span class="table-header">Tujuan</span><br/>
                                                        <small>Perihal</small>
                                                    </th>
                                                    <th width="19%" class="center-align">
                                                        <i class="material-icons tiny">date_range</i><br/>
                                                        <span class="table-header">No. Surat</span><br/>
                                                        <small>Tgl Surat</small>
                                                    </th>
                                                    <th width="16%" class="center-align">
                                                        <i class="material-icons tiny">settings</i><br/>
                                                        <span class="table-header">Tindakan</span>
                                                        <span class="right tooltipped" data-position="left" data-tooltip="Atur jumlah data yang ditampilkan">
                                                            <a class="modal-trigger" href="#modal">
                                                                <i class="material-icons" style="color: #333;">settings</i>
                                                            </a>
                                                        </span>
                                                    </th>

                                        <div id="modal" class="modal">
                                            <div class="modal-content white">
                                                <h5>Jumlah data yang ditampilkan per halaman</h5>';
                $query = mysqli_query($config, "SELECT id_sett,surat_keluar FROM tbl_sett");
                list($id_sett, $surat_keluar) = mysqli_fetch_array($query);
                echo '
                                                <div class="row">
                                                    <form method="post" action="">
                                                        <div class="input-field col s12">
                                                            <input type="hidden" value="' . $id_sett . '" name="id_sett">
                                                            <div class="input-field col s1" style="float: left;">
                                                                <i class="material-icons prefix md-prefix">looks_one</i>
                                                            </div>
                                                            <div class="input-field col s11 right" style="margin: -5px 0 20px;">
                                                                <select class="browser-default validate" name="surat_keluar" required>
                                                                    <option value="' . $surat_keluar . '">' . $surat_keluar . '</option>
                                                                    <option value="5">5</option>
                                                                    <option value="10">10</option>
                                                                    <option value="20">20</option>
                                                                    <option value="50">50</option>
                                                                    <option value="100">100</option>
                                                                </select>
                                                            </div>
                                                            <div class="modal-footer white">
                                                                <button type="submit" class="modal-action waves-effect waves-green btn-flat" name="simpan">Simpan</button>';
                if (isset($_REQUEST['simpan'])) {
                    $id_sett = "1";
                    $surat_keluar = $_REQUEST['surat_keluar'];
                    $id_user = $_SESSION['id_user'];

                    $query = mysqli_query($config, "UPDATE tbl_sett SET surat_keluar='$surat_keluar',id_user='$id_user' WHERE id_sett='$id_sett'");
                    if ($query == true) {
                        header("Location: index.php?page=admin&act=tsk");
                        die();
                    }
                }
                echo '
                                                                <a href="#!" class=" modal-action modal-close waves-effect waves-green btn-flat">Batal</a>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                </tr>
                            </thead>

                            <tbody>
                                <tr>';

                //script untuk mencari data
                if ($_SESSION['admin'] == 4) {
                    $id_user = $_SESSION['id_user'];
                    $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar where id_user='$id_user' ORDER by id_surat DESC LIMIT $curr, $limit");
                } else {
                    $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar ORDER by id_surat DESC LIMIT $curr, $limit");
                }

                if (mysqli_num_rows($query) > 0) {
                    $no = 1;
                    while ($row = mysqli_fetch_array($query)) {
                        echo '
                                    <td>' . $row['no_agenda'] . '<br/><hr/>' . $row['kode'] . '</td>
                                    <td>' . substr($row['isi'], 0, 200) . '<br/><br/><strong>File :</strong>';

                        if (!empty($row['file'])) {
                            echo ' <strong><a href="index.php?page=admin&act=gsk&sub=fsk&id_surat=' . $row['id_surat'] . '" class="blue-text text-darken-2">' . $row['file'] . '</a></strong>';
                        } else {
                            echo ' <em class="grey-text">Tidak ada file yang diupload</em>';
                        }
                        echo '</td>
                                    <td>' . $row['tujuan'] . '<br/><strong class="blue-text text-darken-3">' . $row['perihal'] . '</strong></td>';

                        $y = substr($row['tgl_surat'], 0, 4);
                        $m = substr($row['tgl_surat'], 5, 2);
                        $d = substr($row['tgl_surat'], 8, 2);

                        if ($m == "01") {
                            $nm = "Januari";
                        } elseif ($m == "02") {
                            $nm = "Februari";
                        } elseif ($m == "03") {
                            $nm = "Maret";
                        } elseif ($m == "04") {
                            $nm = "April";
                        } elseif ($m == "05") {
                            $nm = "Mei";
                        } elseif ($m == "06") {
                            $nm = "Juni";
                        } elseif ($m == "07") {
                            $nm = "Juli";
                        } elseif ($m == "08") {
                            $nm = "Agustus";
                        } elseif ($m == "09") {
                            $nm = "September";
                        } elseif ($m == "10") {
                            $nm = "Oktober";
                        } elseif ($m == "11") {
                            $nm = "November";
                        } elseif ($m == "12") {
                            $nm = "Desember";
                        }
                        echo '

                                    <td class="center-align">
                                        <div class="card-panel grey lighten-5" style="margin: 5px 0; padding: 10px;">
                                            <strong>' . $row['no_surat'] . '</strong><br/>
                                            <small class="grey-text">' . $d . " " . $nm . " " . $y . '</small>
                                        </div>
                                    </td>
                                    <td class="center-align">';

                        if ($_SESSION['admin'] == 2) {
                            echo '<div class="action-buttons">
                                    <button class="btn blue-grey waves-effect waves-light tooltipped" data-position="top" data-tooltip="Tidak ada aksi tersedia">
                                        <i class="material-icons">block</i>
                                    </button>
                                  </div>';
                        } else {
                            echo '<div class="action-buttons">
                                    <a class="btn blue waves-effect waves-light tooltipped" data-position="top" data-tooltip="Edit Data" 
                                       href="index.php?page=admin&act=tsk&sub=edit&id_surat=' . $row['id_surat'] . '">
                                        <i class="material-icons">edit</i>
                                    </a>
                                    <a class="btn deep-orange waves-effect waves-light tooltipped" data-position="top" data-tooltip="Hapus Data" 
                                       href="index.php?page=admin&act=tsk&sub=del&id_surat=' . $row['id_surat'] . '" 
                                       onclick="return confirm(\'Apakah Anda yakin ingin menghapus data ini?\')">
                                        <i class="material-icons">delete</i>
                                    </a>
                                  </div>';
                        }
                        echo '
                                    </td>
                                </tr>
                            </tbody>';
                    }
                } else {
                    if ($_SESSION['admin'] != 2) {
                        echo '<tr><td colspan="5" class="center-align">
                                <div class="card-panel grey lighten-4" style="margin: 20px;">
                                    <i class="material-icons large grey-text">inbox</i>
                                    <p class="grey-text">Tidak ada data untuk ditampilkan.</p>
                                    <a href="index.php?page=admin&act=tsk&sub=add" class="btn blue waves-effect waves-light">
                                        <i class="material-icons left">add</i>Tambah Data Baru
                                    </a>
                                </div>
                              </td></tr>';
                    }
                }
                echo '</table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Row form END -->';
                if ($_SESSION['admin'] == 4) {
                    $id_user = $_SESSION['id_user'];
                    $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar where id_user='$id_user'");
                } else {
                    $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar");
                }
                //$id_user=$_SESSION['id_user'];
                //$query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar where id_user='$id_user'");
                $cdata = mysqli_num_rows($query);
                $cpg = ceil($cdata / $limit);

                echo '<br/><!-- Pagination START -->
                          <ul class="pagination">';

                if ($cdata > $limit) {

                    //first and previous pagging
                    if ($pg > 1) {
                        $prev = $pg - 1;
                        echo '<li><a href="index.php?page=admin&act=tsk&pg=1"><i class="material-icons md-48">first_page</i></a></li>
                                  <li><a href="index.php?page=admin&act=tsk&pg=' . $prev . '"><i class="material-icons md-48">chevron_left</i></a></li>';
                    } else {
                        echo '<li class="disabled"><a href=""><i class="material-icons md-48">first_page</i></a></li>
                                  <li class="disabled"><a href=""><i class="material-icons md-48">chevron_left</i></a></li>';
                    }

                    //perulangan pagging

                    // for($i=1; $i <= $cpg; $i++)
                    //   if($i != $pg){
                    //     echo '<li class="waves-effect waves-dark"><a href="?page=tsk&pg='.$i.'"> '.$i.' </a></li>';
                    //} else {
                    //  echo '<li class="active waves-effect waves-dark"><a href="?page=tsk&pg='.$i.'"> '.$i.' </a></li>';
                    //}

                    //last and next pagging
                    if ($pg < $cpg) {
                        $next = $pg + 1;
                        echo '<li><a href="index.php?page=admin&act=tsk&pg=' . $next . '"><i class="material-icons md-48">chevron_right</i></a></li>
                                  <li><a href="index.php?page=admin&act=tsk&pg=' . $cpg . '"><i class="material-icons md-48">last_page</i></a></li>';
                    } else {
                        echo '<li class="disabled"><a href=""><i class="material-icons md-48">chevron_right</i></a></li>
                                  <li class="disabled"><a href=""><i class="material-icons md-48">last_page</i></a></li>';
                    }
                    echo '
                        </ul>
                        <!-- Pagination END -->';
                } else {
                    echo '';
                }
            }
        }
    }
}
    ?>
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
                case 'edit':
                    include 'edit_surat_keluar.php';
                    break;
                case 'del':
                    include 'hapus_surat_keluar.php';
                    break;
                case 'proses_tambah':
                    include 'proses_tambah_surat_keluar.php';
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
            <div class="row">
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
            <div class="row jarak-form">
                <div class="col m12" id="colres">
                    <div class="card">
                        <div class="card-content">
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
                                            <th width="10%" class="center-align">No. Agenda<br /><small>Kode</small></th>
                                            <th width="22%">Isi Ringkas<br /><small>File</small></th>
                                            <th width="20%">Tujuan<br /><small>Perihal</small></th>
                                            <th width="17%" class="center-align">No. Surat<br /><small>Tgl Surat</small></th>
                                            <th width="20%">Pembuat<br /><small>Tgl Dibuat</small></th>
                                            <th width="11%" class="center-align">
                                                <div style="display: flex; justify-content: center; align-items: center; gap: 8px;">
                                                    Tindakan
                                                    <a class="modal-trigger tooltipped" href="#modal" data-position="left" data-tooltip="Atur jumlah data"><i class="material-icons" style="color: #333;">settings</i></a>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // PENERAPAN LOGIKA HAK AKSES DIMULAI DI SINI
                                        $is_admin_user = ($_SESSION['admin'] == 4);
                                        $base_query = "FROM tbl_surat_keluar";
                                        $where_clause = "";

                                        // 1. Filter Data: Jika Admin User, hanya tampilkan data miliknya
                                        if ($is_admin_user) {
                                            $where_clause .= " WHERE id_user='$id_user'";
                                        }

                                        // Tambahkan filter pencarian jika ada
                                        if (isset($_REQUEST['submit'])) {
                                            $cari = mysqli_real_escape_string($config, $_REQUEST['cari']);
                                            $search_condition = "(isi LIKE '%$cari%' OR perihal LIKE '%$cari%' OR tujuan LIKE '%$cari%')";
                                            $where_clause .= ($is_admin_user ? " AND " : " WHERE ") . $search_condition;
                                        }

                                        // Query untuk mengambil data sesuai hak akses
                                        $query = mysqli_query($config, "SELECT * " . $base_query . $where_clause . " ORDER BY id_surat DESC LIMIT $curr, $limit");

                                        if (mysqli_num_rows($query) > 0) {
                                            while ($row = mysqli_fetch_array($query)) {
                                                echo '
                                                <tr style="vertical-align: top;">
                                                    <td class="center-align">' . $row['no_agenda'] . '<hr class="grey lighten-3" style="margin: 4px 0;"/>' . $row['kode'] . '</td>
                                                    <td>' . $row['isi'];

                                                if (!empty($row['file'])) {
                                                    echo '<br/><br/><strong>File : </strong>
                                                          <a href="src/SuratKeluar/lihat_file_sk.php?id_surat=' . $row['id_surat'] . '" target="_blank" style="text-decoration: underline;">' . $row['file'] . '</a>';
                                                }

                                                echo '</td>
                                                    <td>' . $row['tujuan'] . '<br/><small class="grey-text text-darken-1">' . $row['perihal'] . '</small></td>
                                                    <td class="center-align">' . $row['no_surat'] . '<hr class="grey lighten-3" style="margin: 4px 0;"/>' . indoDate($row['tgl_surat']) . '</td>
                                                    <td>' . $row['nama_pembuat'] . '<br/><small class="grey-text text-darken-1">' . (isset($row['tgl_dibuat']) ? date('d M Y, H:i', strtotime($row['tgl_dibuat'])) : '') . '</small></td>
                                                    <td class="center-align">';

                                                // 2. Batasi Tombol: Super Admin & Verifikator bisa semua, Admin User hanya data miliknya
                                                $can_manage = in_array($_SESSION['admin'], [1, 2, 3]); // Super Admin & Verifikator
                                                $is_owner = $row['id_user'] == $_SESSION['id_user'];

                                                if ($can_manage || $is_owner) {
                                                    echo '
                                                    <div style="display: flex; justify-content: center; gap: 5px; padding-top: 5px;">
                                                        <a class="btn small blue waves-effect waves-light" style="color:white;" href="?page=admin&act=tsk&sub=edit&id_surat=' . $row['id_surat'] . '"><i class="material-icons" style="color:white;">edit</i>
                                                            EDIT
                                                        </a>
                                                        <a class="btn small deep-orange waves-effect waves-light" style="color:white;" href="?page=admin&act=tsk&sub=del&id_surat=' . $row['id_surat'] . '" onclick="return confirm(\'Apakah Anda yakin ingin menghapus data ini?\');"><i class="material-icons" style="color:white;">delete</i>
                                                            DEL
                                                        </a>
                                                    </div>';
                                                } else {
                                                    echo '<div class="grey-text" style="padding-top: 15px;">-</div>';
                                                }

                                                echo '</td>
                                                </tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="5" class="center-align"><div class="card-panel grey lighten-4" style="margin: 20px;">';
                                            if (isset($_REQUEST['submit'])) {
                                                echo '<i class="material-icons large grey-text">search</i><p class="grey-text">Tidak ada data yang ditemukan untuk pencarian "<strong>' . stripslashes($cari) . '</strong>"</p>';
                                            } else {
                                                echo '<i class="material-icons large grey-text">inbox</i><p class="grey-text">Tidak ada data untuk ditampilkan.</p>';
                                            }
                                            echo '</div></td></tr>';
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

            echo '<br/><!-- Pagination START --><ul class="pagination">';
            if ($cdata > $limit) {
                if ($pg > 1) {
                    $prev = $pg - 1;
                    echo '<li><a href="index.php?page=admin&act=tsk&pg=1"><i class="material-icons md-48">first_page</i></a></li><li><a href="index.php?page=admin&act=tsk&pg=' . $prev . '"><i class="material-icons md-48">chevron_left</i></a></li>';
                } else {
                    echo '<li class="disabled"><a><i class="material-icons md-48">first_page</i></a></li><li class="disabled"><a><i class="material-icons md-48">chevron_left</i></a></li>';
                }

                if ($pg < $cpg) {
                    $next = $pg + 1;
                    echo '<li><a href="index.php?page=admin&act=tsk&pg=' . $next . '"><i class="material-icons md-48">chevron_right</i></a></li><li><a href="index.php?page=admin&act=tsk&pg=' . $cpg . '"><i class="material-icons md-48">last_page</i></a></li>';
                } else {
                    echo '<li class="disabled"><a><i class="material-icons md-48">chevron_right</i></a></li><li class="disabled"><a><i class="material-icons md-48">last_page</i></a></li>';
                }
            }
            echo '</ul><!-- Pagination END -->';
        }
    }
}
?>
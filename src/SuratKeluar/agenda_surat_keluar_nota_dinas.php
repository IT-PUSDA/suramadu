<?php
// Cek session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header('Location: ./');
    die();
}

// Helper: build base query with optional operator/bidang scope
function ask_nd_build_query($config, $dari_tanggal, $sampai_tanggal) {
    $whereTanggal = "tgl_surat BETWEEN '" . mysqli_real_escape_string($config, $dari_tanggal) . "' AND '" . mysqli_real_escape_string($config, $sampai_tanggal) . "'";
    $whereJenis = "jenis='nota_dinas'";

    if ((int)$_SESSION['admin'] === 4) { // Bidang: hanya miliknya
        $id_user = (int)$_SESSION['id_user'];
        $sqlUser = "id_user='$id_user'";
        if (isset($_SESSION['kode_bidang']) && !empty($_SESSION['kode_bidang'])) {
            $kb = mysqli_real_escape_string($config, $_SESSION['kode_bidang']);
            $sqlUser = "(bidang='$kb' OR id_user='$id_user')";
        }
        return mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE $whereJenis AND $sqlUser AND $whereTanggal");
    }

    if ((int)$_SESSION['admin'] === 3) { // Operator: batasi per kelompok user (mengikuti dashboard)
        $BIDANG_USERNAMES = [
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
        $namaUpper = isset($_SESSION['nama']) ? strtoupper($_SESSION['nama']) : '';
        $foundGroup = null;
        foreach ($BIDANG_USERNAMES as $gKey => $arrU) {
            foreach ($arrU as $uChk) {
                $token = strtoupper($uChk);
                if ($uname === $token || strpos($uname, $token) !== false || ($namaUpper && strpos($namaUpper, $token) !== false)) {
                    $foundGroup = $gKey; break 2;
                }
            }
        }
        if ($foundGroup === null) {
            $unameFlat = str_replace(['_', ' '], '', $uname);
            foreach ($BIDANG_USERNAMES as $gKey => $arrU) {
                foreach ($arrU as $uChk) {
                    $tokenFlat = str_replace(['_', ' '], '', strtoupper($uChk));
                    if (strpos($unameFlat, $tokenFlat) !== false) { $foundGroup = $gKey; break 2; }
                }
            }
        }
        $idsOperator = [];
        if ($foundGroup !== null) {
            $names = array_map('strtoupper', $BIDANG_USERNAMES[$foundGroup]);
            $esc = [];
            foreach ($names as $n) { $esc[] = "'" . mysqli_real_escape_string($config, $n) . "'"; }
            $sqlUsers = "SELECT id_user FROM tbl_user WHERE UPPER(username) IN (" . implode(',', $esc) . ")";
            $ru = mysqli_query($config, $sqlUsers);
            if ($ru) { while ($r = mysqli_fetch_assoc($ru)) { $idsOperator[] = (int)$r['id_user']; } }
        }
        if (empty($idsOperator)) { $idsOperator[] = (int)$_SESSION['id_user']; }
        $operator_id_list_sql = implode(',', array_map('intval', $idsOperator));
        return mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE $whereJenis AND id_user IN ($operator_id_list_sql) AND $whereTanggal");
    }

    return mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE $whereJenis AND $whereTanggal");
}

// Shared page renderer (adapted from agenda_surat_keluar.php)
function ask_nd_render($config, $title, $slugAct) {
    echo '<style type="text/css">.hidd{display:none}@media print{body{font-size:12px!important;color:#212121}.disp{text-align:center;margin:-.5rem 0}.hidd{display:block}.logodisp{float:left;position:relative;width:80px;height:80px;margin:0 0 0 1.2rem}#nama{font-size:20px!important;text-transform:uppercase;font-weight:bold;margin:-2.5rem 0 -3.7rem 0}.up{font-size:17px!important;font-weight:normal;text-transform:uppercase}.status{font-size:17px!important;font-weight:normal;margin-bottom:-.1rem}#alamat{margin-top:-15px;font-size:13px}.separator{border-bottom:2px solid #616161;margin:1rem 0 -.7rem}}</style>';
    // Build a clean label for headings (remove leading 'Cetak Agenda ' to avoid duplicate wording)
    $cleanLabel = preg_replace('/^\s*cetak\s+agenda\s+/i', '', $title);

    if (isset($_REQUEST['submit'])) {
        $dari_tanggal = $_REQUEST['dari_tanggal'];
        $sampai_tanggal = $_REQUEST['sampai_tanggal'];
        if ($dari_tanggal === '' || $sampai_tanggal === '') {
            header("Location: ./admin.php?page=admin&act=".$slugAct);
            die();
        }
        $query = ask_nd_build_query($config, $dari_tanggal, $sampai_tanggal);
        echo "
        <div class='row'>
            <div class='col s12'>
                <div class='z-depth-1'>
                    <nav class='secondary-nav'>
                        <div class='nav-wrapper blue-grey darken-1'>
                            <div class='col 12'>
                                <ul class='left'>
                                    <li class='waves-effect waves-light'><a href='?page=admin&act=$slugAct' class='judul'><i class='material-icons'>print</i> $title<a></li>
                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
        <div class='row jarak-form black-text'>
            <form class='col s12' method='post' action=''>
                <div class='input-field col s3'>
                    <i class='material-icons prefix md-prefix'>date_range</i>
                    <input id='dari_tanggal' type='text' name='dari_tanggal' required>
                    <label for='dari_tanggal'>Dari Tanggal</label>
                </div>
                <div class='input-field col s3'>
                    <i class='material-icons prefix md-prefix'>date_range</i>
                    <input id='sampai_tanggal' type='text' name='sampai_tanggal' required>
                    <label for='sampai_tanggal'>Sampai Tanggal</label>
                </div>
                <div class='input-field col s3'>
                    <i class='material-icons prefix md-prefix'>filter_list</i>
                    <select class='browser-default' name='jenis_surat' onchange='if(this.value) window.location.href=this.value;' style='margin-top: 10px;'>
                        <option value='?page=admin&act=ask&tipe=all'>Semua Jenis</option>
                        <option value='?page=admin&act=ask&tipe=umum'>Surat Keluar</option>
                        <option value='?page=admin&act=ask_nd' selected>Nota Dinas</option>
                        <option value='?page=admin&act=ask_ph'>Produk Hukum</option>
                        <option value='?page=admin&act=ask_keu'>Keuangan</option>
                    </select>
                </div>
                <div class='col s3'>
                    <button type='submit' name='submit' class='btn-large blue waves-effect waves-light'> TAMPILKAN <i class='material-icons'>visibility</i></button>
                </div>
            </form>
        </div>";

        // Header info rentang tanggal
        $y = substr($dari_tanggal,0,4); $m = substr($dari_tanggal,5,2); $d = substr($dari_tanggal,8,2);
        $y2= substr($sampai_tanggal,0,4);$m2= substr($sampai_tanggal,5,2);$d2= substr($sampai_tanggal,8,2);
        $BULAN = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
        $nm = isset($BULAN[$m])?$BULAN[$m]:$m; $nm2 = isset($BULAN[$m2])?$BULAN[$m2]:$m2;
        echo "<div class='row agenda'>
                <div class='col s10'>
                    <h5 class='hid'>AGENDA ".strtoupper($cleanLabel)."</h5>
                    <p class='warna agenda'>Agenda $cleanLabel dari tanggal <strong>$d $nm $y</strong> sampai dengan tanggal <strong>$d2 $nm2 $y2</strong></p>
                </div>
                <div class='col s2'>
                    <button type='submit' onClick='window.print()' class='btn-large deep-orange waves-effect waves-light right'>CETAK <i class='material-icons'>print</i></button>
                </div>
            </div>
            <div id='colres' class='warna cetak'>
                <table class='bordered' id='tbl' width='100%'>
                    <thead class='blue lighten-4'>
                        <tr>
                            <th width='3%'>No Agenda</th>
                            <th width='5%'>Kode</th>
                            <th width='21%'>Perihal</th>
                            <th width='18%'>Tujuan Surat</th>
                            <th width='15%'>Nomor Surat</th>
                            <th width='15%'>Tanggal Surat</th>
                            <th width='12%'>Pengelola</th>
                        </tr>
                    </thead>
                    <tbody><tr>";

        if ($query && mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_array($query)) {
                $y = substr($row['tgl_surat'],0,4); $m = substr($row['tgl_surat'],5,2); $d = substr($row['tgl_surat'],8,2);
                $nm = isset($BULAN[$m])?$BULAN[$m]:$m;
                $id_user = (int)$row['id_user'];
                $uName = '';
                $qU = mysqli_query($config, "SELECT username FROM tbl_user WHERE id_user='$id_user'");
                if ($qU) { list($uName) = mysqli_fetch_array($qU); }
                echo '<td>'.$row['no_agenda'].'</td>';
                echo '<td>'.$row['kode'].'</td>';
                echo '<td>'.$row['perihal'].'</td>';
                echo '<td>'.$row['tujuan'].'</td>';
                echo '<td>'.$row['no_surat'].'</td>';
                echo '<td>'.$d.' '.$nm.' '.$y.'</td>';
                echo '<td>'.$uName.'</td>';
                echo '</tr></tbody>';
            }
        } else {
            echo "<tr><td colspan='9'><center><p class='add'>Tidak ada agenda surat</p></center></td></tr>";
        }
        echo "</table></div><div class='jarak2'></div>";
    } else {
        echo "
        <div class='row'>
            <div class='col s12'>
                <div class='z-depth-1'>
                    <nav class='secondary-nav'>
                        <div class='nav-wrapper blue-grey darken-1'>
                            <div class='col 12'>
                                <ul class='left'>
                                    <li class='waves-effect waves-light'><a href='?page=admin&act=$slugAct' class='judul'><i class='material-icons'>print</i> $title<a></li>
                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
        <div class='row jarak-form black-text'>
            <form class='col s12' method='post' action=''>
                <div class='input-field col s3'>
                    <i class='material-icons prefix md-prefix'>date_range</i>
                    <input id='dari_tanggal' type='text' name='dari_tanggal' required>
                    <label for='dari_tanggal'>Dari Tanggal</label>
                </div>
                <div class='input-field col s3'>
                    <i class='material-icons prefix md-prefix'>date_range</i>
                    <input id='sampai_tanggal' type='text' name='sampai_tanggal' required>
                    <label for='sampai_tanggal'>Sampai Tanggal</label>
                </div>
                <div class='input-field col s3'>
                    <i class='material-icons prefix md-prefix'>filter_list</i>
                    <select class='browser-default' name='jenis_surat' onchange='if(this.value) window.location.href=this.value;' style='margin-top: 10px;'>
                        <option value='?page=admin&act=ask&tipe=all'>Semua Jenis</option>
                        <option value='?page=admin&act=ask&tipe=umum'>Surat Keluar</option>
                        <option value='?page=admin&act=ask_nd' selected>Nota Dinas</option>
                        <option value='?page=admin&act=ask_ph'>Produk Hukum</option>
                        <option value='?page=admin&act=ask_keu'>Keuangan</option>
                    </select>
                </div>
                <div class='col s3'>
                    <button type='submit' name='submit' class='btn-large blue waves-effect waves-light'> TAMPILKAN <i class='material-icons'>visibility</i></button>
                </div>
            </form>
        </div>
        <div class='jarak'></div>";
    }
}

ask_nd_render($config, 'Cetak Agenda Nota Dinas', 'ask_nd');

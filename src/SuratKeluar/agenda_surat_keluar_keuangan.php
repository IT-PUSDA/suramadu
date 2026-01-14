<?php
// Halaman Cetak Agenda Keuangan (berbasis agenda_surat_keluar.php)
if (empty($_SESSION['admin'])) { $_SESSION['err']='<center>Anda harus login terlebih dahulu!</center>'; header('Location: ./'); die(); }

echo '<style type="text/css">.hidd{display:none}@media print{body{font-size:12px!important;color:#212121}.disp{text-align:center;margin:-.5rem 0}.hidd{display:block}.logodisp{float:left;position:relative;width:80px;height:80px;margin:0 0 0 1.2rem}#nama{font-size:20px!important;text-transform:uppercase;font-weight:bold;margin:-2.5rem 0 -3.7rem 0}.up{font-size:17px!important;font-weight:normal;text-transform:uppercase}.status{font-size:17px!important;font-weight:normal;margin-bottom:-.1rem}#alamat{margin-top:-15px;font-size:13px}.separator{border-bottom:2px solid #616161;margin:1rem 0 -.7rem}}</style>';

if (isset($_REQUEST['submit'])) {
    $dari_tanggal = $_REQUEST['dari_tanggal'];
    $sampai_tanggal = $_REQUEST['sampai_tanggal'];
    if ($dari_tanggal === '' || $sampai_tanggal === '') { header("Location: ./admin.php?page=admin&act=ask_keu"); die(); }

    $whereTanggal = "tgl_surat BETWEEN '" . mysqli_real_escape_string($config, $dari_tanggal) . "' AND '" . mysqli_real_escape_string($config, $sampai_tanggal) . "'";
    $whereJenis = "jenis='keuangan'";
    if ((int)$_SESSION['admin'] === 4) {
        $id_user = (int)$_SESSION['id_user'];
        $sqlUser = "id_user='$id_user'";
        if (isset($_SESSION['kode_bidang']) && !empty($_SESSION['kode_bidang'])) {
            $kb = mysqli_real_escape_string($config, $_SESSION['kode_bidang']);
            $sqlUser = "(bidang='$kb' OR id_user='$id_user')";
        }
        $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE $whereJenis AND $sqlUser AND $whereTanggal");
    } else if ((int)$_SESSION['admin'] === 3) {
        if (!function_exists('operator_access_info')) { @include_once __DIR__ . '/../include/operator_access.php'; }
        if (!function_exists('resolve_bidang_code_from_session')) { @include_once __DIR__ . '/../include/bidang_mapping.php'; }
        
        $opInfo = operator_access_info($config, $_SESSION);
        $operator_allowed_ids = $opInfo['allowed_ids'];
        if (empty($operator_allowed_ids)) { $operator_allowed_ids[] = (int)$_SESSION['id_user']; }
        $idList = implode(',', array_map('intval', $operator_allowed_ids));
        
        $myBidangCode = resolve_bidang_code_from_session(); 
        
        $filterExpression = "id_user IN ($idList)";
        if ($myBidangCode) {
            $escC = mysqli_real_escape_string($config, $myBidangCode);
            $filterExpression .= " OR bidang='$escC'";
            if ($escC === '104.6.05') {
                 $filterExpression .= " OR bidang LIKE '%BOJONEGORO%'";
            }
        }
        
        $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE $whereJenis AND ($filterExpression) AND $whereTanggal");
    } else {
        $query = mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE $whereJenis AND $whereTanggal");
    }

    echo "<div class='row'><div class='col s12'><div class='z-depth-1'><nav class='secondary-nav'><div class='nav-wrapper blue-grey darken-1'><div class='col 12'><ul class='left'><li class='waves-effect waves-light'><a href='?page=admin&act=ask_keu' class='judul'><i class='material-icons'>print</i> Cetak Agenda Keuangan<a></li></ul></div></div></nav></div></div></div>";
    echo "<div class='row jarak-form black-text'><form class='col s12' method='post' action=''>
            <div class='input-field col s3'><i class='material-icons prefix md-prefix'>date_range</i><input id='dari_tanggal' type='text' name='dari_tanggal' required><label for='dari_tanggal'>Dari Tanggal</label></div>
            <div class='input-field col s3'><i class='material-icons prefix md-prefix'>date_range</i><input id='sampai_tanggal' type='text' name='sampai_tanggal' required><label for='sampai_tanggal'>Sampai Tanggal</label></div>
            <div class='input-field col s3'><i class='material-icons prefix md-prefix'>filter_list</i>
                <select class='browser-default' name='jenis_surat' onchange='if(this.value) window.location.href=this.value;' style='margin-top:10px;'>
                    <option value='?page=admin&act=ask&tipe=all'>Semua Jenis</option>
                    <option value='?page=admin&act=ask&tipe=umum'>Surat Keluar</option>
                    <option value='?page=admin&act=ask_nd'>Nota Dinas</option>
                    <option value='?page=admin&act=ask_ph'>Produk Hukum</option>
                    <option value='?page=admin&act=ask_keu' selected>Keuangan</option>
                </select>
            </div>
            <div class='col s3'><button type='submit' name='submit' class='btn-large blue waves-effect waves-light'> TAMPILKAN <i class='material-icons'>visibility</i></button></div>
          </form></div>";

    $y=substr($dari_tanggal,0,4);$m=substr($dari_tanggal,5,2);$d=substr($dari_tanggal,8,2);$y2=substr($sampai_tanggal,0,4);$m2=substr($sampai_tanggal,5,2);$d2=substr($sampai_tanggal,8,2);
    $B=['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember']; $nm=$B[$m]??$m; $nm2=$B[$m2]??$m2;
    echo "<div class='row agenda'><div class='col s10'><h5 class='hid'>AGENDA KEUANGAN</h5><p class='warna agenda'>Keuangan dari tanggal <strong>$d $nm $y</strong> sampai dengan tanggal <strong>$d2 $nm2 $y2</strong></p></div><div class='col s2'><button type='submit' onClick='window.print()' class='btn-large deep-orange waves-effect waves-light right'>CETAK <i class='material-icons'>print</i></button></div></div>";

    echo "<div id='colres' class='warna cetak'><table class='bordered' id='tbl' width='100%'><thead class='blue lighten-4'><tr><th width='3%'>No Agenda</th><th width='5%'>Kode</th><th width='21%'>Perihal</th><th width='18%'>Tujuan Surat</th><th width='15%'>Nomor Surat</th><th width='15%'>Tanggal Surat</th><th width='12%'>Pengelola</th></tr></thead><tbody><tr>";
    if($query && mysqli_num_rows($query)>0){
        while($row=mysqli_fetch_array($query)){
            $y=substr($row['tgl_surat'],0,4);$m=substr($row['tgl_surat'],5,2);$d=substr($row['tgl_surat'],8,2);$nm=$B[$m]??$m;
            $id_user=(int)$row['id_user']; $uName=''; $qU=mysqli_query($config, "SELECT username FROM tbl_user WHERE id_user='$id_user'"); if($qU){ list($uName)=mysqli_fetch_array($qU); }
            echo '<td>'.$row['no_agenda'].'</td><td>'.$row['kode'].'</td><td>'.$row['perihal'].'</td><td>'.$row['tujuan'].'</td><td>'.$row['no_surat'].'</td><td>'.$d.' '.$nm.' '.$y.'</td><td>'.$uName.'</td></tr></tbody>';
        }
    } else { echo "<tr><td colspan='9'><center><p class='add'>Tidak ada agenda surat</p></center></td></tr>"; }
    echo "</table></div><div class='jarak2'></div>";
} else {
    echo "<div class='row'><div class='col s12'><div class='z-depth-1'><nav class='secondary-nav'><div class='nav-wrapper blue-grey darken-1'><div class='col 12'><ul class='left'><li class='waves-effect waves-light'><a href='?page=admin&act=ask_keu' class='judul'><i class='material-icons'>print</i> Cetak Agenda Keuangan<a></li></ul></div></div></nav></div></div></div>";
    echo "<div class='row jarak-form black-text'><form class='col s12' method='post' action=''>
            <div class='input-field col s3'><i class='material-icons prefix md-prefix'>date_range</i><input id='dari_tanggal' type='text' name='dari_tanggal' required><label for='dari_tanggal'>Dari Tanggal</label></div>
            <div class='input-field col s3'><i class='material-icons prefix md-prefix'>date_range</i><input id='sampai_tanggal' type='text' name='sampai_tanggal' required><label for='sampai_tanggal'>Sampai Tanggal</label></div>
            <div class='input-field col s3'><i class='material-icons prefix md-prefix'>filter_list</i>
                <select class='browser-default' name='jenis_surat' onchange='if(this.value) window.location.href=this.value;' style='margin-top:10px;'>
                    <option value='?page=admin&act=ask&tipe=all'>Semua Jenis</option>
                    <option value='?page=admin&act=ask&tipe=umum'>Surat Keluar</option>
                    <option value='?page=admin&act=ask_nd'>Nota Dinas</option>
                    <option value='?page=admin&act=ask_ph'>Produk Hukum</option>
                    <option value='?page=admin&act=ask_keu' selected>Keuangan</option>
                </select>
            </div>
            <div class='col s3'><button type='submit' name='submit' class='btn-large blue waves-effect waves-light'> TAMPILKAN <i class='material-icons'>visibility</i></button></div>
          </form></div><div class='jarak'></div>";
}

<?php
    //session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        // gunakan parameter uact (user action) agar tidak bentrok dengan act=sett pada router induk
        $uact = isset($_REQUEST['uact']) ? $_REQUEST['uact'] : '';
        if(!empty($uact)){
            switch ($uact) {
                case 'add':
                    include BASE_PATH . "/src/User/tambah_user.php";
                    break;
                case 'edit':
                    include BASE_PATH . "/src/User/edit_tipe_user.php";
                    break;
                case 'del':
                    include BASE_PATH . "/src/User/hapus_user.php";
                    break;
                case 'reset':
                    // Hanya Super Admin yang boleh reset password
                    if($_SESSION['admin'] != 1){
                        echo '<script>alert("ERROR! Anda tidak memiliki hak reset password."); window.location.href="index.php?page=admin&act=sett&sub=usr";</script>';
                        break;
                    }
                    $id_user = isset($_GET['id_user']) ? intval($_GET['id_user']) : 0;
                    if($id_user <= 0){
                        echo '<script>alert("Parameter tidak valid."); window.location.href="index.php?page=admin&act=sett&sub=usr";</script>';
                        break;
                    }

                    // Ambil info user (opsional, untuk tampilan)
                    $uinfo = mysqli_fetch_assoc(mysqli_query($config, "SELECT username,nama FROM tbl_user WHERE id_user='".$id_user."'"));

                    // Tampilkan form input password baru
                    if(!isset($_POST['do_reset'])){
                        $errHtml = '';
                        if(isset($_SESSION['errQ'])){
                            $msg = $_SESSION['errQ'];
                            unset($_SESSION['errQ']);
                            $errHtml = '<div class="card red lighten-5" style="margin-bottom:12px;"><div class="card-content"><span class="red-text"><i class="material-icons left">error_outline</i>'.$msg.'</span></div></div>';
                        }
                        $uname = $uinfo && isset($uinfo['username']) ? htmlspecialchars($uinfo['username']) : 'pengguna';
                        echo '<div class="row" style="min-height:70vh; display:flex; align-items:center; justify-content:center;"><div class="col s12 m8 l6"><div class="card" style="margin:0 auto;">
                                <div class="card-content">
                                    <span class="card-title">Reset Password Pengguna</span>
                                    <p>Atur password baru untuk pengguna: <strong>'.$uname.'</strong></p>'
                                    .$errHtml.
                                    '<form method="post" action="index.php?page=admin&act=sett&sub=usr&uact=reset&id_user='.$id_user.'">
                                        <div class="input-field">
                                            <i class="material-icons prefix">lock</i>
                                            <input id="password" type="password" name="password" minlength="6" required />
                                            <label for="password">Password baru (min. 6 karakter)</label>
                                        </div>
                                        <div class="input-field">
                                            <i class="material-icons prefix">lock_outline</i>
                                            <input id="password2" type="password" name="password2" minlength="6" required />
                                            <label for="password2">Konfirmasi password baru</label>
                                        </div>
                                        <input type="hidden" name="do_reset" value="1" />
                                        <div class="center-align" style="margin-top:12px;">
                                            <button class="btn red" type="submit"><i class="material-icons left">refresh</i> Reset</button>
                                            <a class="btn grey" href="index.php?page=admin&act=sett&sub=usr" style="margin-left:8px;"><i class="material-icons left">arrow_back</i> Batal</a>
                                        </div>
                                    </form>
                                </div></div></div></div>';
                        break;
                    }

                    // Proses set password baru
                    $pwd = isset($_POST['password']) ? trim($_POST['password']) : '';
                    $pwd2 = isset($_POST['password2']) ? trim($_POST['password2']) : '';

                    if($pwd === '' || $pwd2 === ''){
                        $_SESSION['errQ'] = 'ERROR! Password tidak boleh kosong';
                        header('Location: index.php?page=admin&act=sett&sub=usr&uact=reset&id_user='.$id_user);
                        die();
                    }
                    if(strlen($pwd) < 6){
                        $_SESSION['errQ'] = 'ERROR! Panjang password minimal 6 karakter';
                        header('Location: index.php?page=admin&act=sett&sub=usr&uact=reset&id_user='.$id_user);
                        die();
                    }
                    if($pwd !== $pwd2){
                        $_SESSION['errQ'] = 'ERROR! Konfirmasi password tidak sama';
                        header('Location: index.php?page=admin&act=sett&sub=usr&uact=reset&id_user='.$id_user);
                        die();
                    }

                    // Simpan hash MD5 agar sesuai skema login saat ini
                    $hash = md5($pwd);
                    $res = mysqli_query($config, "UPDATE tbl_user SET password='".$hash."' WHERE id_user='".$id_user."'");
                    if($res){
                        $_SESSION['succEdit'] = 'SUKSES! Password pengguna berhasil diubah';
                    } else {
                        $_SESSION['errQ'] = 'ERROR! Gagal mengubah password';
                    }
                    header("Location: index.php?page=admin&act=sett&sub=usr");
                    die();
                default:
                    // fallback ke daftar
                    break;
            }
        } else {

            // pagging & search
            $limit = 10;
            $pg = @$_GET['pg'];
            $q = isset($_GET['q']) ? trim(mysqli_real_escape_string($config, $_GET['q'])) : '';
                if(empty($pg)){
                    $curr = 0;
                    $pg = 1;
                } else {
                    $curr = ($pg - 1) * $limit;
                }
				if($_SESSION['admin']==1){
					$where = '';
					if($q !== ''){ $where = "WHERE username LIKE '%$q%' OR nama LIKE '%$q%'"; }
					$query = mysqli_query($config, "SELECT * FROM tbl_user $where ORDER BY id_user ASC LIMIT $curr, $limit");
					$countRes = mysqli_query($config, "SELECT COUNT(*) AS jml FROM tbl_user $where");
					$crow = mysqli_fetch_assoc($countRes);
					$cdata = (int)$crow['jml'];
				} else {
					$id_user=$_SESSION['id_user'];
					$query = mysqli_query($config, "SELECT * FROM tbl_user WHERE id_user='$id_user' LIMIT $curr, $limit");
					$countRes = mysqli_query($config, "SELECT COUNT(*) AS jml FROM tbl_user WHERE id_user='$id_user'");
					$crow = mysqli_fetch_assoc($countRes);
					$cdata = (int)$crow['jml'];
					}
                echo '<!-- Row Start -->
                    <div class="row">
                        <!-- Secondary Nav START -->
                        <div class="col s12">
                            <div class="z-depth-1">
                                <nav class="secondary-nav">
                                    <div class="nav-wrapper blue-grey darken-1">
                                        <div class="col m12">
                                            <ul class="left">
                                                <li class="waves-effect waves-light hide-on-small-only"><a href="index.php?page=admin&act=sett&sub=usr" class="judul"><i class="material-icons">people</i> Manajemen User</a></li>
                                                <li class="waves-effect waves-light">';
                                                if($_SESSION['admin']==1){
                                                    echo '<a href="index.php?page=admin&act=sett&sub=usr&uact=add"><i class="material-icons md-24">person_add</i> Tambah User</a>';
                                                    }

                                                echo '</li></ul>';
                                                // Search form (GET)
                        echo '<form method="get" class="right" style="margin:8px 12px 0 0;">'
                                                    .'<input type="hidden" name="page" value="admin"/>'
                                                    .'<input type="hidden" name="act" value="sett"/>'
                                                    .'<input type="hidden" name="sub" value="usr"/>'
                                                    .'<div class="input-field" style="margin:0;">'
                                                        .'<input id="search" type="search" name="q" value="'.htmlspecialchars($q,ENT_QUOTES).'" placeholder="Cari username/nama" />'
                                                        .'<label class="label-icon" for="search"><i class="material-icons">search</i></label>'
                            .'<a href="index.php?page=admin&act=sett&sub=usr" class="btn-flat white-text" title="Bersihkan pencarian"><i class="material-icons">close</i></a>'
                                                    .'</div>'
                                                .'</form>';
                                    echo '    </div>
                                    </div>
                                </nav>
                            </div>
                        </div>
                        <!-- Secondary Nav END -->
                    </div>
                    <!-- Row END -->';

                    if(isset($_SESSION['succAdd'])){
                        $succAdd = $_SESSION['succAdd'];
                        echo '<div id="alert-message" class="row">
                                <div class="col m12">
                                    <div class="card green lighten-5">
                                        <div class="card-content notif">
                                            <span class="card-title green-text"><i class="material-icons md-36">done</i> '.$succAdd.'</span>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        unset($_SESSION['succAdd']);
                    }
                    if(isset($_SESSION['succEdit'])){
                        $succEdit = $_SESSION['succEdit'];
                        echo '<div id="alert-message" class="row">
                                <div class="col m12">
                                    <div class="card green lighten-5">
                                        <div class="card-content notif">
                                            <span class="card-title green-text"><i class="material-icons md-36">done</i> '.$succEdit.'</span>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        unset($_SESSION['succEdit']);
                    }
                    if(isset($_SESSION['succDel'])){
                        $succDel = $_SESSION['succDel'];
                        echo '<div id="alert-message" class="row">
                                <div class="col m12">
                                    <div class="card green lighten-5">
                                        <div class="card-content notif">
                                            <span class="card-title green-text"><i class="material-icons md-36">done</i> '.$succDel.'</span>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        unset($_SESSION['succDel']);
                    }

                echo '
                    <!-- Row form Start -->
                    <div class="row jarak-form">

                        <div class="col s12" id="colres">
                            <!-- Table START -->
                            <table class="highlight responsive-table" id="tbl">
                                <thead class="blue lighten-4" id="head">
                                    <tr>
                                        <th style="width:8%">No</th>
                                        <th style="width:23%">Username</th>
                                        <th style="width:30%">Nama<br/>NIP</th>
                                        <th style="width:22%">Level</th>
                                        <th style="width:17%">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody>';

                                if(mysqli_num_rows($query) > 0){
                                    $no = 1;
                                    while($row = mysqli_fetch_array($query)){
                                    echo '<tr>';
                                    echo '<td>'.$no++.'</td>';

                                    $roleLabel = 'Bidang';
                                    $roleClass = 'blue-grey';
                                    if($row['admin'] == 1){
                                        $roleLabel = 'Admin';
                                        $roleClass = 'red';
                                    } elseif($row['admin'] == 2){
                                        $roleLabel = 'Pimpinan';
                                        $roleClass = 'purple';
                                    } elseif($row['admin'] == 3){
                                        $roleLabel = 'Operator';
                                        $roleClass = 'teal';
                                    }
                                    echo '<td>'.$row['username'].'</td>
                                            <td>'.$row['nama'].'<br/>'.$row['nip'].'</td>
                                            <td><span class="chip '.$roleClass.' white-text">'.$roleLabel.'</span></td>
                                            <td>';

                                    if($_SESSION['username'] == $row['username']){
                                        echo'<a class="btn small blue waves-effect waves-light" href="index.php?page=admin&act=sett&sub=usr&uact=edit&id_user='.$row['id_user'].'">
                                                 <i class="material-icons">edit</i> EDIT</a>';
                                    } else {

                                        if($row['id_user'] == 1){
                                            echo'<a class="btn small blue waves-effect waves-light" href="index.php?page=admin&act=sett&sub=usr&uact=edit&id_user='.$row['id_user'].'">
                                                 <i class="material-icons">edit</i> EDIT</a>';
                                        } else {
                                                        echo '<a class="btn small blue waves-effect waves-light" href="index.php?page=admin&act=sett&sub=usr&uact=edit&id_user='.$row['id_user'].'" title="Edit User">
                                                                 <i class="material-icons">edit</i></a> ';
                                          if($_SESSION['admin']==1){
                                                             echo '<a class="btn small orange waves-effect waves-light" href="index.php?page=admin&act=sett&sub=usr&uact=reset&id_user='.$row['id_user'].'" title="Reset Password"><i class="material-icons">refresh</i></a> ';
                                                             echo '<a class="btn small deep-orange waves-effect waves-light" href="index.php?page=admin&act=sett&sub=usr&uact=del&id_user='.$row['id_user'].'" title="Hapus User"><i class="material-icons">delete</i></a>';
                                          } else {
                                                             echo '<a class="btn small deep-orange waves-effect waves-light" href="index.php?page=admin&act=sett&sub=usr&uact=del&id_user='.$row['id_user'].'" title="Hapus User"><i class="material-icons">delete</i></a>';
                                          }
                                        }
                                    }
                                    echo '</td></tr>';
                                    }
                                } else {
                                echo '<tr><td colspan="5"><center><p class="add">Tidak ada data untuk ditampilkan'.($q!==''?' untuk kata kunci \''.$q.'\'':'').'</p></center></td></tr>';
                                }
                      echo '</tbody></table>
                            <!-- Table END -->
                        </div>

                    </div>
                    <!-- Row form END -->';

                    $cpg = ceil($cdata/$limit);

                    echo '<!-- Pagination START -->
                          <ul class="pagination">';

                    if($cdata > $limit){

                        if($pg > 1){
                            $prev = $pg - 1;
                    echo '<li><a href="index.php?page=admin&act=sett&sub=usr&pg=1'.($q!==''?'&q='.urlencode($q):'').'"><i class="material-icons md-48">first_page</i></a></li>
                        <li><a href="index.php?page=admin&act=sett&sub=usr&pg='.$prev.($q!==''?'&q='.urlencode($q):'').'"><i class="material-icons md-48">chevron_left</i></a></li>';
                        } else {
                            echo '<li class="disabled"><a href=""><i class="material-icons md-48">first_page</i></a></li>
                                  <li class="disabled"><a href=""><i class="material-icons md-48">chevron_left</i></a></li>';
                        }

                        //perulangan pagging
                        for($i=1; $i <= $cpg; $i++)
                            if($i != $pg){
                      echo '<li class="waves-effect waves-dark"><a href="index.php?page=admin&act=sett&sub=usr&pg='.$i.($q!==''?'&q='.urlencode($q):'').'"> '.$i.' </a></li>';
                            } else {
                      echo '<li class="active waves-effect waves-dark"><a href="index.php?page=admin&act=sett&sub=usr&pg='.$i.($q!==''?'&q='.urlencode($q):'').'"> '.$i.' </a></li>';
                            }

                        //last and next pagging
                        if($pg < $cpg){
                            $next = $pg + 1;
                    echo '<li><a href="index.php?page=admin&act=sett&sub=usr&pg='.$next.($q!==''?'&q='.urlencode($q):'').'"><i class="material-icons md-48">chevron_right</i></a></li>
                        <li><a href="index.php?page=admin&act=sett&sub=usr&pg='.$cpg.($q!==''?'&q='.urlencode($q):'').'"><i class="material-icons md-48">last_page</i></a></li>';
                        } else {
                            echo '<li class="disabled"><a href=""><i class="material-icons md-48">chevron_right</i></a></li>
                                  <li class="disabled"><a href=""><i class="material-icons md-48">last_page</i></a></li>';
                        }
                            echo ' </ul>
                                   <!-- Pagination END -->';
                    } else {
                        echo '';
                    }
              // small summary
              echo '<div class="right" style="margin:8px 0 16px 0; font-size: 0.95rem;">Total Pengguna: <strong>'.$cdata.'</strong></div>';
                }
            }
?>

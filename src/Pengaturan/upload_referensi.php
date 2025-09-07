<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        if($_SESSION['admin'] != 1 AND $_SESSION['admin'] != 2){
            echo '<script language="javascript">
                    window.alert("ERROR! Anda tidak memiliki hak akses untuk membuka halaman ini");
                    window.location.href="./logout.php";
                  </script>';
        } else {

            // Paksa koneksi MySQL ke utf8mb4 agar karakter khusus tidak menyebabkan "Incorrect string value"
            if (isset($config) && function_exists('mysqli_set_charset')) {
                @mysqli_set_charset($config, 'utf8mb4');
            }

            // Import dari file CSV yang ada di folder asset (tanpa upload)
            if(isset($_POST['import_asset'])){
                $assetName = isset($_POST['selected_asset']) ? basename($_POST['selected_asset']) : '';
                if($assetName === '' || stripos($assetName, '.csv') === false){
                    $_SESSION['errEmpty'] = 'ERROR! Pilih file CSV dari folder asset terlebih dahulu';
                    header("Location: ./index.php?page=admin&act=ref&sub=imp");
                    die();
                }
                $path = BASE_PATH . '/asset/' . $assetName;
                if(!file_exists($path)){
                    $_SESSION['errUpload'] = 'ERROR! File asset tidak ditemukan: ' . htmlspecialchars($assetName);
                    header("Location: ./index.php?page=admin&act=ref&sub=imp");
                    die();
                }

                // Jika tidak mencentang cek_asset, kosongkan tabel lebih dulu
                $keepExisting = isset($_POST['cek_asset']);
                if(!$keepExisting){
                    mysqli_query($config, "TRUNCATE TABLE tbl_klasifikasi");
                }

                $handle = fopen($path, 'r');
                if(!$handle){
                    $_SESSION['errUpload'] = 'ERROR! Gagal membuka file asset';
                    header("Location: ./index.php?page=admin&act=ref&sub=imp");
                    die();
                }

                $id_user = $_SESSION['id_user'];
                $imported = 0;
                // Deteksi delimiter dari baris pertama
                $firstLine = fgets($handle);
                $delims = [",",";","\t"];
                $bestDelim = ','; $bestCount=-1;
                foreach($delims as $d){ $c = substr_count($firstLine, $d); if($c>$bestCount){ $bestCount=$c; $bestDelim=$d; } }
                rewind($handle);

                $row = 0;
                while(($data = fgetcsv($handle, 0, $bestDelim)) !== FALSE){
                    if(!$data){ continue; }
                    $row++;
                    // Trim semua kolom dan hilangkan UTF-8 BOM jika ada di awal sel
                    $data = array_map(function($v){
                        $v = trim((string)$v);
                        if (substr($v, 0, 3) === "\xEF\xBB\xBF") { $v = substr($v, 3); }
                        return $v;
                    }, $data);
                    $count = count($data);
                    if($count < 3){ continue; }

                    // Skip header (jika ada)
                    $first = strtolower($data[0]); $second = isset($data[1])? strtolower($data[1]) : '';
                    if($row===1 && (strpos($first,'id')===0 || strpos($first,'kode')===0 || strpos($second,'kode')===0)){
                        continue;
                    }

                    // Tentukan mapping kolom (3: kode,nama,uraian | 4: id,kode,nama,uraian)
                    $hasId = ($count >= 4 && ctype_digit($data[0]));
                    $kode  = mysqli_real_escape_string($config, $hasId ? $data[1] : $data[0]);
                    $nama  = mysqli_real_escape_string($config, $hasId ? $data[2] : $data[1]);
                    $urai  = mysqli_real_escape_string($config, $hasId ? $data[3] : (isset($data[2]) ? $data[2] : ''));

                    if($kode === '' || $nama === ''){ continue; }

                    if($hasId){
                        $id = (int)$data[0];
                        mysqli_query($config, "INSERT INTO tbl_klasifikasi (id_klasifikasi, kode, nama, uraian, id_user) VALUES ($id, '$kode', '$nama', '$urai', '$id_user')");
                    } else {
                        mysqli_query($config, "INSERT INTO tbl_klasifikasi (id_klasifikasi, kode, nama, uraian, id_user) VALUES (NULL, '$kode', '$nama', '$urai', '$id_user')");
                    }
                    $imported++;
                }
                fclose($handle);

                $_SESSION['succUpload'] = 'SUKSES! ' . $imported . ' baris berhasil diimport dari asset';
                header("Location: ./index.php?page=admin&act=ref");
                die();
            }

            //proses upload file
            if(isset($_POST['submit'])){

                $file = $_FILES['file']['tmp_name'];

                if($file == ""){
                    $_SESSION['errEmpty'] = 'ERROR! Form File tidak boleh kosong';
                    header("Location: ./index.php?page=admin&act=ref&sub=imp");
                    die();
                } else {

                    $x = explode('.', $_FILES['file']['name']);
                    $eks = strtolower(end($x));

                    if($eks == 'csv'){

                        //jika tidak ingin menghapus data yang sudah ada
                        if(isset($_REQUEST['cek'])){

                            //upload file
                            if(is_uploaded_file($file)){
                                $_SESSION['succUpload'] = 'SUKSES! Data berhasil diimport';
                            } else {
                                $_SESSION['errUpload'] = 'ERROR! Proses upload data gagal';
                                header("Location: ./index.php?page=admin&act=ref&sub=imp");
                                die();
                            }

                            //membuka file csv
                            $handle = fopen($file, "r");
                            $id_user = $_SESSION['id_user'];

                            // Deteksi delimiter dari baris pertama
                            $firstLine = fgets($handle);
                            $delims = [",",";","\t"];
                            $bestDelim = ','; $bestCount = -1;
                            foreach($delims as $d){ $c = substr_count($firstLine, $d); if($c > $bestCount){ $bestCount = $c; $bestDelim = $d; } }
                            rewind($handle);

                            //parsing file csv
                            $row = 0; $imported=0;
                            while(($data = fgetcsv($handle, 0, $bestDelim)) !== FALSE){
                                if(!$data) { continue; }
                                $row++;
                                // Trim semua kolom & hilangkan UTF-8 BOM di awal jika ada
                                $data = array_map(function($v){
                                    $v = trim((string)$v);
                                    if (substr($v, 0, 3) === "\xEF\xBB\xBF") { $v = substr($v, 3); }
                                    return $v;
                                }, $data);
                                $cnt = count($data);
                                if($cnt < 3) { continue; }
                                // Header? skip jika ada kata Kode/Nama di awal
                                $first = strtolower($data[0]); $second = isset($data[1])? strtolower($data[1]) : '';
                                if($row === 1 && ((strpos($first,'id') === 0) || (strpos($first,'kode') === 0) || (strpos($second,'kode') === 0))){ continue; }

                                $hasId = ($cnt >= 4 && ctype_digit($data[0]));
                                $kode  = mysqli_real_escape_string($config, $hasId ? $data[1] : $data[0]);
                                $nama  = mysqli_real_escape_string($config, $hasId ? $data[2] : $data[1]);
                                $urai  = mysqli_real_escape_string($config, $hasId ? $data[3] : (isset($data[2]) ? $data[2] : ''));
                                if($kode === '' || $nama === '') { continue; }

                                if($hasId){
                                    $id = (int)$data[0];
                                    mysqli_query($config, "INSERT INTO tbl_klasifikasi (id_klasifikasi, kode, nama, uraian, id_user) VALUES ($id, '$kode', '$nama', '$urai', '$id_user')");
                                } else {
                                    mysqli_query($config, "INSERT INTO tbl_klasifikasi (id_klasifikasi, kode, nama, uraian, id_user) VALUES (NULL, '$kode', '$nama', '$urai', '$id_user')");
                                }
                                $imported++;
                            }
                            fclose($handle);
                            header("Location: ./index.php?page=admin&act=ref");
                            die();
                        } else {

                            //mengosongkan table klasifikasi
                            mysqli_query($config, "TRUNCATE TABLE tbl_klasifikasi");

                            //upload file
                            if(is_uploaded_file($file)){
                                $_SESSION['succUpload'] = 'SUKSES! Data berhasil diimport';
                            } else {
                                $_SESSION['errUpload'] = 'ERROR! Proses upload data gagal';
                                header("Location: ./index.php?page=admin&act=ref&sub=imp");
                                die();
                            }

                            //membuka file csv
                            $handle = fopen($file, "r");
                            $id_user = $_SESSION['id_user'];

                            // Deteksi delimiter
                            $firstLine = fgets($handle);
                            $delims = [",",";","\t"];
                            $bestDelim = ','; $bestCount = -1;
                            foreach($delims as $d){ $c = substr_count($firstLine, $d); if($c > $bestCount){ $bestCount = $c; $bestDelim = $d; } }
                            rewind($handle);

                            //parsing file csv
                            $row = 0; $imported=0;
                            while(($data = fgetcsv($handle, 0, $bestDelim)) !== FALSE){
                                if(!$data) { continue; }
                                $row++;
                                $data = array_map(function($v){
                                    $v = trim((string)$v);
                                    if (substr($v, 0, 3) === "\xEF\xBB\xBF") { $v = substr($v, 3); }
                                    return $v;
                                }, $data);
                                $cnt = count($data);
                                if($cnt < 3) { continue; }
                                $first = strtolower($data[0]); $second = isset($data[1])? strtolower($data[1]) : '';
                                if($row === 1 && ((strpos($first,'id') === 0) || (strpos($first,'kode') === 0) || (strpos($second,'kode') === 0))){ continue; }

                                $hasId = ($cnt >= 4 && ctype_digit($data[0]));
                                $kode  = mysqli_real_escape_string($config, $hasId ? $data[1] : $data[0]);
                                $nama  = mysqli_real_escape_string($config, $hasId ? $data[2] : $data[1]);
                                $urai  = mysqli_real_escape_string($config, $hasId ? $data[3] : (isset($data[2]) ? $data[2] : ''));
                                if($kode === '' || $nama === '') { continue; }

                                if($hasId){
                                    $id = (int)$data[0];
                                    mysqli_query($config, "INSERT INTO tbl_klasifikasi (id_klasifikasi, kode, nama, uraian, id_user) VALUES ($id, '$kode', '$nama', '$urai', '$id_user')");
                                } else {
                                    mysqli_query($config, "INSERT INTO tbl_klasifikasi (id_klasifikasi, kode, nama, uraian, id_user) VALUES (NULL, '$kode', '$nama', '$urai', '$id_user')");
                                }
                                $imported++;
                            }
                            fclose($handle);
                            header("Location: ./index.php?page=admin&act=ref");
                            die();
                        }

                    } else {
                        $_SESSION['errFormat'] = 'ERROR! Format file yang diperbolehkan hanya *.CSV';
                        header("Location: ./index.php?page=admin&act=ref&sub=imp");
                        die();
                    }
                }
            }

          echo '
                <!-- Row Start -->
                <div class="row">
                    <!-- Secondary Nav START -->
                    <div class="col s12">
                        <div class="z-depth-1">
                            <nav class="secondary-nav">
                                <div class="nav-wrapper blue-grey darken-1">
                                    <div class="col m12">
                                        <ul class="left">
                                            <li class="waves-effect waves-light"><a href="./index.php?page=admin&act=ref&sub=imp" class="judul"><i class="material-icons">bookmark</i> Import Referensi Surat</a></li>
                                            <li class="waves-effect waves-light"><a href="./index.php?page=admin&act=ref"><i class="material-icons">arrow_back</i> Kembali</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </nav>
                        </div>
                    </div>
                    <!-- Secondary Nav END -->
                </div>
                <!-- Row END -->';

                if(isset($_SESSION['errFormat'])){
                    $errFormat = $_SESSION['errFormat'];
                    echo '<div id="alert-message" class="row">
                            <div class="col m12">
                                <div class="card red lighten-5">
                                    <div class="card-content notif">
                                        <span class="card-title red-text"><i class="material-icons md-36">clear</i> '.$errFormat.'</span>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    unset($_SESSION['errFormat']);
                }
                if(isset($_SESSION['errUpload'])){
                    $errUpload = $_SESSION['errUpload'];
                    echo '<div id="alert-message" class="row">
                            <div class="col m12">
                                <div class="card red lighten-5">
                                    <div class="card-content notif">
                                        <span class="card-title red-text"><i class="material-icons md-36">clear</i> '.$errUpload.'</span>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    unset($_SESSION['errUpload']);
                }
                if(isset($_SESSION['errEmpty'])){
                    $errEmpty = $_SESSION['errEmpty'];
                    echo '<div id="alert-message" class="row">
                            <div class="col m12">
                                <div class="card red lighten-5">
                                    <div class="card-content notif">
                                        <span class="card-title red-text"><i class="material-icons md-36">clear</i> '.$errEmpty.'</span>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    unset($_SESSION['errEmpty']);
                }

                // Siapkan daftar file CSV yang ada di folder asset
                $assetOptions = '';
                if(defined('BASE_PATH')){
                    $assetFiles = glob(BASE_PATH . '/asset/*.csv');
                    if($assetFiles){
                        foreach($assetFiles as $af){
                            $bn = basename($af);
                            $assetOptions .= '<option value="'.$bn.'">'.$bn.'</option>';
                        }
                    }
                }

                echo '
                <!-- Row form Start -->
                <div class="row">
                    <div class="col m12">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title black-text">Import Referensi Kode Klasifikasi Surat</span>
                                <p class="kata">Silakan pilih file referensi kode klasifikasi berformat *.csv (file excel) lalu klik tombol <strong>"Import"</strong> untuk melakukan import file. Contoh format file csv bisa di download melalui link dibawah ini.</p><br/>';

                                // download file contoh format csv
                                if(isset($_REQUEST['download'])){

                                    $dir = "./asset/";
                                    $file = $dir."contoh_format.csv";

                                    if(file_exists($file)){
                                        header('Content-Description: File Transfer');
                                        header('Content-Type: application/octet-stream');
                                        header('Content-Disposition: attachment; filename="contoh_format.csv"');
                                        header('Content-Transfer-Encoding: binary');
                                        header('Expires: 0');
                                        header('Cache-Control: private');
                                        header('Pragma: private');
                                        header('Content-Length: ' . filesize($file));
                                        ob_clean();
                                        flush();
                                        readfile($file);
                                        exit;
                                    }
                                } echo '

                                <p>
                                    <form method="post" enctype="multipart/form-data" >
                                        <a href="./index.php?page=admin&act=ref&sub=imp&download" name="download" class="waves-effect waves-light blue-text"><i class="material-icons">file_download</i> <strong>DOWNLOAD CONTOH FORMAT FILE CSV</strong></a>
                                    </form>
                                </p><br/>

                                <p class="kata"><span class="red-text"><i class="material-icons">error_outline</i> <strong>PERINGATAN!</strong></span><br/>Secara default, data yang ada akan diganti dengan data yang baru. Jika tidak ingin menghapus data yang sudah ada, silakan centang checkbox <i class="material-icons">check_box_outline_blank</i> dibawah form file.</p>
                            </div>
                            <div class="card-action">
                                <form method="post" enctype="multipart/form-data">
                                    <div class="file-field input-field col m6 tooltipped" data-position="top" data-tooltip="Format file yang diperbolehkan hanya *.CSV">
                                        <div class="btn light-green darken-1">
                                            <span>File</span>
                                            <input type="file" name="file" accept=".csv" required>
                                        </div>
                                        <div class="file-path-wrapper">
                                            <input class="file-path validate" placeholder="Upload file csv referensi kode klasifikasi" type="text">
                                         </div>
                                    </div>&nbsp;&nbsp;&nbsp;&nbsp;
                                    <div class="col m12" style="margin-bottom: 25px;">
                                        <input type="checkbox" id="cek" name="cek">
                                        <label for="cek" class="kata" style="color: #444;">Centang jika tidak ingin menghapus data yang sudah ada</label>
                                    </div>
                                    <button type="submit" class="btn-large blue waves-effect waves-light" name="submit">IMPORT <i class="material-icons">file_upload</i></button>
                                </form>
                                <div class="divider" style="margin:18px 0;"></div>
                                <form method="post">
                                    <div class="row" style="margin-bottom:0;">
                                        <div class="input-field col m6">
                                            <select name="selected_asset" required>
                                                <option value="" disabled selected>Pilih file CSV dari folder asset</option>' . $assetOptions . '
                                            </select>
                                            <label>Pilih CSV di asset</label>
                                        </div>
                                        <div class="input-field col m6" style="margin-top:28px;">
                                            <input type="checkbox" id="cek_asset" name="cek_asset">
                                            <label for="cek_asset" class="kata" style="color:#444;">Centang jika tidak ingin menghapus data yang sudah ada</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn teal waves-effect waves-light" name="import_asset">IMPORT DARI ASSET <i class="material-icons">file_download</i></button>
                                    <p class="grey-text" style="margin-top:8px;">Format CSV yang didukung: tiga kolom (kode,nama,uraian) atau empat kolom (id,kode,nama,uraian). Baris header akan diabaikan.</p>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>';
            }
        }
?>

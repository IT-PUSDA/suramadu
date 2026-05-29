<?php
// Proses penyimpanan Surat Keluar - Nota Dinas
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/bidang_mapping.php';
require_once __DIR__ . '/../include/file_sequence.php';

@include_once __DIR__ . '/../Utils/GoogleDriveClient.php';

// DETEKSI ERROR UPLOAD / POST SIZE LIMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES)) {
        echo "<script>alert('ERROR FATAL: Data POST kosong! Ukuran file mungkin melebihi batas upload server (post_max_size). Coba file yang lebih kecil.'); window.history.back();</script>";
        die();
    }
}

if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header('Location: index.php');
    die();
}


if (isset($_REQUEST['submit1']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']))) {
    // FALLBACK: Kadang tombol submit1 tidak terdeteksi di server tertentu jika form terlalu besar, kita anggap submit jika POST & ada file
    if (empty($_REQUEST['kode']) || empty($_REQUEST['perihal']) || empty($_REQUEST['tujuan']) || empty($_REQUEST['tgl_surat']) || empty($_REQUEST['isi']) || empty($_REQUEST['nama_pembuat']) || empty($_REQUEST['pin'])) {
        $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
    header('Location: index.php?page=admin&act=tsk_nd&sub=add_nota_dinas');
        die();

    }

    $nkode = $_REQUEST['kode'];
    // $nkode = !empty($_REQUEST['kode']) ? $_REQUEST['kode'] : ''; 
    $perihal = $_REQUEST['perihal'];
    $tujuan = $_REQUEST['tujuan'];
    $tgl_surat = $_REQUEST['tgl_surat'];
    $isi = $_REQUEST['isi'];
    $id_user = $_SESSION['id_user'];
    $bidang_input = $_REQUEST['bidang'] ?? '';
    $bidang_resolved = (in_array((int)$_SESSION['admin'], [3,4], true)) ? resolve_bidang_code_from_session() : null;
    $bidang = ($bidang_input !== '' && $bidang_input !== null) ? $bidang_input : ($bidang_resolved ?? $bidang_input);
    $nama_pembuat = $_REQUEST['nama_pembuat'];

    $raw_pin = isset($_REQUEST['pin']) ? trim($_REQUEST['pin']) : '';
    if (!(ctype_digit($raw_pin) && strlen($raw_pin) === 6)) {
        $_SESSION['pink'] = 'PIN harus berupa tepat 6 digit angka';
    header('Location: index.php?page=admin&act=tsk_nd&sub=add_nota_dinas');
        die();
    }
    $pin = password_hash($raw_pin, PASSWORD_DEFAULT);

    // Nomor agenda: halaman-baris. Maks 100 baris per halaman. Reset setiap awal tahun dan dipisah per jenis bila kolom tersedia.
    $year = date('Y', strtotime($tgl_surat));
    $hasJenisForQuery = false; $resJenisQ = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
    if ($resJenisQ && mysqli_num_rows($resJenisQ) === 1) { $hasJenisForQuery = true; }
    $filterJenis = $hasJenisForQuery ? " AND jenis='nota_dinas'" : "";
    $uid = (int)$id_user;
    $qcount = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE YEAR(tgl_surat)='".$year."' AND id_user='".$uid."'".$filterJenis);
    $totalThisYear = 0; if ($qcount) { $rc = mysqli_fetch_assoc($qcount); $totalThisYear = (int)$rc['c']; }
    $next = $totalThisYear + 1; // urutan ke-n dalam tahun tsb
    $page = (int)ceil($next / 100);
    $line = (int)((($next - 1) % 100) + 1);
    $lineStr = ($line === 100) ? '100' : sprintf('%02d', $line);
    $no_agenda = sprintf('%02d', $page) . '-' . $lineStr; // contoh: 01-01 ... 01-100, 02-01, dst
    $no_agendak = sprintf('%02d', $page) . $lineStr;      // tanpa dash untuk keperluan no_surat

    $q1 = mysqli_query($config, "SELECT max(id_surat) as urut FROM tbl_surat_keluar");
    $data1 = mysqli_fetch_array($q1);
    $id_surat = ($data1['urut'] ?? 0) + 1;

    // safety: make sure no_surat uniqueness constraint exists
    if (function_exists('ensure_no_surat_unique_index')) {
        ensure_no_surat_unique_index($config);
    }

    $pos_code = get_sequence_code_with_sisipan($config, (int)$year, $bidang, 'nota_dinas', $tgl_surat);
    $no_surat = $nkode . '/' . $pos_code . '/' . $bidang . '/' . $year;



    // Cek duplikasi sekali lagi di dalam blok aman
    $cek = mysqli_query($config, "SELECT 1 FROM tbl_surat_keluar WHERE no_surat='$no_surat' LIMIT 1");
    if (mysqli_num_rows($cek) > 0) { $_SESSION['errDup']='Nomor Surat sudah terpakai, gunakan yang lain!'; header('Location: index.php?page=admin&act=tsk_nd&sub=add_nota_dinas'); die(); }


    // Pastikan kolom file_no tersedia
    $hasFileNo = ensure_file_no_column($config);


    // Upload file & simpan nomor file
    // KODE BARU: DETEKSI ERROR UPLOAD PHP LEBIH AWAL
    if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
         // Tidak ada file diupload
         // Logic asli mengharuskan file pdf, jadi kita reject
         // KECUALI jika edit mode dan file lama ada (tapi ini CREATE mode)
         // Jadi ini wajib
         // TAPI... block asli code di bawah menggunakan `if (!empty($name))`.
         // Mari kita ikuti block asli, namun tambahkan warning jika POST size ok tapi file gagal.
    } elseif (isset($_FILES['file']['error']) && $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = $_FILES['file']['error'];
        $msg = 'Unknown Error';
        switch ($uploadError) {
            case UPLOAD_ERR_INI_SIZE: $msg = 'Ukuran file melebihi upload_max_filesize di php.ini'; break;
            case UPLOAD_ERR_FORM_SIZE: $msg = 'Ukuran file melebihi MAX_FILE_SIZE di form HTML'; break;
            case UPLOAD_ERR_PARTIAL: $msg = 'File hanya terupload sebagian. Coba lagi.'; break;
            case UPLOAD_ERR_NO_TMP_DIR: $msg = 'Folder temporary server hilang. Hubungi Admin Server.'; break;
            case UPLOAD_ERR_CANT_WRITE: $msg = 'Gagal menulis file ke disk server.'; break;
            case UPLOAD_ERR_EXTENSION: $msg = 'Upload dihentikan oleh ekstensi PHP.'; break;
        }
        $_SESSION['errQ'] = 'ERROR UPLOAD: ' . $msg;
        header('Location: index.php?page=admin&act=tsk_nd&sub=add_nota_dinas');
        die();
    }

    $nfile = ''; $file_no = null;
    

    // REMOVED: if (!empty($file)) check that wrapped everything.
    // Instead we do flat logic: if no file, error. if file, process.
    if (!isset($_FILES['file']['name']) || empty($_FILES['file']['name'])) {
         $_SESSION['errEmpty'] = 'ERROR! File surat (PDF) wajib diupload.';
         header('Location: index.php?page=admin&act=tsk_nd&sub=add_nota_dinas');
         die();
    }
    
    // Now we know file is present
    $ekstensi = ['pdf'];
    $file = $_FILES['file']['name'];
        $x = explode('.', $file);
        $eks = strtolower(end($x));
        $ukuran = $_FILES['file']['size'];

        // KODE BARU: Support Google Drive
        $target_dir = BASE_PATH . "/upload/surat_keluar/";
        $max_size = 2097152; // 2MB
        if (in_array($eks, $ekstensi) === true) {
            if ($ukuran < $max_size) {
                $file_no = next_file_sequence_for_year($config, (int)$year);
                $label = format_file_sequence_label($file_no);
                $nfile = $label . " - " . $file;
                $labeledName = $nfile;
                $tmpPath = $_FILES['file']['tmp_name'];
                if (function_exists('is_gdrive_enabled') && is_gdrive_enabled()) {
                    try {
                        $gdc = new GoogleDriveClientSimple(GDRIVE_SERVICE_ACCOUNT_JSON);
                        $result = $gdc->uploadFile($tmpPath, $nfile, 'application/pdf', GDRIVE_PARENT_FOLDER_ID);
                        $nfile = 'gdrive:fileId=' . $result['fileId'];
                        if (!empty($result['webViewLink'])) { $nfile .= '|view=' . $result['webViewLink']; }
                        $nfile .= '|name=' . rawurlencode($labeledName);
                    } catch (Exception $e) {
                        if (!is_dir($target_dir)) { @mkdir($target_dir, 0775, true); }
                        if (!move_uploaded_file($tmpPath, $target_dir . $nfile)) {
                            $_SESSION['errQ'] = 'ERROR! Gagal mengupload file (lokal/gdrive). Detail: ' . $e->getMessage();
                            header('Location: index.php?page=admin&act=tsk_nd&sub=add_nota_dinas');
                            die();
                        }
                    }
                } else {
                    if (!is_dir($target_dir)) { @mkdir($target_dir, 0775, true); }
                    if (!move_uploaded_file($tmpPath, $target_dir . $nfile)) {
                        $_SESSION['errQ'] = 'ERROR! Gagal mengupload file.';
                        header('Location: index.php?page=admin&act=tsk_nd&sub=add_nota_dinas');
                        die();
                    }
                }
            } else { $_SESSION['errSize']='Ukuran file terlalu besar! Maks 2 MB.'; header('Location: index.php?page=admin&act=tsk_nd&sub=add_nota_dinas'); die(); }

        } else { $_SESSION['errFormat']='Format file yang diperbolehkan hanya *.PDF!'; header('Location: index.php?page=admin&act=tsk_nd&sub=add_nota_dinas'); die(); }
    
    // } // END IF !empty file (Removed this brace because we removed the if wrapper)

    // Tambahkan kolom jenis bila tersedia
    $hasJenis = false; $resJenis = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
    if ($resJenis && mysqli_num_rows($resJenis) === 1) { $hasJenis = true; }

    if ($hasJenis && $hasFileNo) {
        $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,file_no,id_user,bidang,nama_pembuat,pin,jenis)
                        VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile',".($file_no===null?"NULL":"'".intval($file_no)."'").",'$id_user','$bidang','$nama_pembuat','$pin','nota_dinas')");
    } elseif ($hasJenis) {
        $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,id_user,bidang,nama_pembuat,pin,jenis)
                        VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang','$nama_pembuat','$pin','nota_dinas')");
    } elseif ($hasFileNo) {
        $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,file_no,id_user,bidang,nama_pembuat,pin)
                        VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile',".($file_no===null?"NULL":"'".intval($file_no)."'").",'$id_user','$bidang','$nama_pembuat','$pin')");
    } else {
        $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,id_user,bidang,nama_pembuat,pin)
                        VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang','$nama_pembuat','$pin')");
    }
    if ($query) {
        $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
        header('Location: index.php?page=admin&act=tsk_nd');
        die();
    } else {
        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query: ' . mysqli_error($config);
        header('Location: index.php?page=admin&act=tsk_nd&sub=add_nota_dinas');
        die();
    }
}

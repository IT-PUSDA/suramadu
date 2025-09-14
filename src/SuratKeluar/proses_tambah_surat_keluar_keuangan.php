<?php
// Proses Surat Keluar - Keuangan
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/bidang_mapping.php';
require_once __DIR__ . '/../include/file_sequence.php';

if (empty($_SESSION['admin'])) { $_SESSION['err']='<center>Anda harus login terlebih dahulu!</center>'; header('Location: index.php'); die(); }

if (isset($_REQUEST['submit1'])) {
    if (empty($_REQUEST['kode']) || empty($_REQUEST['perihal']) || empty($_REQUEST['tujuan']) || empty($_REQUEST['tgl_surat']) || empty($_REQUEST['isi']) || empty($_REQUEST['nama_pembuat']) || empty($_REQUEST['pin'])) {
    $_SESSION['errEmpty']='ERROR! Semua form wajib diisi'; header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die();
    }

    $nkode = $_REQUEST['kode'];
    $perihal = $_REQUEST['perihal'];
    $tujuan = $_REQUEST['tujuan'];
    $tgl_surat = $_REQUEST['tgl_surat'];
    $isi = $_REQUEST['isi'];
    $id_user = $_SESSION['id_user'];
    $bidang_input = $_REQUEST['bidang'];
    $bidang = (in_array((int)$_SESSION['admin'], [3,4], true) && ($auto = resolve_bidang_code_from_session())) ? $auto : $bidang_input;
    $nama_pembuat = $_REQUEST['nama_pembuat'];

    $raw_pin = isset($_REQUEST['pin']) ? trim($_REQUEST['pin']) : '';
    if (!(ctype_digit($raw_pin) && strlen($raw_pin) === 6)) { $_SESSION['pink']='PIN harus berupa tepat 6 digit angka'; header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die(); }
    $pin = password_hash($raw_pin, PASSWORD_DEFAULT);

    $year = date('Y', strtotime($tgl_surat));
    // Agenda halaman-baris: 100 baris/halaman. Reset per tahun & per jenis bila kolom tersedia
    $hasJenisForQuery = false; $resJenisQ = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
    if ($resJenisQ && mysqli_num_rows($resJenisQ) === 1) { $hasJenisForQuery = true; }
    $filterJenis = $hasJenisForQuery ? " AND jenis='keuangan'" : "";
    $uid = (int)$id_user;
    $qcount = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE YEAR(tgl_surat)='".$year."' AND id_user='".$uid."'".$filterJenis);
    $totalThisYear = 0; if ($qcount) { $rc = mysqli_fetch_assoc($qcount); $totalThisYear = (int)$rc['c']; }
    $next = $totalThisYear + 1;
    $page = (int)ceil($next / 100);
    $line = (int)((($next - 1) % 100) + 1);
    $lineStr = ($line === 100) ? '100' : sprintf('%02d', $line);
    $no_agenda = sprintf('%02d', $page) . '-' . $lineStr;
    $no_agendak = sprintf('%02d', $page) . $lineStr;

    $q1 = mysqli_query($config, "SELECT max(id_surat) as urut FROM tbl_surat_keluar"); $d1 = mysqli_fetch_array($q1); $id_surat = ($d1['urut'] ?? 0) + 1;

    // Format umum seperti default: kode/no_agenda/bidang/tahun
    $no_surat = $nkode . '/' . $no_agendak . '/' . $bidang . '/' . $year;

    // Validasi standar
    if (!preg_match('/^[0-9.]*$/', $nkode)) { $_SESSION['kodek']='Form Kode Klasifikasi hanya angka & titik'; header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die(); }
    if (!preg_match('/^[a-zA-Z0-9.\/ -]*$/', $no_surat)) { $_SESSION['no_suratk']='No Surat tidak valid'; header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die(); }
    if (!preg_match('/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/', $perihal)) { $_SESSION['perihal']='Perihal tidak valid'; header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die(); }
    if (!preg_match('/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/', $tujuan)) { $_SESSION['tujuan']='Tujuan tidak valid'; header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die(); }
    if (!preg_match('/^[0-9.-]*$/', $tgl_surat)) { $_SESSION['tgl_suratk']='Tanggal tidak valid'; header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die(); }
    if (!preg_match('/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/', $isi)) { $_SESSION['isik']='Isi ringkas tidak valid'; header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die(); }
    if (!preg_match('/^[0-9.]*$/', $bidang)) { $_SESSION['bidangk']='Bidang tidak valid'; header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die(); }

    $dup = mysqli_query($config, "SELECT 1 FROM tbl_surat_keluar WHERE no_surat='$no_surat' LIMIT 1");
    if (mysqli_num_rows($dup) > 0) { $_SESSION['errDup']='Nomor Surat sudah terpakai, gunakan yang lain!'; header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die(); }

    // Pastikan kolom file_no tersedia
    $hasFileNo = ensure_file_no_column($config);

    // Upload file (opsional, hanya PDF maks 2MB)
    $nfile = ''; $file_no = null;
    if (!empty($_FILES['file']['name'])) {
        $ek = ['pdf'];
        $file = $_FILES['file']['name'];
        $x = explode('.', $file);
        $eks = strtolower(end($x));
        $uk = $_FILES['file']['size'];
        $dir = BASE_PATH . '/upload/surat_keluar/';
        $max = 2097152; // 2MB
        if (in_array($eks, $ek)) {
            if ($uk < $max) {
                $file_no = next_file_sequence_for_year($config, (int)$year);
                $label = format_file_sequence_label($file_no);
                $nfile = $label . ' - ' . $file;
                if (!move_uploaded_file($_FILES['file']['tmp_name'], $dir . $nfile)) {
                    $_SESSION['errQ'] = 'ERROR! Gagal mengupload file.';
                    header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan');
                    die();
                }
            } else {
                $_SESSION['errSize'] = 'Ukuran file terlalu besar! Maks 2 MB.';
                header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan');
                die();
            }
        } else {
            $_SESSION['errFormat'] = 'Format file yang diperbolehkan hanya *.PDF!';
            header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan');
            die();
        }
    }

    // Ensure jenis column exists and insert with jenis = keuangan
    $hasJenis = false; $resJenis = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
    if ($resJenis && mysqli_num_rows($resJenis) === 1) { $hasJenis = true; }
    else {
        mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN jenis VARCHAR(20) NOT NULL DEFAULT 'umum'");
        $resJenis2 = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
        if ($resJenis2 && mysqli_num_rows($resJenis2) === 1) { $hasJenis = true; }
    }
    if ($hasJenis && $hasFileNo) {
        $sql = "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,file_no,id_user,bidang,nama_pembuat,pin,jenis) VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile',".($file_no===null?"NULL":"'".intval($file_no)."'").",'$id_user','$bidang','$nama_pembuat','$pin','keuangan')";
    } elseif ($hasJenis) {
        $sql = "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,id_user,bidang,nama_pembuat,pin,jenis) VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang','$nama_pembuat','$pin','keuangan')";
    } elseif ($hasFileNo) {
        $sql = "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,file_no,id_user,bidang,nama_pembuat,pin) VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile',".($file_no===null?"NULL":"'".intval($file_no)."'").",'$id_user','$bidang','$nama_pembuat','$pin')";
    } else {
        $sql = "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,id_user,bidang,nama_pembuat,pin) VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang','$nama_pembuat','$pin')";
    }
    if (mysqli_query($config,$sql)) { $_SESSION['succAdd']='SUKSES! Data berhasil ditambahkan'; header('Location: index.php?page=admin&act=tsk_keu'); die(); }
    $_SESSION['errQ']='ERROR! Ada masalah dengan query: '.mysqli_error($config); header('Location: index.php?page=admin&act=tsk_keu&sub=add_keuangan'); die();
}

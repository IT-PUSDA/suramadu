<?php
// Proses Bank Dokumen - Hapus dan Download

if(empty($_SESSION['admin'])){
    $_SESSION['err'] = 'Anda harus login terlebih dahulu!';
    header("Location: index.php");
    die();
}

$allowed_roles = [1, 3, 4];
if (!in_array((int)$_SESSION['admin'], $allowed_roles)) {
    $_SESSION['err'] = 'Anda tidak memiliki akses!';
    header("Location: index.php");
    die();
}

$sub = isset($_GET['sub']) ? $_GET['sub'] : '';

// ========== HAPUS DOKUMEN ==========
if ($sub === 'hapus_dokumen') {
    // Check permission for delete (only admin 1 & 3)
    if (!in_array((int)$_SESSION['admin'], [1, 3])) {
        $_SESSION['err'] = 'Anda tidak memiliki hak akses untuk menghapus!';
        header("Location: index.php?page=admin&act=bank_dok");
        die();
    }

    $id_file = isset($_GET['id_file']) ? (int)$_GET['id_file'] : 0;
    $id_jenis = isset($_GET['id_jenis']) ? (int)$_GET['id_jenis'] : 0;
    $id_kat = isset($_GET['id_kat']) ? (int)$_GET['id_kat'] : 0;
    
    $query = mysqli_query($config, "SELECT file_path FROM tbl_bank_dokumen_file WHERE id_file='$id_file'");
    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $file_path = BASE_PATH . '/upload/bank_dokumen/' . $id_kat . '/' . $row['file_path'];
        
        $delete = mysqli_query($config, "DELETE FROM tbl_bank_dokumen_file WHERE id_file='$id_file'");
        if ($delete) {
            @unlink($file_path);
            $_SESSION['succ'] = 'Dokumen berhasil dihapus!';
        } else {
            $_SESSION['err'] = 'Error: ' . mysqli_error($config);
        }
    } else {
        $_SESSION['err'] = 'Dokumen tidak ditemukan!';
    }
    
    header("Location: index.php?page=admin&act=bank_dok&sub=list_dokumen&id_jenis=$id_jenis&id_kat=$id_kat");
    die();
}

// ========== DOWNLOAD DOKUMEN ==========
elseif ($sub === 'download') {
    $id_file = isset($_GET['id_file']) ? (int)$_GET['id_file'] : 0;
    
    $query = mysqli_query($config, "SELECT id_kat, file_path, nama_file FROM tbl_bank_dokumen_file WHERE id_file='$id_file'");
    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $file_path = BASE_PATH . '/upload/bank_dokumen/' . $row['id_kat'] . '/' . $row['file_path'];
        
        if (file_exists($file_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($row['nama_file']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            $_SESSION['err'] = 'File tidak ditemukan di server!';
            header("Location: index.php?page=admin&act=bank_dok");
            die();
        }
    } else {
        $_SESSION['err'] = 'Dokumen tidak ditemukan!';
        header("Location: index.php?page=admin&act=bank_dok");
        die();
    }
}

// ========== HAPUS JENIS BERKAS ==========
elseif ($sub === 'hapus_jenis') {
    // Check permission for delete (only admin 1 & 3)
    if (!in_array((int)$_SESSION['admin'], [1, 3])) {
        $_SESSION['err'] = 'Anda tidak memiliki hak akses untuk menghapus!';
        header("Location: index.php?page=admin&act=bank_dok");
        die();
    }

    $id_jenis = isset($_GET['id_jenis']) ? (int)$_GET['id_jenis'] : 0;
    $id_kat = isset($_GET['id_kat']) ? (int)$_GET['id_kat'] : 0;
    
    // Hapus semua file terlebih dahulu
    $files_query = mysqli_query($config, "SELECT file_path FROM tbl_bank_dokumen_file WHERE id_jenis='$id_jenis'");
    while ($f = mysqli_fetch_assoc($files_query)) {
        $file_path = BASE_PATH . '/upload/bank_dokumen/' . $id_kat . '/' . $f['file_path'];
        @unlink($file_path);
    }
    
    // Hapus jenis berkas
    $delete = mysqli_query($config, "DELETE FROM tbl_bank_dokumen_jenis WHERE id_jenis='$id_jenis'");
    if ($delete) {
        $_SESSION['succ'] = 'Jenis berkas dan dokumennya berhasil dihapus!';
    } else {
        $_SESSION['err'] = 'Error: ' . mysqli_error($config);
    }
    
    header("Location: index.php?page=admin&act=bank_dok&sub=list_jenis&id_kat=$id_kat");
    die();
}

else {
    header("Location: index.php?page=admin&act=bank_dok");
    die();
}

?>

<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: index.php");
        die();
    } else {

        $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_surat']);

        // Ambil nama file dari database SEBELUM record dihapus
        $query_file = mysqli_query($config, "SELECT file FROM tbl_surat_keluar WHERE id_surat='$id_surat'");
        list($file) = mysqli_fetch_array($query_file);

        // Hapus file fisik dari server jika ada
        if (!empty($file)) {
            $path_to_file = BASE_PATH . "/upload/surat_keluar/" . $file;
            
            // Cek apakah file benar-benar ada di server, lalu hapus
            if (file_exists($path_to_file)) {
                unlink($path_to_file);
            }
        }

        // Hapus record dari database SETELAH file fisik dihapus
        $query = mysqli_query($config, "DELETE FROM tbl_surat_keluar WHERE id_surat='$id_surat'");

        if ($query == true) {
            $_SESSION['succDel'] = 'SUKSES! Data dan file berhasil dihapus';
            header("Location: index.php?page=admin&act=tsk");
            die();
        } else {
            $_SESSION['errQ'] = 'ERROR! Gagal menghapus data';
            header("Location: index.php?page=admin&act=tsk");
            die();
        }
    }
?>

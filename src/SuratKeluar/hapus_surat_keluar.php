<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: index.php");
        die();
    } else {

        $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_surat']);

        // Cek hak akses server-side: hanya Super Admin bebas PIN, lainnya wajib tiket delete + kewenangan
        $is_super_admin = ($_SESSION['admin'] == 1);
        if (!$is_super_admin) {
            // Ambil pemilik data untuk validasi owner
            $q_owner = mysqli_query($config, "SELECT id_user FROM tbl_surat_keluar WHERE id_surat='$id_surat'");
            list($owner_id) = mysqli_fetch_array($q_owner);
            $can_manage = in_array($_SESSION['admin'], [2,3]); // level ini boleh kelola semua jika punya tiket
            $is_owner = ($owner_id == $_SESSION['id_user']);

            if ((!$can_manage && !$is_owner) || empty($_SESSION['delete_access_granted'][$id_surat])) {
                $_SESSION['err'] = '<center>ERROR! Anda tidak memiliki izin menghapus surat ini</center>';
                header("Location: index.php?page=admin&act=tsk");
                die();
            }
        }

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
            // Hapus tiket akses delete setelah sukses (sekali pakai)
            unset($_SESSION['delete_access_granted'][$id_surat]);
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

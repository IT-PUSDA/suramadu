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
        $is_operator = ((int)$_SESSION['admin'] === 3);
        $is_bidang   = ((int)$_SESSION['admin'] === 4);
        $q_owner = mysqli_query($config, "SELECT id_user FROM tbl_surat_keluar WHERE id_surat='$id_surat'");
        list($owner_id) = mysqli_fetch_array($q_owner);
        $is_owner = ((int)$owner_id === (int)$_SESSION['id_user']);
        // Centralized operator access
        if (!function_exists('operator_access_info')) { @include_once __DIR__ . '/../include/operator_access.php'; }
        $operator_group_access = false;
        if ($is_operator) {
            $opInfo = operator_access_info($config, $_SESSION, (int)$owner_id);
            $operator_group_access = $opInfo['operator_group_access'];
        }
        // Bidang wajib pemilik; operator wajib dalam group; jika tidak, butuh tiket (PIN) yang diset saat verifikasi
        if ($is_bidang && !$is_owner) {
            $_SESSION['err'] = '<center>ERROR! Anda tidak memiliki izin menghapus surat bidang lain</center>';
            $ret_act = isset($_REQUEST['act']) ? preg_replace('/[^a-zA-Z0-9_]/','', $_REQUEST['act']) : 'tsk';
            header("Location: index.php?page=admin&act=".$ret_act); die();
        }
        if (!$operator_group_access && !$is_owner && empty($_SESSION['delete_access_granted'][$id_surat])) {
            $_SESSION['err'] = '<center>ERROR! Anda tidak memiliki izin menghapus surat ini</center>';
            $ret_act = isset($_REQUEST['act']) ? preg_replace('/[^a-zA-Z0-9_]/','', $_REQUEST['act']) : 'tsk';
            header("Location: index.php?page=admin&act=".$ret_act); die();
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
            $ret_act = isset($_REQUEST['act']) ? preg_replace('/[^a-zA-Z0-9_]/','', $_REQUEST['act']) : 'tsk';
            header("Location: index.php?page=admin&act=".$ret_act);
            die();
        } else {
            $_SESSION['errQ'] = 'ERROR! Gagal menghapus data';
            $ret_act = isset($_REQUEST['act']) ? preg_replace('/[^a-zA-Z0-9_]/','', $_REQUEST['act']) : 'tsk';
            header("Location: index.php?page=admin&act=".$ret_act);
            die();
        }
    }
?>

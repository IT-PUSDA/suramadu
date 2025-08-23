<?php
// KODE UNTUK MENAMPILKAN ERROR SECARA PAKSA
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// TAMBAHKAN BARIS INI untuk memastikan koneksi database selalu ada
require_once __DIR__ . '/../include/config.php';

if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: index.php");
    die();
} else {
    if (isset($_REQUEST['submit1'])) {

        // Validasi form kosong
        if (
            empty($_REQUEST['kode']) || empty($_REQUEST['perihal'])
            || empty($_REQUEST['tujuan']) || empty($_REQUEST['tgl_surat'])  || empty($_REQUEST['isi'])
        ) {
            $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
            header("Location: index.php?page=admin&act=tsk&sub=add");
            die();
        } else {
            $tglx = $_REQUEST['tgl_surat'];
            $nkode = $_REQUEST['kode'];
            $perihal = $_REQUEST['perihal'];
            $tujuan = $_REQUEST['tujuan'];
            $tgl_surat = $_REQUEST['tgl_surat'];
            $isi = $_REQUEST['isi'];
            $id_user = $_SESSION['id_user'];
            $bidang = $_REQUEST['bidang'];

            // =========================================================================
            // KODE PEMBUATAN NOMOR AGENDA & SURAT YANG DIPERBAIKI DAN LEBIH AMAN
            // =========================================================================
            $year = date('Y', strtotime($tgl_surat));

            // 1. Dapatkan nomor urut agenda terakhir pada tanggal yang sama
            $query_agenda = mysqli_query($config, "SELECT no_agenda FROM tbl_surat_keluar WHERE tgl_surat='$tgl_surat' ORDER BY id_surat DESC LIMIT 1");
            
            $no_agenda_urut_baru = 1; // Default jika ini surat pertama
            if(mysqli_num_rows($query_agenda) > 0){
                $data_agenda = mysqli_fetch_assoc($query_agenda);
                // Ambil angka setelah tanda '-'
                $last_urut = (int) substr($data_agenda['no_agenda'], strpos($data_agenda['no_agenda'], "-") + 1);
                $no_agenda_urut_baru = $last_urut + 1;
            }

            // 2. Buat format nomor agenda (misal: 234-01)
            $tanggal_awal_tahun = new DateTime($year . "-01-01");
            $tanggal_surat_obj = new DateTime($tgl_surat);
            $selisih_hari = $tanggal_surat_obj->diff($tanggal_awal_tahun)->format("%a");
            
            $no_agenda = $selisih_hari . '-' . sprintf("%02d", $no_agenda_urut_baru);
            $no_agendak = $selisih_hari . sprintf("%02d", $no_agenda_urut_baru);

            // 3. Dapatkan ID Surat terakhir untuk auto-increment
            $q1 = mysqli_query($config, "SELECT max(id_surat) as urut FROM tbl_surat_keluar");
            $data1 = mysqli_fetch_array($q1);
            $id_surat = ($data1['urut'] ?? 0) + 1;

            // 4. Gabungkan menjadi Nomor Surat Lengkap
            $no_surat = $nkode . '/' . $no_agendak . '/' . $bidang . '/' . $year;
            // ===========================================================================
            // AKHIR BLOK KODE YANG DIPERBAIKI
            // ===========================================================================

            // Validasi input data (regex)
            if (!preg_match("/^[0-9.]*$/", $nkode)) {
                $_SESSION['kodek'] = 'Form Kode Klasifikasi hanya boleh mengandung karakter angka';
                header("Location: index.php?page=admin&act=tsk&sub=add");
                die();
            } else {
                if (!preg_match("/^[a-zA-Z0-9.\/ -]*$/", $no_surat)) {
                    $_SESSION['no_suratk'] = 'Form No Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), minus(-) dan garis miring(/)';
                    header("Location: index.php?page=admin&act=tsk&sub=add");
                    die();
                } else {
                    if (!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $perihal)) {
                        $_SESSION['perihal'] = 'Form Perihal Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)';
                        header("Location: index.php?page=admin&act=tsk&sub=add");
                        die();
                    } else {
                        if (!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $tujuan)) {
                            $_SESSION['tujuan'] = 'Form Tujuan Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)';
                            header("Location: index.php?page=admin&act=tsk&sub=add");
                            die();
                        } else {
                            if (!preg_match("/^[0-9.-]*$/", $tgl_surat)) {
                                $_SESSION['tgl_suratk'] = 'Form Tanggal Surat hanya boleh mengandung angka dan minus(-)';
                                header("Location: index.php?page=admin&act=tsk&sub=add");
                                die();
                            } else {
                                if (!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $isi)) {
                                    $_SESSION['isik'] = 'Form Isi Ringkas hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)';
                                    header("Location: index.php?page=admin&act=tsk&sub=add");
                                    die();
                                } else {
                                    // KODE YANG DIPERBAIKI: Regex untuk bidang sekarang memperbolehkan titik.
                                    if (!preg_match("/^[0-9.]*$/", $bidang)) {
                                        $_SESSION['bidangk'] = 'Form Bidang hanya boleh mengandung karakter angka dan titik(.)';
                                        header("Location: index.php?page=admin&act=tsk&sub=add");
                                        die();
                                    } else {
                                        $cek = mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE no_surat='$no_surat'");
                                        $result = mysqli_num_rows($cek);

                                        if ($result > 0) {
                                            $_SESSION['errDup'] = 'Nomor Surat sudah terpakai, gunakan yang lain!';
                                            header("Location: index.php?page=admin&act=tsk&sub=add");
                                            die();
                                        } else {
                                            // Logika upload file disederhanakan
                                            $nfile = ''; // Set nama file default menjadi kosong
                                            if (!empty($_FILES['file']['name'])) {
                                                $ekstensi = array('pdf');
                                                $file = $_FILES['file']['name'];
                                                $x = explode('.', $file);
                                                $eks = strtolower(end($x));
                                                $ukuran = $_FILES['file']['size'];
                                                
                                                // KODE DIPERBAIKI: Menggunakan path absolut untuk target direktori
                                                $target_dir = BASE_PATH . "/upload/surat_keluar/";
                                                
                                                $max_size = 2097152; //2MB

                                                if (in_array($eks, $ekstensi) === true) {
                                                    if ($ukuran < $max_size) {
                                                        $rand = rand(1, 10000);
                                                        $nfile = $rand . "-" . $file;
                                                        if (!move_uploaded_file($_FILES['file']['tmp_name'], $target_dir . $nfile)) {
                                                            $_SESSION['errQ'] = 'ERROR! Gagal mengupload file.';
                                                            header("Location: index.php?page=admin&act=tsk&sub=add");
                                                            die();
                                                        }
                                                    } else {
                                                        $_SESSION['errSize'] = 'Ukuran file yang diupload terlalu besar! Ukuran maksimal adalah 2 MB.';
                                                        header("Location: index.php?page=admin&act=tsk&sub=add");
                                                        die();
                                                    }
                                                } else {
                                                    $_SESSION['errFormat'] = 'Format file yang diperbolehkan hanya *.PDF!';
                                                    header("Location: index.php?page=admin&act=tsk&sub=add");
                                                    die();
                                                }
                                            }

                                            // Query INSERT hanya satu kali
                                            $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,id_user,bidang)
                                                                    VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang')");

                                            if ($query == true) {
                                                $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
                                                header("Location: index.php?page=admin&act=tsk");
                                                die();
                                            } else {
                                                $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query: ' . mysqli_error($config);
                                                header("Location: index.php?page=admin&act=tsk&sub=add");
                                                die();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

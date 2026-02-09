<?php
// KODE UNTUK MENAMPILKAN ERROR SECARA PAKSA
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// TAMBAHKAN BARIS INI untuk memastikan koneksi database selalu ada
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/bidang_mapping.php';
require_once __DIR__ . '/../include/file_sequence.php';
// Opsional: client Drive sederhana bila penyimpanan GDrive diaktifkan
@include_once __DIR__ . '/../Utils/GoogleDriveClient.php';

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
            || empty($_REQUEST['nama_pembuat']) || empty($_REQUEST['pin'])
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
            $bidang_input = $_REQUEST['bidang'] ?? '';
            // Resolve default bidang for locked roles (3 & 4). If the form submitted
            // a different value (user clicked "Ubah"), prefer the submitted value.
            $bidang_resolved = (in_array((int)$_SESSION['admin'], [3,4], true)) ? resolve_bidang_code_from_session() : null;
            $bidang = ($bidang_input !== '' && $bidang_input !== null) ? $bidang_input : ($bidang_resolved ?? $bidang_input);
            $nama_pembuat = $_REQUEST['nama_pembuat'];

            // Validasi PIN: harus 6 digit angka
            $raw_pin = isset($_REQUEST['pin']) ? trim($_REQUEST['pin']) : '';
            if (!(ctype_digit($raw_pin) && strlen($raw_pin) === 6)) {
                $_SESSION['pink'] = 'PIN harus berupa tepat 6 digit angka';
                header("Location: index.php?page=admin&act=tsk&sub=add");
                die();
            }
            $pin = password_hash($raw_pin, PASSWORD_DEFAULT);

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

            // 4. Buat format nomor surat: gunakan posisi page+line per bidang dan per jenis
            //    Posisi dihitung per tahun, per bidang, dan per jenis agar setiap kombinasi terpisah.
            //    KODE BARU: Support penomoran sisipan jika tanggal surat mundur.
            $pos_code = get_sequence_code_with_sisipan($config, (int)$year, $bidang, 'umum', $tgl_surat);
            // $pos_seq = next_position_sequence_for_year_and_bidang($config, (int)$year, $bidang, 'umum');
            // $pos_code = page_line_label_from_seq($pos_seq, 40); // mis. '0101' -> halaman 01 baris 01
            $no_surat = $nkode . '/' . $pos_code . '/' . $bidang . '/' . $year;
            // ===========================================================================
            // AKHIR BLOK KODE YANG DIPERBAIKI
            // ===========================================================================

            // Validasi input data (regex)
            if (!preg_match("/^[0-9.]*$/", $nkode)) {
                $_SESSION['kodek'] = 'Form Kode Klasifikasi hanya boleh mengandung karakter angka';
                header("Location: index.php?page=admin&act=tsk&sub=add");
                die();
            } else {
                if (!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -'\"!:;?]*$/", $no_surat)) {
                    $_SESSION['no_suratk'] = 'Form No Surat mengandung karakter terlarang.';
                    header("Location: index.php?page=admin&act=tsk&sub=add");
                    die();
                } else {
                    // Regex Fix: Move hyphen to end to avoid range interpretation, and fix Session Keys
                    if (!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n '\"!:;?-]*$/", $perihal)) {
                        $_SESSION['perihalk'] = 'Form Perihal Surat mengandung karakter terlarang.';
                        header("Location: index.php?page=admin&act=tsk&sub=add");
                        die();
                    } else {
                        if (!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n '\"!:;?-]*$/", $tujuan)) {
                            $_SESSION['tujuan_surat'] = 'Form Tujuan Surat mengandung karakter terlarang.';
                            header("Location: index.php?page=admin&act=tsk&sub=add");
                            die();
                        } else {
                            if (!preg_match("/^[0-9.-]*$/", $tgl_surat)) {
                                $_SESSION['tgl_suratk'] = 'Form Tanggal Surat hanya boleh mengandung angka dan minus(-)';
                                header("Location: index.php?page=admin&act=tsk&sub=add");
                                die();
                            } else {
                                if (!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n '\"!:;?-]*$/", $isi)) {
                                    $_SESSION['isik'] = 'Form Isi Ringkas mengandung karakter terlarang.';
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
                                            // Pastikan kolom file_no tersedia untuk menyimpan nomor file yang konsisten
                                            $hasFileNo = ensure_file_no_column($config);

                                            // Logika upload file
                                            $nfile = ''; $file_no = null; // default
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
                                                        // Ambil nomor urut file untuk tahun surat (reset awal tahun)
                                                        $file_no = next_file_sequence_for_year($config, (int)$year);
                                                        $label = format_file_sequence_label($file_no);
                                                        $nfile = $label . " - " . $file;
                                                        $labeledName = $nfile; // keep original labeled name for display
                                                        $tmpPath = $_FILES['file']['tmp_name'];
                                                        // Jika penyimpanan = gdrive, unggah ke Google Drive
                                                        if (function_exists('is_gdrive_enabled') && is_gdrive_enabled()) {
                                                            try {
                                                                $gdc = new GoogleDriveClientSimple(GDRIVE_SERVICE_ACCOUNT_JSON);
                                                                $result = $gdc->uploadFile($tmpPath, $nfile, 'application/pdf', GDRIVE_PARENT_FOLDER_ID);
                                                                // Simpan marker gdrive + sertakan nama berkas berlabel agar bisa ditampilkan di list
                                                                $nfile = 'gdrive:fileId=' . $result['fileId'];
                                                                if (!empty($result['webViewLink'])) {
                                                                    $nfile .= '|view=' . $result['webViewLink'];
                                                                }
                                                                $nfile .= '|name=' . rawurlencode($labeledName);
                                                            } catch (Exception $e) {
                                                                // Fallback ke penyimpanan lokal bila gagal
                                                                if (!is_dir($target_dir)) { @mkdir($target_dir, 0775, true); }
                                                                if (!move_uploaded_file($tmpPath, $target_dir . $nfile)) {
                                                                    $_SESSION['errQ'] = 'ERROR! Gagal mengupload file (lokal/gdrive). Detail: ' . $e->getMessage();
                                                                    header("Location: index.php?page=admin&act=tsk&sub=add");
                                                                    die();
                                                                }
                                                            }
                                                        } else {
                                                            if (!is_dir($target_dir)) { @mkdir($target_dir, 0775, true); }
                                                            if (!move_uploaded_file($tmpPath, $target_dir . $nfile)) {
                                                                $_SESSION['errQ'] = 'ERROR! Gagal mengupload file.';
                                                                header("Location: index.php?page=admin&act=tsk&sub=add");
                                                                die();
                                                            }
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
                                            // Pastikan kolom jenis ada (default 'umum')
                                            $hasJenis = false; $resJenis = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
                                            if ($resJenis && mysqli_num_rows($resJenis) === 1) { $hasJenis = true; }
                                            else {
                                                mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN jenis VARCHAR(20) NOT NULL DEFAULT 'umum'");
                                                $resJenis2 = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
                                                if ($resJenis2 && mysqli_num_rows($resJenis2) === 1) { $hasJenis = true; }
                                            }

                                            if ($hasJenis && $hasFileNo) {
                                                $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,file_no,id_user,bidang,nama_pembuat,pin,jenis)
                                                                        VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile',".($file_no===null?"NULL":"'".intval($file_no)."'").",'$id_user','$bidang', '$nama_pembuat', '$pin','umum')");
                                            } elseif ($hasJenis) {
                                                $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,id_user,bidang,nama_pembuat,pin,jenis)
                                                                        VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang', '$nama_pembuat', '$pin','umum')");
                                            } elseif ($hasFileNo) {
                                                $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,file_no,id_user,bidang,nama_pembuat,pin)
                                                                        VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile',".($file_no===null?"NULL":"'".intval($file_no)."'").",'$id_user','$bidang', '$nama_pembuat', '$pin')");
                                            } else {
                                                $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,id_user,bidang,nama_pembuat,pin)
                                                                        VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang', '$nama_pembuat', '$pin')");
                                            }

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

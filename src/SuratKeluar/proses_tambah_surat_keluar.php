<?php
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: index.php");
    die();
} else {
    if (isset($_REQUEST['submit1'])) { // Diubah dari submit menjadi submit1 sesuai form sebelumnya

        // Validasi form kosong
        if (
            $_REQUEST['kode'] ==  "" || $_REQUEST['perihal'] == ""
            || $_REQUEST['tujuan'] == "" || $_REQUEST['tgl_surat'] == ""  || $_REQUEST['isi'] == ""
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
            $tanggal2 = new DateTime($tglx);
            $id_next = mysqli_query($config, "SELECT max(id_surat) as id_next FROM tbl_surat_keluar where tgl_surat='$tglx' ");
            $data_id = mysqli_fetch_array($id_next);
            $idn = $data_id['id_next'];
            $q = mysqli_query($config, "SELECT no_agenda FROM tbl_surat_keluar where tgl_surat='$tglx' and id_surat='$idn'");
            $data = mysqli_fetch_array($q);
            $q1 = mysqli_query($config, "SELECT max(id_surat) as urut FROM tbl_surat_keluar");
            $data1 = mysqli_fetch_array($q1);
            $urutan = $data1['urut'];
            $id_surat = $urutan + 1;
            $year = substr($tglx, 0, 4);
            $tanggal1 = new DateTime($year . "-01-01");
            $tgl_selisih = $tanggal2->diff($tanggal1)->format("%a");
            $c_no_agenda = strpos($data['no_agenda'], "-");
            if ($tgl_selisih < 10) {
                $tgls = "0" . $tgl_selisih;
                if (isset($data['no_agenda'])) {
                    if ($c_no_agenda < 3) {
                        $no_agenda_k = $data['no_agenda'];
                        $no_agenda_in = strlen($no_agenda_k);
                        if ($no_agenda_in >= 6) {
                            $no_agenda_urut = ((substr($no_agenda_k, -3))) + 1;
                            $no_lembar = substr($no_agenda_k, 0, strpos($no_agenda_k, "-"));
                            if ($no_agenda_urut < 10) {
                                $no_agenda = $no_lembar . "-0" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "0" . $no_agenda_urut;
                            } else {
                                $no_agenda = $no_lembar . "-" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "" . $no_agenda_urut;
                            }
                        } else {
                            $no_agenda_urut = ((substr($no_agenda_k, -2))) + 1;
                            $no_lembar = substr($no_agenda_k, 0, strpos($no_agenda_k, "-"));
                            if ($no_agenda_urut < 10) {
                                $no_agenda = $no_lembar . "-0" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "0" . $no_agenda_urut;
                            } else {
                                $no_agenda = $no_lembar . "-" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "" . $no_agenda_urut;
                            }
                        }
                    } else {
                        $no_agenda_k = $data['no_agenda'];
                        $no_agenda_in = strlen($no_agenda_k);
                        if ($no_agenda_in > 6) {
                            $no_agenda_urut = ((substr($no_agenda_k, -3))) + 1;
                            $no_lembar = substr($no_agenda_k, 0, strpos($no_agenda_k, "-"));
                            if ($no_agenda_urut < 10) {
                                $no_agenda = $no_lembar . "-0" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "0" . $no_agenda_urut;
                            } else {
                                $no_agenda = $no_lembar . "-" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "" . $no_agenda_urut;
                            }
                        } else {
                            $no_agenda_urut = ((substr($no_agenda_k, -2))) + 1;
                            $no_lembar = substr($no_agenda_k, 0, strpos($no_agenda_k, "-"));
                            if ($no_agenda_urut < 10) {
                                $no_agenda = $no_lembar . "-0" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "0" . $no_agenda_urut;
                            } else {
                                $no_agenda = $no_lembar . "-" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "" . $no_agenda_urut;
                            }
                        }
                    }
                } else {
                    $no_agenda_urut = "01";
                    $no_agenda = $tgls . "-" . $no_agenda_urut;
                    $no_agendak = $tgls . "" . $no_agenda_urut;
                }
            } else {
                $tgls = $tgl_selisih;
                if (isset($data['no_agenda'])) {
                    if ($c_no_agenda < 3) {
                        $no_agenda_k = $data['no_agenda'];
                        $no_agenda_in = strlen($no_agenda_k);
                        if ($no_agenda_in >= 6) {
                            $no_agenda_urut = ((substr($no_agenda_k, -3))) + 1;
                            $no_lembar = substr($no_agenda_k, 0, strpos($no_agenda_k, "-"));
                            if ($no_agenda_urut < 10) {
                                $no_agenda = $no_lembar . "-0" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "0" . $no_agenda_urut;
                            } else {
                                $no_agenda = $no_lembar . "-" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "" . $no_agenda_urut;
                            }
                        } else {
                            $no_agenda_urut = ((substr($no_agenda_k, -2))) + 1;
                            $no_lembar = substr($no_agenda_k, 0, strpos($no_agenda_k, "-"));
                            if ($no_agenda_urut < 10) {
                                $no_agenda = $no_lembar . "-0" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "0" . $no_agenda_urut;
                            } else {
                                $no_agenda = $no_lembar . "-" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "" . $no_agenda_urut;
                            }
                        }
                    } else {
                        $no_agenda_k = $data['no_agenda'];
                        $no_agenda_in = strlen($no_agenda_k);
                        if ($no_agenda_in > 6) {
                            $no_agenda_urut = ((substr($no_agenda_k, -3))) + 1;
                            $no_lembar = substr($no_agenda_k, 0, strpos($no_agenda_k, "-"));
                            if ($no_agenda_urut < 10) {
                                $no_agenda = $no_lembar . "-0" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "0" . $no_agenda_urut;
                            } else {
                                $no_agenda = $no_lembar . "-" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "" . $no_agenda_urut;
                            }
                        } else {
                            $no_agenda_urut = ((substr($no_agenda_k, -2))) + 1;
                            $no_lembar = substr($no_agenda_k, 0, strpos($no_agenda_k, "-"));
                            if ($no_agenda_urut < 10) {
                                $no_agenda = $no_lembar . "-0" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "0" . $no_agenda_urut;
                            } else {
                                $no_agenda = $no_lembar . "-" . $no_agenda_urut;
                                $no_agendak = $no_lembar . "" . $no_agenda_urut;
                            }
                        }
                    }
                } else {
                    $no_agenda_urut = "01";
                    $no_agenda = $tgls . "-" . $no_agenda_urut;
                    $no_agendak = $tgls . "" . $no_agenda_urut;
                }
            }
            $no_surat = $nkode . '/' . $no_agendak . '/' . $bidang . '/' . $year;

            // Validasi input data
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
                                    $cek = mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE no_surat='$no_surat'");
                                    $result = mysqli_num_rows($cek);

                                    if ($result > 0) {
                                        $_SESSION['errDup'] = 'Nomor Surat sudah terpakai, gunakan yang lain!';
                                        header("Location: index.php?page=admin&act=tsk&sub=add");
                                        die();
                                    } else {
                                        $ekstensi = array('jpg', 'png', 'jpeg', 'doc', 'docx', 'pdf');
                                        $file = $_FILES['file']['name'];
                                        $x = explode('.', $file);
                                        $eks = strtolower(end($x));
                                        $ukuran = $_FILES['file']['size'];
                                        $target_dir = "upload/surat_keluar/";

                                        if ($file != "") {
                                            $rand = rand(1, 10000);
                                            $nfile = $rand . "-" . $file;
                                            if (in_array($eks, $ekstensi) == true) {
                                                if ($ukuran < 5220350) {
                                                    move_uploaded_file($_FILES['file']['tmp_name'], $target_dir . $nfile);

                                                    $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,id_user,bidang)
                                                                VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang')");

                                                    if ($query == true) {
                                                        $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
                                                        header("Location: index.php?page=admin&act=tsk");
                                                        die();
                                                    } else {
                                                        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                                                        header("Location: index.php?page=admin&act=tsk&sub=add");
                                                        die();
                                                    }
                                                } else {
                                                    $_SESSION['errSize'] = 'Ukuran file yang diupload terlalu besar!';
                                                    header("Location: index.php?page=admin&act=tsk&sub=add");
                                                    die();
                                                }
                                            } else {
                                                $_SESSION['errFormat'] = 'Format file yang diperbolehkan hanya *.JPG, *.PNG, *.DOC, *.DOCX atau *.PDF!';
                                                header("Location: index.php?page=admin&act=tsk&sub=add");
                                                die();
                                            }
                                        } else {
                                            $_SESSION['errQ'] = 'ERROR! File Surat belum diupload';
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

<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        if(isset($_REQUEST['submit'])){

            //validasi form kosong
            if($_REQUEST['kode'] == "" || $_REQUEST['no_surat'] == "" || $_REQUEST['perihal'] == ""
                || $_REQUEST['tujuan'] == "" || $_REQUEST['tgl_surat'] == ""  || $_REQUEST['isi'] == ""){
                $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
                    echo '<script language="javascript">window.history.back();</script>';
            } else {

                $id_surat=$_REQUEST['id_surat'];
                $no_agenda = $_REQUEST['no_agenda'];
                $kode = substr($_REQUEST['kode'],0,30);
                $nkode = trim($kode);
				$no_surat = $_REQUEST['no_surat'];
				$perihal = $_REQUEST['perihal'];
                $tujuan = $_REQUEST['tujuan'];
				$tgl_surat = $_REQUEST['tgl_surat'];
                $isi = $_REQUEST['isi'];
                $id_user = $_SESSION['id_user'];
				$bidang = $_REQUEST['bidang'];

                //validasi input data
                if(!preg_match("/^[0-9.]*$/", $nkode)){
                    $_SESSION['kodek'] = 'Form Kode Klasifikasi hanya boleh mengandung karakter angka dan titik(.)';
                    echo '<script language="javascript">window.history.back();</script>';
                }  else {

                    if(!preg_match("/^[a-zA-Z0-9.\/ -]*$/", $no_surat)){
                        $_SESSION['no_suratk'] = 'Form No Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), minus(-) dan garis miring(/)';
                        echo '<script language="javascript">window.history.back();</script>';
                    } else {

                        if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $perihal)){
                            $_SESSION['perihal'] = 'Form Perihal Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)';
                            echo '<script language="javascript">window.history.back();</script>';
                        } else {

                            if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $tujuan)){
								$_SESSION['tujuan_surat'] = 'Form Tujuan Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)';
								echo '<script language="javascript">window.history.back();</script>';
							} else {

                                if(!preg_match("/^[0-9.-]*$/", $tgl_surat)){
									$_SESSION['tgl_suratk'] = 'Form Tanggal Surat hanya boleh mengandung angka dan minus(-)';
                                    echo '<script language="javascript">window.history.back();</script>';
                                }  else {

                                    if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $isi)){
										$_SESSION['isik'] = 'Form Isi Ringkas hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)';
										echo '<script language="javascript">window.history.back();</script>';
									} else {
											$ekstensi = array('jpg','png','jpeg','doc','docx','pdf');
											$file = $_FILES['file']['name'];
											$x = explode('.', $file);
											$eks = strtolower(end($x));
											$ukuran = $_FILES['file']['size'];
											$target_dir = "upload/surat_keluar/";
											
											$id_surat = $_REQUEST['id_surat'];
											$query1 = mysqli_query($config, "SELECT file FROM tbl_surat_keluar WHERE id_surat='$id_surat'");
                                            $data1=mysqli_fetch_array($query1);
											$files=$data1['file'];
                                            //jika form file tidak kosong akan mengeksekusi script dibawah ini
                                            if($file != ""){
												
											    $rand = rand(1,10000);
                                                $nfile = $rand."-".$file;
												
                                                //validasi file
                                                if(in_array($eks, $ekstensi) == true){
                                                    if($ukuran < 5220350){

                                                        $id_surat = $_REQUEST['id_surat'];
                                                     	
                                                        //jika file kosong akan diganti baru mengeksekusi script dibawah ini
                                                        if($files ==""){
                                                            //unlink($target_dir.$file);

                                                            move_uploaded_file($_FILES['file']['tmp_name'], $target_dir.$nfile);

                                                            $query = mysqli_query($config, "UPDATE tbl_surat_keluar SET perihal='$perihal', no_surat='$no_surat', tujuan='$tujuan', kode='$nkode', 
															tgl_surat='$tgl_surat', isi='$isi', file='$nfile', id_user='$id_user',bidang='$bidang' WHERE id_surat='$id_surat'");

                                                            if($query == true){
                                                                $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
                                                                header("Location: ./admin.php?page=tsk");
                                                                die();
                                                            } else {
                                                                $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                                                                echo '<script language="javascript">window.history.back();</script>';
                                                            }
                                                        } else {

                                                            //jika file diganti baru akan mengeksekusi script dibawah ini
															unlink($target_dir.$file);
                                                            move_uploaded_file($_FILES['file']['tmp_name'], $target_dir.$nfile);

                                                            $query = mysqli_query($config, "UPDATE tbl_surat_keluar SET perihal='$perihal', no_surat='$no_surat', tujuan='$tujuan', kode='$nkode', 
															tgl_surat='$tgl_surat', isi='$isi', file='$nfile', id_user='$id_user',bidang='$bidang' WHERE id_surat='$id_surat'");

                                                            if($query == true){
                                                                $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
                                                                header("Location: ./admin.php?page=tsk");
                                                                die();
                                                            } else {
                                                                $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                                                                echo '<script language="javascript">window.history.back();</script>';
                                                            }
                                                        }
                                                    } else {
                                                        $_SESSION['errSize'] = 'Ukuran file yang diupload terlalu besar!';
                                                        echo '<script language="javascript">window.history.back();</script>';
                                                    }
                                                } else {
                                                    $_SESSION['errFormat'] = 'Format file yang diperbolehkan hanya *.JPG, *.PNG, *.DOC, *.DOCX atau *.PDF!';
                                                    echo '<script language="javascript">window.history.back();</script>';
                                                }
                                            } else {

                                                //jika form file kosong atau tetap akan mengeksekusi script dibawah ini
                                                $id_surat = $_REQUEST['id_surat'];

                                                $query = mysqli_query($config, "UPDATE tbl_surat_keluar SET perihal='$perihal', no_surat='$no_surat', tujuan='$tujuan', kode='$nkode', 
															tgl_surat='$tgl_surat', isi='$isi', id_user='$id_user',bidang='$bidang' WHERE id_surat='$id_surat'");

                                                if($query == true){
                                                    $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
                                                    header("Location: ./admin.php?page=tsk");
                                                    die();
                                                } else {
                                                    $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                                                    echo '<script language="javascript">window.history.back();</script>';
                                                }
                                            }
                                        
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {

            $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_surat']);
            $query = mysqli_query($config, "SELECT id_surat, no_agenda, perihal, no_surat, tujuan, kode, tgl_surat, isi, file, id_user, bidang FROM tbl_surat_keluar WHERE id_surat='$id_surat'");
            list($id_surat, $no_agenda, $perihal, $no_surat, $tujuan, $kode, $tgl_surat, $isi, $file, $id_user, $bidang) = mysqli_fetch_array($query);
            if($_SESSION['id_user'] != $id_user AND $_SESSION['id_user'] != 1){
                echo '<script language="javascript">
                        window.alert("ERROR! Anda tidak memiliki hak akses untuk mengedit data ini");
                        window.location.href="./index.php?page=admin&act=tsk";
                      </script>';
            } else {?>

                <!-- Row Start -->
                <div class="row">
                    <!-- Secondary Nav START -->
                    <div class="col s12">
                        <nav class="secondary-nav">
                            <div class="nav-wrapper blue-grey darken-1">
                                <ul class="left">
                                    <li class="waves-effect waves-light"><a href="#" class="judul"><i class="material-icons">edit</i> Edit Data Surat Keluar</a></li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                    <!-- Secondary Nav END -->
                </div>
                <!-- Row END -->

                <?php
                    if(isset($_SESSION['errQ'])){
                        $errQ = $_SESSION['errQ'];
                        echo '<div id="alert-message" class="row">
                                <div class="col m12">
                                    <div class="card red lighten-5">
                                        <div class="card-content notif">
                                            <span class="card-title red-text"><i class="material-icons md-36">clear</i> '.$errQ.'</span>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        unset($_SESSION['errQ']);
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
                ?>

                <!-- Row form Start -->
                <div class="row jarak-form">

                    <!-- Form START -->
                    <form class="col s12" method="POST" action="?page=tsk&act=edit" enctype="multipart/form-data">

                        <!-- Row in form START -->
                        <div class="row">
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">looks_one</i>
							<input id="id_surat" type="number" class="validate" name="id_surat"  value="<?php echo $id_surat;?>" hidden required>
                            <input id="no_agenda" type="text" class="validate" name="no_agenda"  value="<?php echo $no_agenda;?>" readonly required>
                                <?php
                                    if(isset($_SESSION['no_agendak'])){
                                        $no_agendak = $_SESSION['no_agendak'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$no_agendak.'</div>';
                                        unset($_SESSION['no_agendak']);
                                    }
                                ?>
                            <label for="no_agenda">Nomor Agenda</label>
                        </div>
                            <div class="input-field col s6 tooltipped" data-position="top" data-tooltip="Diambil dari data referensi kode klasifikasi">
                                <i class="material-icons prefix md-prefix">bookmark</i>
                                <input id="kode" type="text" class="validate" name="kode" value="<?php echo $kode ;?>" readonly required>
                                    <?php
                                        if(isset($_SESSION['kodek'])){
                                            $kodek = $_SESSION['kodek'];
                                            echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$kodek.'</div>';
                                            unset($_SESSION['kodek']);
                                        }
                                    ?>
                                <label for="kode">Kode Klasifikasi</label>
                            </div>
							<div class="input-field col s6">
                                <i class="material-icons prefix md-prefix">looks_two</i>
                                <input id="no_surat" type="text" class="validate" name="no_surat" value="<?php echo $no_surat ;?>" readonly required>
                                    <?php
                                        if(isset($_SESSION['no_suratk'])){
                                            $no_suratk = $_SESSION['no_suratk'];
                                            echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$no_suratk.'</div>';
                                            unset($_SESSION['no_suratk']);
                                        }
                                    ?>
                                <label for="no_surat">Nomor Surat</label>
                            </div>
							<div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">featured_play_list</i>
                            <input id="perihal" type="text" class="validate" name="perihal" value="<?php echo $perihal ;?>"required>
                                <?php
                                    if(isset($_SESSION['perihalk'])){
                                        $perihalk = $_SESSION['perihalk'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$perihalk.'</div>';
                                        unset($_SESSION['perihalk']);
                                    }
                                ?>
                            <label for="perihal">Perihal</label>
							</div>
							<div class="input-field col s6">
                                <i class="material-icons prefix md-prefix">place</i>
                                <input id="tujuan" type="text" class="validate" name="tujuan" value="<?php echo $tujuan ;?>" required>
                                    <?php
                                        if(isset($_SESSION['tujuan_surat'])){
                                            $tujuan_surat = $_SESSION['tujuan_surat'];
                                            echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$tujuan_surat.'</div>';
                                            unset($_SESSION['tujuan_surat']);
                                        }
                                    ?>
                                <label for="tujuan">Tujuan Surat</label>
                            </div>
                            <div class="input-field col s6">
                                <i class="material-icons prefix md-prefix">date_range</i>
                                <input type="text"  class="validate" name="tgl_surat" value="<?php echo $tgl_surat ;?>" readonly required>
                                    <?php
                                        if(isset($_SESSION['tgl_suratk'])){
                                            $tgl_suratk = $_SESSION['tgl_suratk'];
                                            echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$tgl_suratk.'</div>';
                                            unset($_SESSION['tgl_suratk']);
                                        }
                                    ?>
                                <label for="tgl_surat">Tanggal Surat</label>
                            </div>
                            <div class="input-field col s6">
                                <i class="material-icons prefix md-prefix">description</i>
                                <textarea id="isi" class="materialize-textarea validate" name="isi" required><?php echo $isi ;?></textarea>
                                    <?php
                                        if(isset($_SESSION['isik'])){
                                            $isik = $_SESSION['isik'];
                                            echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$isik.'</div>';
                                            unset($_SESSION['isik']);
                                        }
                                    ?>
                                <label for="isi">Isi Ringkas</label>
                            </div>
							<div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">featured_play_list</i>
                            <input id="bidang" type="text" class="validate" name="bidang" value='<?php echo $bidang; ?>' readonly required>
                                <?php
                                    if(isset($_SESSION['bidangk'])){
                                        $bidangk = $_SESSION['bidangk'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$bidangk.'</div>';
                                        unset($_SESSION['bidangk']);
                                    }
                                ?>
                            <label for="bidang">Bidang</label>
                        </div>
                            <div class="input-field col s6">
                            <div class="file-field input-field tooltipped" data-position="top" data-tooltip="Jika tidak ada file/scan gambar surat, biarkan kosong">
                                <div class="btn light-green darken-1">
                                    <span>File</span>
                                    <input type="file" id="file" name="file">
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" type="text" value="<?php echo $file ;?>" placeholder="Upload file/scan gambar surat masuk">
                                        <?php
                                            if(isset($_SESSION['errSize'])){
                                                $errSize = $_SESSION['errSize'];
                                                echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$errSize.'</div>';
                                                unset($_SESSION['errSize']);
                                            }
                                            if(isset($_SESSION['errFormat'])){
                                                $errFormat = $_SESSION['errFormat'];
                                                echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$errFormat.'</div>';
                                                unset($_SESSION['errFormat']);
                                            }
                                        ?>
                                    <small class="red-text">*Format file yang diperbolehkan *.JPG, *.PNG, *.DOC, *.DOCX, *.PDF dan ukuran maksimal file 5 MB!</small>
                                </div>
                            </div>
                        </div>
                        </div>
                        <!-- Row in form END -->

                        <div class="row">
                            <div class="col 6">
                                <button type="submit" name="submit" class="btn-large blue waves-effect waves-light">SIMPAN <i class="material-icons">done</i></button>
                            </div>
                            <div class="col 6">
                                <a href="?page=tsk" class="btn-large deep-orange waves-effect waves-light">BATAL <i class="material-icons">clear</i></a>
                            </div>
                        </div>

                    </form>
                    <!-- Form END -->

                </div>
                <!-- Row form END -->

<?php
            }
        }
    }
?>

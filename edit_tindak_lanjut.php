<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        if(isset($_REQUEST['submit'])){

            //validasi form kosong
            if($_REQUEST['perihal'] == "" || $_REQUEST['isi_tindak_lanjut'] == ""){
                $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
                echo '<script language="javascript">window.history.back();</script>';
            } else {
				
				$id_tindak_lanjut = $_REQUEST['id_tindak_lanjut'];
                $isi_tindak_lanjut = $_REQUEST['isi_tindak_lanjut'];
                $perihal = $_REQUEST['perihal'];
				//$file = $_REQUEST['file'];
                $id_disposisi=$_REQUEST['id_disposisi'];
				$id_user = $_SESSION['id_user'];
				

                //validasi input data
                if(!preg_match("/^[a-zA-Z0-9.,()%@\/ -]*$/", $perihal)){
                    $_SESSION['perihal'] = 'Form Perihal hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-) garis miring(/), dan kurung()';
                    echo '<script language="javascript">window.history.back();</script>';                   
				} else {

                    if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $isi_tindak_lanjut)){
                        $_SESSION['isi_tindak_lanjut'] = 'Form Isi Tindak Lanjut hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), dan(&), underscore(_), kurung(), persen(%) dan at(@)';
                        echo '<script language="javascript">window.history.back();</script>';
					} else {
									$ekstensi = array('jpg','png','jpeg','doc','docx','pdf');
                                    $file = $_FILES['file']['name'];
									$x = explode('.', $file);
                                    $eks = strtolower(end($x));
                                    $ukuran = $_FILES['file']['size'];
                                    $target_dir = "upload/tindak_lanjut/";
                                     
									 //jika form file tidak kosong akan mengeksekusi script dibawah ini
                                     if($file != ""){

										$rand = rand(1,10000);
                                         $nfile = $rand."-".$file;

                                         //validasi file
                                         if(in_array($eks, $ekstensi) == true){
                                         if($ukuran < 5220350){

											$id_tindak_lanjut = $_REQUEST['id_tindak_lanjut'];
											$query = mysqli_query($config, "SELECT file FROM tbl_tindak_lanjut WHERE id_tindak_lanjut='$id_tindak_lanjut'");
                                            list($file) = mysqli_fetch_array($query);

                                            //jika file tidak kosong akan mengeksekusi script dibawah ini
                                            if(!empty($file)){
												unlink($target_dir.$file);

												move_uploaded_file($_FILES['file']['tmp_name'], $target_dir.$nfile);

												$query = mysqli_query($config, "UPDATE tbl_tindak_lanjut SET perihal='$perihal',isi_tindak_lanjut='$isi_tindak_lanjut',tgl_tindak_lanjut=NOW(),file='$nfile' 
												WHERE id_tindak_lanjut='$id_tindak_lanjut'");

												if($query == true){
													$_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
													echo '<script language="javascript">
															window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
															</script>';
													die();
												} else {
															$_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
															echo '<script language="javascript">window.history.back();</script>';
															}
                                            } else {

                                                         //jika file kosong akan mengeksekusi script dibawah ini
                                                         move_uploaded_file($_FILES['file']['tmp_name'], $target_dir.$nfile);

                                                        $query = mysqli_query($config, "UPDATE tbl_tindak_lanjut SET perihal='$perihal',isi_tindak_lanjut='$isi_tindak_lanjut',tgl_tindak_lanjut=NOW(),file='$nfile' 
														WHERE id_tindak_lanjut='$id_tindak_lanjut'");

                                                            if($query == true){
                                                                $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
                                                                echo '<script language="javascript">
																window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
																</script>';
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

                                                //jika form file kosong akan mengeksekusi script dibawah ini
                                                
                                                $query = mysqli_query($config, "UPDATE tbl_tindak_lanjut SET perihal='$perihal',isi_tindak_lanjut='$isi_tindak_lanjut',tgl_tindak_lanjut=NOW() WHERE id_tindak_lanjut='$id_tindak_lanjut'");

                                                if($query == true){
													$id_disposisi = $_REQUEST['id_disposisi'];
                                                    $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
                                                    echo '<script language="javascript">
																window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
																</script>';
                                                    die();
                                                } else {
                                                    $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                                                    echo '<script language="javascript">window.history.back();</script>';
                                                }
                                            }
                }
            }
        }
    } else {
		$id_tindak_lanjut = $_REQUEST['id_tindak_lanjut'];
		$id_user = $_SESSION['id_user'];
        $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_tindak_lanjut']);
        $query = mysqli_query($config, "SELECT id_tindak_lanjut,perihal,isi_tindak_lanjut,id_disposisi,file FROM tbl_tindak_lanjut WHERE id_tindak_lanjut='$id_tindak_lanjut'");
        list($id_tindak_lanjut, $perihal, $isi_tindak_lanjut,$id_disposisi,$file) = mysqli_fetch_array($query);

        if($_SESSION['admin'] == 2 OR $_SESSION['admin'] == 3){
            echo '<script language="javascript">
                    window.alert("ERROR! Anda tidak memiliki hak akses untuk mengedit data ini");
                    window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
                  </script>';
        } else {?>

            <!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <nav class="secondary-nav">
                        <div class="nav-wrapper blue-grey darken-1">
                            <ul class="left">
                                <li class="waves-effect waves-light"><a href="#" class="judul"><i class="material-icons">edit</i> Edit Data Tindak Lanjut</a></li>
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
                <form class="col s12" method="POST" action="" enctype="multipart/form-data">

                    <!-- Row in form START -->
                    <div class="row">
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">looks_one</i>
							<input id="id_tindak_lanjut" type="text" class="validate" name="id_tindak_lanjut" value="<?php echo $id_tindak_lanjut; ?>" readonly required>
							<?php
									if(isset($_SESSION['id_tindak_lanjutl'])){
                                    $id_tindak_lanjutl = $_SESSION['id_tindak_lanjutl'];
                                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$id_tindak_lanjutl.'</div>';
                                    unset($_SESSION['id_tindak_lanjutl']);
                                    }
							?>
                            <label for="id_tindak_lanjut">Nomor Tindak Lanjut</label>
                        </div>
						<div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">featured_play_list</i>
							<input id="perihal" type="text" class="validate" name="perihal" value="<?php echo $perihal;?>" readonly required>
                                <?php
                                    if(isset($_SESSION['perihal'])){
                                        $perihal = $_SESSION['perihal'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$perihal.'</div>';
                                        unset($_SESSION['perihal']);
                                    }
                                ?>
                            <label for="perihal">Perihal Surat</label>
                        </div>
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">description</i>
                            <textarea id="isi_tindak_lanjut" class="materialize-textarea validate" name="isi_tindak_lanjut" required><?php echo $isi_tindak_lanjut;?></textarea>
                                <?php
                                    if(isset($_SESSION['isi_tindak_lanjut'])){
                                        $isi_disposisi = $_SESSION['isi_tindak_lanjut'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$isi_tindak_lanjut.'</div>';
                                        unset($_SESSION['isi_tindak_lanjut']);
                                    }
                                ?>
                            <label for="isi_tindak_lanjut">Isi Tindak Lanjut</label>
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
                            <a href="?page=tsm&act=tlanjut&id_disposisi=<?php echo $_REQUEST['id_disposisi']; ?>" class="btn-large deep-orange waves-effect waves-light">BATAL <i class="material-icons">clear</i></a>
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

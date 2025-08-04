<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        if(isset($_REQUEST['submit'])){
			$id_disposisi=$_REQUEST['id_disposisi'];
			$query = "SELECT * FROM tbl_surat_masuk join tbl_disposisi on tbl_surat_masuk.id_surat = tbl_disposisi.id_surat where id_disposisi='$id_disposisi' ";
			$result = mysqli_query($config, $query);
			$data = mysqli_fetch_array($result);
            $id_tindak_lanjut = $_REQUEST['id_tindak_lanjut'];
            //$query = mysqli_query($config, "SELECT * FROM tbl_surat_masuk WHERE id_surat='$id_surat'");
            //list($id_surat) = mysqli_fetch_array($query);

            //validasi form kosong
            if($_REQUEST['perihal'] == "" || $_REQUEST['isi_tindak_lanjut'] == "" ){
                $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
                echo '<script language="javascript">window.history.back();</script>';
            } else {
				

                //$id_tindak_lanjut = $_REQUEST['id_tindak_lanjut'];
                $isi_tindak_lanjut = $_REQUEST['isi_tindak_lanjut'];
                $perihal = $_REQUEST['perihal'];
                $tgl_disposisi = $data['tgl_disposisi'];
                $id_disposisi = $data['id_disposisi'];
				$id_surat = $data['id_surat'];
                $id_user = $_SESSION['id_user'];
							

                //validasi input data
                if(!preg_match("/^[a-zA-Z0-9.,()%@\/ -]*$/", $perihal)){
                    $_SESSION['perihall'] = 'Form Perihal hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-) garis miring(/), dan kurung()';
                    echo '<script language="javascript">window.history.back();</script>';                   
				} else {

                    if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $isi_tindak_lanjut)){
                        $_SESSION['isi_tindak_lanjutl'] = 'Form Isi Tindak Lanjut hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), dan(&), underscore(_), kurung(), persen(%) dan at(@)';
                        echo '<script language="javascript">window.history.back();</script>';
					} else {
								$ekstensi = array('jpg','png','jpeg','doc','docx','pdf');
                                $file = $_FILES['file']['name'];
                                $x = explode('.', $file);
                                $eks = strtolower(end($x));
                                $ukuran = $_FILES['file']['size'];
                                $target_dir = "upload/tindak_lanjut/";

                                 //jika form file tidak kosong akan mengeksekusi script dibawah ini
                                 If($file != ""){

                                     $rand = rand(1,10000);
                                     $nfile = $rand."-".$file;

                                     //validasi file
                                     if(in_array($eks, $ekstensi) == true){
										if($ukuran < 5220350){

                                            move_uploaded_file($_FILES['file']['tmp_name'], $target_dir.$nfile);

                                            $query = mysqli_query($config, "INSERT INTO tbl_tindak_lanjut(id_tindak_lanjut,perihal,isi_tindak_lanjut,tgl_disposisi,tgl_tindak_lanjut,id_disposisi,id_surat,id_user,file)
											VALUES($id_tindak_lanjut,'$perihal','$isi_tindak_lanjut','$tgl_disposisi',NOW(),'$id_disposisi','$id_surat','$id_user','$nfile')");

                                            if($query == true){
                                                $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
                                                echo '<script language="javascript">
                                                window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
												</script>';
                                                die();
                                            } else {
                                                $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                                                echo '<script language="javascript">window.history.back();</script>';
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
                                    $query = mysqli_query($config, "INSERT INTO tbl_tindak_lanjut(id_tindak_lanjut,perihal,isi_tindak_lanjut,tgl_disposisi,tgl_tindak_lanjut,id_disposisi,id_surat,id_user,file)
									VALUES($id_tindak_lanjut,'$perihal','$isi_tindak_lanjut','$tgl_disposisi',NOW(),'$id_disposisi','$id_surat','$id_user','$nfile')");

                                    if($query == true){
                                        $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
										echo '<script language="javascript">
                                                window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
												</script>';
                                        die();
                                    } else {
                                        $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                                        echo '<script language="javascript">window.history.back();</script>';
                                    }
                                }
                                               
								//$query = mysqli_query($config, "INSERT INTO tbl_tindak_lanjut(perihal,isi_tindak_lanjut,tgl_disposisi,tgl_tindak_lanjut,id_disposisi,id_surat,id_user)
								//VALUES('$perihal','$isi_tindak_lanjut','$tgl_disposisi',NOW(),'$id_disposisi','$id_surat','$id_user')");

                                    //if($query == true){
                                       // $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
                                        //echo '<script language="javascript">
                                          //      window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
                                            //  </script>';
                                    //} else {
										//		$_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
											//	echo '<script language="javascript">window.history.back();</script>';
											//}
							
 
                    }
                }
            }
        } else {?>

            <!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <nav class="secondary-nav">
                        <div class="nav-wrapper blue-grey darken-1">
                            <ul class="left">
                                <li class="waves-effect waves-light"><a href="#" class="judul"><i class="material-icons">description</i> Tambah Tindak Lanjut Surat</a></li>
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
                <form class="col s12" method="post" action="" enctype="multipart/form-data">

                    <!-- Row in form START -->
						<div class="row">
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">looks_one</i>
							<?php
									$q = mysqli_query($config, "SELECT max(id_tindak_lanjut) as idterbesar FROM tbl_tindak_lanjut ");
									$data = mysqli_fetch_array($q);
									$urutan = $data['idterbesar'];
									$id_tindak_lanjut = $urutan+1;
									
                                ?>
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
							<?php
									$id_disposisi = $_REQUEST['id_disposisi'];
									$query = "SELECT * FROM tbl_surat_masuk join tbl_disposisi on tbl_surat_masuk.id_surat = tbl_disposisi.id_surat where id_disposisi='$id_disposisi' ";
									$result = mysqli_query($config, $query);
									$data = mysqli_fetch_assoc($result);
							?>
							<input id="perihal" type="text" class="validate" name="perihal" value="<?php echo $data['perihal'];?>" readonly required>
                                <?php
                                    if(isset($_SESSION['perihall'])){
                                        $perihall = $_SESSION['perihall'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$perihall.'</div>';
                                        unset($_SESSION['perihall']);
                                    }
                                ?>
                            <label for="perihal">Perihal Surat</label>
                        </div>
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">description</i>
                            <textarea id="isi_tindak_lanjut" class="materialize-textarea validate" name="isi_tindak_lanjut" required></textarea>
                                <?php
                                    if(isset($_SESSION['isi_tindak_lanjutl'])){
                                        $isi_tindak_lanjutl = $_SESSION['isi_tindak_lanjutl'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$isi_tindak_lanjutl.'</div>';
                                        unset($_SESSION['isi_tindak_lanjutl']);
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
                                    <input class="file-path validate" type="text" placeholder="Upload file/scan gambar surat masuk">
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
                            <button type="submit" name ="submit" class="btn-large blue waves-effect waves-light">SIMPAN <i class="material-icons">done</i></button>
                        </div>
                        <div class="col 6">
                            <button type="reset" onclick="window.history.back();" class="btn-large deep-orange waves-effect waves-light">BATAL <i class="material-icons">clear</i></button>
                        </div>
                    </div>

                </form>
                <!-- Form END -->

            </div>
            <!-- Row form END -->

<?php
        }
    }
?>

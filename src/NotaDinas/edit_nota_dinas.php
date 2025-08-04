<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        if(isset($_REQUEST['submit'])){

            //validasi form kosong
            if($_REQUEST['asal_notdin'] == "" || $_REQUEST['tuj_notdin'] == "" || $_REQUEST['hal_notdin'] == ""  || $_REQUEST['isi_notdin'] ==  ""){
                $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
                echo '<script language="javascript">window.history.back();</script>';
            } else {
				$id_notdin = $_REQUEST['id_notdin'];
				$asal_notdin = $_REQUEST['asal_notdin'];
                $tuj_notdin = $_REQUEST['tuj_notdin'];
                $hal_notdin = $_REQUEST['hal_notdin'];
                $tgl_notdin = $_REQUEST['tgl_notdin'];
				$isi_notdin = $_REQUEST['isi_notdin'];
                $id_user = $_SESSION['id_user'];

                //validasi input data
        
                        if(!preg_match("/^[a-zA-Z0-9.,() \/ -]*$/", $asal_notdin)){
                            $_SESSION['asal_notdink'] = 'Form Asal hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-),kurung() dan garis miring(/)';
                            echo '<script language="javascript">window.history.back();</script>';
                        } else {

                             if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $tuj_notdin)){
								$_SESSION['tuj_notdin'] = 'Form Tujuan hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-),kurung() dan garis miring(/)';
								echo '<script language="javascript">window.history.back();</script>';
							} else {

                               if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $hal_notdin)){
									$_SESSION['hal_notdin'] = 'Form Perihal hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)';
									echo '<script language="javascript">window.history.back();</script>';
								} else {
                                             if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $isi_notdin)){
												$_SESSION['isi_notdink'] = 'Form Isi Ringkas hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)';
												echo '<script language="javascript">window.history.back();</script>';
											} else {

                                                $ekstensi = array('jpg','png','jpeg','doc','docx','pdf');
                                                $file = $_FILES['file']['name'];
                                                $x = explode('.', $file);
                                                $eks = strtolower(end($x));
                                                $ukuran = $_FILES['file']['size'];
                                                $target_dir = "upload/notdin/";
												
												$id_notdin = $_REQUEST['id_notdin'];
												$query1 = mysqli_query($config, "SELECT file FROM tbl_notdin WHERE id_notdin='$id_notdin'");
												$data1=mysqli_fetch_array($query1);
												$files=$data1['file'];
												

                                            //jika form file tidak kosong akan mengeksekusi script dibawah ini
                                            if($file != ""){

                                                $rand = rand(1,10000);
                                                $nfile = $rand."-".$file;

                                                //validasi file
                                                if(in_array($eks, $ekstensi) == true){
                                                    if($ukuran < 5220350){
                                                        //jika file kosong diganti baru akan mengeksekusi script dibawah ini
                                                        if($files ==""){
                                                            //unlink($target_dir.$file);
                                                            move_uploaded_file($_FILES['file']['tmp_name'], $target_dir.$nfile);

                                                            $query = mysqli_query($config, "UPDATE tbl_notdin SET asal_notdin='$asal_notdin',tuj_notdin='$tuj_notdin',
															hal_notdin='$hal_notdin',isi_notdin='$isi_notdin',file_notdin='$nfile',id_user='$id_user' WHERE id_notdin='$id_notdin'");
                                                            if($query == true){
                                                                $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
                                                                header("Location: ./admin.php?page=not");
                                                                die();
                                                            } else {
                                                                $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                                                                echo '<script language="javascript">window.history.back();</script>';
                                                            }
                                                        } else {
															
                                                            //jika file diganti baru mengeksekusi script dibawah ini
															unlink($target_dir.$file);
                                                            move_uploaded_file($_FILES['file']['tmp_name'], $target_dir.$nfile);

                                                            $query = mysqli_query($config, "UPDATE tbl_notdin SET asal_notdin='$asal_notdin',tuj_notdin='$tuj_notdin',
															hal_notdin='$hal_notdin',isi_notdin='$isi_notdin',file_notdin='$nfile',id_user='$id_user' WHERE id_notdin='$id_notdin'");

                                                            if($query == true){
                                                                $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
                                                                header("Location: ./admin.php?page=not");
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
                                                $id_notdin = $_REQUEST['id_notdin'];

                                                $query = mysqli_query($config, "UPDATE tbl_notdin SET asal_notdin='$asal_notdin',tuj_notdin='$tuj_notdin',
															hal_notdin='$hal_notdin',isi_notdin='$isi_notdin',id_user='$id_user' WHERE id_notdin='$id_notdin'");

                                                if($query == true){
                                                    $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
                                                    header("Location: ./admin.php?page=not");
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
    } else {

        $id_notdin = mysqli_real_escape_string($config, $_REQUEST['id_notdin']);
        $query = mysqli_query($config, "SELECT id_notdin, no_notdin, tuj_notdin,  asal_notdin, tgl_notdin, hal_notdin,  isi_notdin, file_notdin, id_user FROM tbl_notdin WHERE id_notdin='$id_notdin'");
        list($id_notdin, $no_notdin, $tuj_notdin, $asal_notdin, $tgl_notdin, $hal_notdin,  $isi_notdin, $file, $id_user) = mysqli_fetch_array($query);

        if($_SESSION['admin'] == 4){
            echo '<script language="javascript">
                    window.alert("ERROR! Anda tidak memiliki hak akses untuk mengedit data ini");
                    window.location.href="./admin.php?page=not";
                  </script>';
        } else {?>

            <!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <nav class="secondary-nav">
                        <div class="nav-wrapper blue-grey darken-1">
                            <ul class="left">
                                <li class="waves-effect waves-light"><a href="#" class="judul"><i class="material-icons">edit</i> Edit Data Nota Dinas</a></li>
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
                <form class="col s12" method="POST" action="?page=not&act=edit" enctype="multipart/form-data">

                    <!-- Row in form START -->
                    <div class="row">
               
                
						<div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">looks_one</i>
							<input id="id_notdin" type="number" class="validate" value="<?php echo $id_notdin ;?>" name="id_notdin" hidden required>
                            <input id="no_notdin" type="text" class="validate" name="no_notdin" value="<?php echo $no_notdin ;?>" readonly required>
                            <label for="no_notdin">Nomor Nota Dinas</label>
                        </div>
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">place</i>
                            <input id="asal_notdin" type="text" class="validate" name="asal_notdin" value="<?php echo $asal_notdin ;?>" required>
                                <?php
                                    if(isset($_SESSION['asal_notdink'])){
                                        $easal_notdin = $_SESSION['asal_notdink'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$asal_notdink.'</div>';
                                        unset($_SESSION['asal_notdink']);
                                    }
                                ?>
                            <label for="asal_notdin">Asal</label>
                        </div>
						<div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">place</i>
							<textarea id="tuj_notdin" class="materialize-textarea validate" name="tuj_notdin" required><?php echo $tuj_notdin ;?></textarea>
                                <?php
                                    if(isset($_SESSION['tuj_nordink'])){
                                        $tujuan = $_SESSION['tuj_nordink'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$tuj_nordink.'</div>';
                                        unset($_SESSION['tuj_notdink']);
                                    }
                                ?>
                            <label for="tujuan">Tujuan</label>
                        </div>
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">featured_play_list</i>
                            <input id="hal_notdin" type="text" class="validate" name="hal_notdin" value="<?php echo $hal_notdin ;?>" required>
                                <?php
                                    if(isset($_SESSION['hal_notdink'])){
                                        $perihal = $_SESSION['hal_notdink'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$hal_notdink.'</div>';
                                        unset($_SESSION['hal_notdink']);
                                    }
                                ?>
                            <label for="perihal">Perihal</label>
                        </div>
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">date_range</i>
                            <input id="tgl_notdin" type="text" name="tgl_notdin" class="datepicker" value="<?php echo $tgl_notdin ;?>" readonly required>
           
                            <label for="tgl_surat">Tanggal</label>
                        </div>
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">description</i>
                            <textarea id="isi_notdin" class="materialize-textarea validate" name="isi_notdin" required><?php echo $isi_notdin ;?></textarea>
                                <?php
                                    if(isset($_SESSION['isi_notdink'])){
                                        $eisi = $_SESSION['isi_notdink'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$isi_notdink.'</div>';
                                        unset($_SESSION['isi_notdink']);
                                    }
                                ?>
                            <label for="isi">Isi Ringkas</label>
                        </div>
                        
                        <div class="input-field col s6">
                            <div class="file-field input-field tooltipped" data-position="top" data-tooltip="Jika tidak ada file/scan gambar nota dinas, biarkan kosong">
                                <div class="btn light-green darken-1">
                                    <span>File</span>
                                    <input type="file" id="file" name="file">
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" type="text" value="<?php echo $file ;?>" placeholder="Upload file/scan gambar Nota Dinas">
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
                            <a href="?page=not" class="btn-large deep-orange waves-effect waves-light">BATAL <i class="material-icons">clear</i></a>
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

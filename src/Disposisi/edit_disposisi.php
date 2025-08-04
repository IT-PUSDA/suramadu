<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        if(isset($_REQUEST['submit'])){

            $id_surat = $_REQUEST['id_surat'];
            $query = mysqli_query($config, "SELECT * FROM tbl_surat_masuk WHERE id_surat='$id_surat'");
            list($id_surat) = mysqli_fetch_array($query);

            //validasi form kosong
            if($_REQUEST['id_tujuan'] == "" || $_REQUEST['isi_disposisi'] == "" || $_REQUEST['sifat'] == "" 
                || $_REQUEST['catatan'] == ""){
                $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
                echo '<script language="javascript">window.history.back();</script>';
            } else {

                $id_disposisi = $_REQUEST['id_disposisi'];
				$no_agenda = $_REQUEST['no_agenda'];
				$id_tujuan = $_REQUEST['id_tujuan'];
				if($id_tujuan>0)
				{
					$query1 = "SELECT username FROM tbl_user where id_user='$id_tujuan' ";
					$result1 = mysqli_query($config, $query1);
					while($data1 = mysqli_fetch_assoc($result1) ){
						$tujuan_disposisi=$data1['username'];
					}
				}
                //$tujuan_disposisi = $_REQUEST['tujuan_disposisi'];
                $isi_disposisi = $_REQUEST['isi_disposisi'];
                $sifat = $_REQUEST['sifat'];
                $tgl_disposisi = $_REQUEST['tgl_disposisi'];
                $catatan = $_REQUEST['catatan'];
                $id_user = $_SESSION['id_user'];

                //validasi input data
                if(!preg_match("/^[a-zA-Z0-9.,()\/ -]*$/", $tujuan_disposisi)){
                    $_SESSION['tujuan_disposisi'] = 'Form Tujuan Disposisi hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,) minus(-). kurung() dan garis miring(/)';
                    echo '<script language="javascript">window.history.back();</script>';
                } else {

                    if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $isi_disposisi)){
                        $_SESSION['isi_disposisi'] = 'Form Isi Disposisi hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), dan(&), underscore(_), kurung(), persen(%) dan at(@)';
                        echo '<script language="javascript">window.history.back();</script>';
                    } else {

                        if(!preg_match("/^[a-zA-Z0 ]*$/", $sifat)){
                            $_SESSION['catatan'] = 'Form SIFAT hanya boleh mengandung karakter huruf dan spasi';
                            echo '<script language="javascript">window.history.back();</script>';
                            }  else {

                            if(!preg_match("/^[a-zA-Z0-9.,()%@\/ -]*$/", $catatan)){
                                $_SESSION['catatan'] = 'Form catatan hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-) garis miring(/), dan kurung()';
                                echo '<script language="javascript">window.history.back();</script>';
                            } else {

                                if(!preg_match("/^[a-zA-Z0 ]*$/", $sifat)){
                                    $_SESSION['catatan'] = 'Form SIFAT hanya boleh mengandung karakter huruf dan spasi';
                                    echo '<script language="javascript">window.history.back();</script>';
                                } else {

                                    $query = mysqli_query($config, "UPDATE tbl_disposisi SET tujuan_disposisi='$tujuan_disposisi', isi_disposisi='$isi_disposisi', sifat='$sifat', catatan='$catatan', id_user='$id_user' WHERE id_disposisi='$id_disposisi'");

                                    if($query == true){
                                        $_SESSION['succEdit'] = 'SUKSES! Data berhasil diupdate';
                                        echo '<script language="javascript">
                                                window.location.href="./admin.php?page=tsm&act=disp&id_surat='.$id_surat.'";
                                              </script>';
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

            $id_disposisi = mysqli_real_escape_string($config, $_REQUEST['id_disposisi']);
            $query = mysqli_query($config, "SELECT * FROM tbl_disposisi WHERE id_disposisi='$id_disposisi'");
            if(mysqli_num_rows($query) > 0){
                //$no = 1;
                $row = mysqli_fetch_array($query)
				?>

                <!-- Row Start -->
                <div class="row">
                    <!-- Secondary Nav START -->
                    <div class="col s12">
                        <nav class="secondary-nav">
                            <div class="nav-wrapper blue-grey darken-1">
                                <ul class="left">
                                    <li class="waves-effect waves-light"><a href="#" class="judul"><i class="material-icons">edit</i> Edit Disposisi Surat</a></li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                    <!-- Secondary Nav END -->
                </div>
                <!-- Row END -->

                <?php
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
                ?>

                <!-- Row form Start -->
                <div class="row jarak-form">

                    <!-- Form START -->
                    <form class="col s12" method="post" action="">

                        <!-- Row in form START -->
							<div class="row">
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">looks_one</i>
							<input id="id_disposisi" type="number" class="validate" name="id_disposisi" value="<?php echo $row['id_disposisi']; ?>" hidden required>
							<input id="no_agenda" type="text" class="validate" name="no_agenda" value="<?php echo $row['no_agenda']; ?>" readonly required>
							<?php
									if(isset($_SESSION['no_agendad'])){
                                    $no_agendad = $_SESSION['no_agendad'];
                                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$no_agendad.'</div>';
                                    unset($_SESSION['no_agendad']);
                                    }
							?>
                            <label for="no_agenda">Nomor Agenda</label>
                        </div>
						
						 <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">place</i><label>Pilih Tujuan Disposisi</label><br/>
                            <div class="input-field col s11 right">
                                <select class="browser-default validate" name="id_tujuan" id="id_tujuan" required>
									<option value="<?php echo $row['id_tujuan']; ?>"><?php echo $row['tujuan_disposisi']; ?></option>
                                    <?php
									$query = "SELECT * FROM tbl_user where id_user>'1' ";
									$result = mysqli_query($config, $query);
									while($data = mysqli_fetch_assoc($result) ){
									?>
									<option value="<?php echo $data['id_user']; ?>"><?php echo $data['username']; ?></option>
                                    <?php
									}
									?>
                                </select>
                            </div>
                            <?php
                                if(isset($_SESSION['tujuan_disposisid'])){
                                    $tujuan_disposisid = $_SESSION['tujuan_disposisid'];
                                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$tujuan_disposisid.'</div>';
                                    unset($_SESSION['tujuan_disposisid']);
                                }
                            ?>
                        </div>
                        
						
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">description</i>
                            <textarea id="isi_disposisi" class="materialize-textarea validate" name="isi_disposisi" required><?php echo $row['isi_disposisi']; ?></textarea>
                                <?php
                                    if(isset($_SESSION['isi_disposisid'])){
                                        $isi_disposisid = $_SESSION['isi_disposisid'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$isi_disposisid.'</div>';
                                        unset($_SESSION['isi_disposisid']);
                                    }
                                ?>
                            <label for="isi_disposisi">Isi Disposisi</label>
                        </div>
                        
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">low_priority</i><label>Pilih Sifat Disposisi</label><br/>
                            <div class="input-field col s11 right">
                                <select class="browser-default validate" name="sifat" id="sifat" required>
								<option value="<?php echo $row['sifat']; ?>"><?php echo $row['sifat']; ?></option>
                                    <option value="Biasa">Biasa</option>
                                    <option value="Penting">Penting</option>
                                    <option value="Segera">Segera</option>
                                    <option value="Rahasia">Rahasia</option>
                                </select>
                            </div>
                            <?php
                                if(isset($_SESSION['sifat'])){
                                    $sifat = $_SESSION['sifat'];
                                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$sifat.'</div>';
                                    unset($_SESSION['sifat']);
                                }
                            ?>
                        </div>
						<div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">featured_play_list   </i>
                            <input id="catatan" type="text" class="validate" name="catatan" value="<?php echo $row['catatan']; ?>" required>
                                <?php
                                    if(isset($_SESSION['catatand'])){
                                        $catatand = $_SESSION['catatand'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$catatand.'</div>';
                                        unset($_SESSION['catatand']);
                                    }
                                ?>
                            <label for="catatan">Catatan</label>
                        </div>
                    </div>
                            
                        <!-- Row in form END -->

                        <div class="row">
                            <div class="col 6">
                                <button type="submit" name ="submit" class="btn-large blue waves-effect waves-light">SIMPAN <i class="material-icons">done</i></button>
                            </div>
                            <div class="col 6">
                                <a href="?page=tsm&act=disp&id_surat=<?php echo $row['id_surat']; ?>" class="btn-large deep-orange waves-effect waves-light">BATAL <i class="material-icons">clear</i></a>
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

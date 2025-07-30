<script src="assets/js/moment-with-locales.js"></script>
<script src="assets/js/jquery-1.11.3.min.js"></script>
<script src="assets/js/bootstrap-datetimepicker.js"></script>
<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        if(isset($_REQUEST['submit'])){
            //validasi form kosong
            if($_REQUEST['asal_notdin'] == "" || $_REQUEST['tuj_notdin'] == "" || $_REQUEST['hal_notdin'] == "" || $_REQUEST['tgl_notdin'] == "" || $_REQUEST['isi_notdin'] ==  "")
			{
                $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
                echo '<script language="javascript">window.history.back();</script>';
            } else {
                $asal_notdin = $_REQUEST['asal_notdin'];
                $tuj_notdin = $_REQUEST['tuj_notdin'];
                $hal_notdin = $_REQUEST['hal_notdin'];
                $tgl_notdin = $_REQUEST['tgl_notdin'];
				$isi_notdin = $_REQUEST['isi_notdin'];
                $id_user = $_SESSION['id_user'];
				$q = mysqli_query($config, "SELECT id_notdin,no_notdin,tgl_notdin FROM tbl_notdin  where id_notdin in (select max(id_notdin) from tbl_notdin)");
				$data = mysqli_fetch_array($q);
				if(isset($data['no_notdin']))
				{
					$no_notdin_k = $data['no_notdin'];
					$no = substr($no_notdin_k,0,1);
					if($no == 0)
					{
						$urut = substr($no_notdin_k,-1,1) +1;
						$no_notdin = '0'.$urut;
					}
					else
					{
						$urut = $no_notdin_k + 1;
						$no_notdin = $urut;
					}	
				} 
				else
				{
					$no_notdin='01';
				}
                //validasi input data
                        if(!preg_match("/^[a-zA-Z0-9.,() \/ -]*$/", $asal_notdin)){
                            $_SESSION['asal_notdink'] = 'Form Asal hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-),kurung() dan garis miring(/)';
                            echo '<script language="javascript">window.history.back();</script>';
                        } else {

                           if(!preg_match("/^[a-zA-Z0-9.,() \/ -]*$/", $tuj_notdin)){
                            $_SESSION['tuj_notdink'] = 'Form Tujuan hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-),kurung() dan garis miring(/)';
                            echo '<script language="javascript">window.history.back();</script>';
							} else {

								if(!preg_match("/^[a-zA-Z0-9.,() \/ -]*$/", $hal_notdin)){
								$_SESSION['hal_notdink'] = 'Form Perihal hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-),kurung() dan garis miring(/)';
								echo '<script language="javascript">window.history.back();</script>';
								} else {

                                    if(!preg_match("/^[0-9.-]*$/", $tgl_notdin)){
										$_SESSION['tgl_notdink'] = 'Form Tanggal hanya boleh mengandung angka dan minus(-)';
                                        echo '<script language="javascript">window.history.back();</script>';
                                        } else {

                                            if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $isi_notdin)){
												$_SESSION['isi_notdink'] = 'Form Isi Ringkas hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)';
												echo '<script language="javascript">window.history.back();</script>';
												} else {

                                                $cek = mysqli_query($config, "SELECT * FROM tbl_notdin WHERE no_notdin='$no_notdin'");
                                                $result = mysqli_num_rows($cek);

                                                if($result > 0){
                                                    $_SESSION['errDup'] = 'Nomor sudah terpakai, gunakan yang lain!';
                                                    echo '<script language="javascript">window.history.back();</script>';
                                                } else {

                                                    $ekstensi = array('jpg','png','jpeg','doc','docx','pdf');
                                                $file = $_FILES['file']['name'];
                                                $x = explode('.', $file);
                                                $eks = strtolower(end($x));
                                                $ukuran = $_FILES['file']['size'];
                                                $target_dir = "upload/notdin/";

                                                    //jika form file tidak kosong akan mengeksekusi script dibawah ini
                                                    if($file != ""){
                                                        $rand = rand(1,10000);
                                                        $nfile = $rand."-".$file;

                                                        //validasi file
                                                        if(in_array($eks, $ekstensi) == true){
                                                            if($ukuran < 5220350){

                                                                move_uploaded_file($_FILES['file']['tmp_name'], $target_dir.$nfile);

                                                                $query = mysqli_query($config, "INSERT INTO tbl_notdin(no_notdin,tuj_notdin,asal_notdin, tgl_notdin,hal_notdin
                                                                   ,isi_notdin,file_notdin,id_user)
                                                                        VALUES('$no_notdin','$tuj_notdin','$asal_notdin','$tgl_notdin','$hal_notdin','$isi_notdin','$nfile','$id_user')");

                                                                if($query == true){
                                                                    $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
                                                                    header("Location: ./admin.php?page=not");
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
                                                        $query = mysqli_query($config, "INSERT INTO tbl_notdin(no_notdin,asal_notdin,tuj_notdin,hal_notdin,
                                                                    tgl_notdin,isi_notdin,file_notdin,id_user)
                                                                        VALUES('$no_notdin','$asal_notdin','$tuj_notdin','$hal_notdin','$tgl_notdin','$isi_notdin','$nfile','$id_user')");

                                                        if($query == true){
                                                            $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
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
                                <li class="waves-effect waves-light"><a href="?page=tsm&act=add" class="judul"><i class="material-icons">mail</i> Tambah Data Nota Dinas</a></li>
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
                <form class="col s12" method="POST" action="?page=not&act=add" enctype="multipart/form-data">

                    <!-- Row in form START -->
                    <div class="row">
						<div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">featured_play_list</i>
                            <input id="hal_notdin" type="text" class="validate" name="hal_notdin" required>
                                <?php
                                    if(isset($_SESSION['hal_notdink'])){
                                        $keterangan = $_SESSION['hal_notdink'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$hal_notdink.'</div>';
                                        unset($_SESSION['hal_notdink']);
                                    }
                                ?>
                            <label for="perihal">Perihal</label>
                        </div>
						<div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">place</i>
                            <textarea id="tuj_notdin" class="materialize-textarea validate" name="tuj_notdin" required></textarea>
                                <?php
                                    if(isset($_SESSION['tujuank'])){
                                        $asal_surat = $_SESSION['tujuank'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$tujuank.'</div>';
                                        unset($_SESSION['tujuank']);
                                    }
                                ?>
                            <label for="asal_surat">Tujuan</label>
                        </div>
						<div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">date_range</i>
                            <input id="tgl_surat" type="text" name="tgl_notdin" class="datepicker" required>
                                <?php
                                    if(isset($_SESSION['tgl_notdin'])){
                                        $tgl_suratk = $_SESSION['tgl_notdin'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$tgl_notdink.'</div>';
                                        unset($_SESSION['tgl_notdink']);
                                    }
                                ?>
                            <label for="tgl_surat">Tanggal Surat</label>
                        </div>
                       <div class="input-field col s6">  
							<i class="material-icons prefix md-prefix">low_priority</i><label>Asal Surat</label><br/>
                            <div class="input-field col s11 right">
                                <select name="asal_notdin" class="browser-grey validate" id="asal_notdin" required>
                                    <option value="SEKRETARIAT">Sekretariat</option>
                                    <option value="PSDA">PSDA</option>
                                    <option value="SWP">SWP</option>
                                    <option value="IRIGASI">Irigasi</option>
									<option value="BINFAT">Binfat</option>
                                </select>
                            </div>
                                <?php
                                    if(isset($_SESSION['asal_notdink'])){
                                        $bidangk = $_SESSION['asal_notdink'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$asal_notdink.'</div>';
                                        unset($_SESSION['asal_notdink']);
                                    }
                                ?>
                        </div>
                        
				
                        
                        						
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">description</i>
                            <textarea id="isi_notdin" class="materialize-textarea validate" name="isi_notdin" required></textarea>
                                <?php
                                    if(isset($_SESSION['isi_notdink'])){
                                        $isi = $_SESSION['isi_notdink'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$isi_notdink.'</div>';
                                        unset($_SESSION['isi_notdink']);
                                    }
                                ?>
                            <label for="isi">Isi Ringkas</label>
                        </div>
                        
                        <div class="input-field col s6">
                            <div class="file-field input-field tooltipped" data-position="top" data-tooltip="Jika tidak ada file/scan gambar surat, biarkan kosong">
                                <div class="btn light-green darken-1">
                                    <span>File</span>
                                    <input type="file" id="file" name="file">
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" type="text" placeholder="Upload file/scan gambar nota dinas">
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
                            <a href="?page=tsm" class="btn-large deep-orange waves-effect waves-light">BATAL <i class="material-icons">clear</i></a>
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

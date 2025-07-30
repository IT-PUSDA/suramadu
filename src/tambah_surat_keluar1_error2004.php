<script src="assets/js/moment-with-locales.js"></script>
<script src="assets/js/jquery-1.11.3.min.js"></script>
<script src="assets/js/bootstrap-datetimepicker.js"></script>
<?php
    //cek session
	
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else{
		if(empty($_REQUEST['tgl_surat'])){
        $_SESSION['errEmpty'] = 'ERROR! Tanggal wajib diisi';
        echo '<script language="javascript">window.history.back();</script>';
        }
		$tglx = $_POST['tgl_surat'];
        if(isset($_REQUEST['submit'])){

            //validasi form kosong
            if($_REQUEST['kode'] == "" || $_REQUEST['no_surat'] == "" || $_REQUEST['perihal'] == ""
                || $_REQUEST['tujuan'] == "" || $_REQUEST['tgl_surat'] == ""  || $_REQUEST['isi'] == ""){
                $_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi';
                echo '<script language="javascript">window.history.back();</script>';
            } else {
				$id_surat=$_REQUEST['id_surat'];
                $no_agenda = $_REQUEST['no_agenda'];
                $nkode = $_REQUEST['kode'];
				$no_surat = $_REQUEST['no_surat'];
				$perihal = $_REQUEST['perihal'];
                $tujuan = $_REQUEST['tujuan'];
				$tgl_surat = $_REQUEST['tgl_surat'];
                $isi = $_REQUEST['isi'];
                $id_user = $_SESSION['id_user'];
				$bidang = $_REQUEST['bidang'];
				

                //validasi input data
                if(!preg_match("/^[a-zA-Z0-9., ]*$/", $nkode)){
                    $_SESSION['kodek'] = 'Form Kode Klasifikasi hanya boleh mengandung karakter huruf, angka, spasi, titik(.) dan koma(,)';
                    echo '<script language="javascript">window.history.back();</script>';
                } else {

                    if(!preg_match("/^[a-zA-Z0-9.\/ -]*$/", $no_surat)){
                        $_SESSION['no_suratk'] = 'Form No Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), minus(-) dan garis miring(/)';
                        echo '<script language="javascript">window.history.back();</script>';
                    } else {

                        if(!preg_match("/^[a-zA-Z0-9.,() \/ -]*$/", $perihal)){
                            $_SESSION['perihal'] = 'Form Perihal Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), kurung() dan garis miring(/)';
                            echo '<script language="javascript">window.history.back();</script>';
                        } else {

                            if(!preg_match("/^[a-zA-Z0-9.,() \/ -]*$/", $tujuan)){
                            $_SESSION['tujuan'] = 'Form Tujuan Surat hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), kurung() dan garis miring(/)';
								echo '<script language="javascript">window.history.back();</script>';
							} else {

                                    if(!preg_match("/^[0-9.-]*$/", $tgl_surat)){
                                        $_SESSION['tgl_suratk'] = 'Form Tanggal Surat hanya boleh mengandung angka dan minus(-)';
                                        echo '<script language="javascript">window.history.back();</script>';
                                    } else {

                                        if(!preg_match("/^[a-zA-Z0-9.,_()%&@\/\r\n -]*$/", $isi)){
											$_SESSION['isik'] = 'Form Isi Ringkas hanya boleh mengandung karakter huruf, angka, spasi, titik(.), koma(,), minus(-), garis miring(/), kurung(), underscore(_), dan(&) persen(%) dan at(@)';
											echo '<script language="javascript">window.history.back();</script>';
										} else {

                                            $cek = mysqli_query($config, "SELECT * FROM tbl_surat_keluar WHERE no_surat='$no_surat'");
                                            $result = mysqli_num_rows($cek);

                                            if($result > 0){
                                                $_SESSION['errDup'] = 'Nomor Surat sudah terpakai, gunakan yang lain!';
                                                echo '<script language="javascript">window.history.back();</script>';
                                            } else {

                                                $ekstensi = array('jpg','png','jpeg','doc','docx','pdf');
                                                $file = $_FILES['file']['name'];
                                                $x = explode('.', $file);
                                                $eks = strtolower(end($x));
                                                $ukuran = $_FILES['file']['size'];
                                                $target_dir = "upload/surat_keluar/";

                                                //jika form file tidak kosong akan mengekse
                                                if($file != ""){

                                                    $rand = rand(1,10000);
                                                    $nfile = $rand."-".$file;
                                                    if(in_array($eks, $ekstensi) == true){
                                                        if($ukuran < 5220350){

                                                            move_uploaded_file($_FILES['file']['tmp_name'], $target_dir.$nfile);

                                                            $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,
                                                                isi,file,id_user,bidang)
                                                                VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang')");

                                                            if($query == true){
                                                                $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
                                                                header("Location: ./admin.php?page=tsk");
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
                                                    $query = mysqli_query($config, "INSERT INTO tbl_surat_keluar(id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,
                                                                isi,file,id_user,bidang)
                                                                VALUES('$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang')");

                                                    if($query == true){
                                                        $_SESSION['succAdd'] = 'SUKSES! Data berhasil ditambahkan';
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
            }
        } else {?>

            <!-- Row Start -->
            <div class="row">
                <!-- Secondary Nav START -->
                <div class="col s12">
                    <nav class="secondary-nav">
                        <div class="nav-wrapper blue-grey darken-1">
                            <ul class="left">
                                <li class="waves-effect waves-light"><a href="?page=tsk&act=add" class="judul"><i class="material-icons">drafts</i> Tambah Data Surat Keluar</a></li>
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
								
							<?php
									$tanggal2 = new DateTime($tglx);
									$id_next = mysqli_query($config, "SELECT max(id_surat) as id_next FROM tbl_surat_keluar where tgl_surat='$tglx' "); //menentukan max id_surat dari tgl srt
									$data_id = mysqli_fetch_array($id_next);
									$idn = $data_id['id_next'];
									$q = mysqli_query($config, "SELECT no_agenda FROM tbl_surat_keluar where tgl_surat='$tglx' and id_surat='$idn'");
									$data = mysqli_fetch_array($q);
									$q1 = mysqli_query($config, "SELECT max(id_surat) as urut FROM tbl_surat_keluar"); //menentukan id_surat next
									$data1 = mysqli_fetch_array($q1);
									$urutan = $data1['urut'];
									$id_surat = $urutan+1;
									$year = substr($tglx,0,4);
									$tanggal1 = new DateTime($year."-01-01");
									$tgl_selisih = $tanggal2->diff($tanggal1)->format("%a");
									if($tgl_selisih<10)
									{
										$tgls="0".$tgl_selisih;
										if(isset($data['no_agenda']))
										{
											$no_agenda_k=$data['no_agenda'];
											$no_agenda_in=strlen($no_agenda_k);
											if($no_agenda_in>6){
												$no_agenda_urut=((substr($no_agenda_k,-3)))+1;
												$no_lembar=substr($no_agenda_k,0,strpos($no_agenda_k,"-"));
												if($no_agenda_urut<10)
												{
													$no_agenda=$no_lembar."-5".$no_agenda_urut;
												} else
													{
														$no_agenda=$no_lembar."-".$no_agenda_urut;
													}
											} else
												{
													$no_agenda_urut=((substr($no_agenda_k,-2)))+1;
													$no_lembar=substr($no_agenda_k,0,strpos($no_agenda_k,"-"));
													if($no_agenda_urut<10)
													{
														$no_agenda=$no_lembar."-5".$no_agenda_urut;
													} else
														{
															$no_agenda=$no_lembar."-".$no_agenda_urut;
														}
												}
											
										 } else
											{
												$no_agenda_urut="50";
												//$no_lembar=floatval((substr($no_agenda_k,0,strpos($no_agenda_k,"-"))))+1;
												$no_agenda=$tgls."-".$no_agenda_urut;
											}	
									} else
									{
										$tgls=$tgl_selisih;
										if(isset($data['no_agenda']))
										{
											$no_agenda_k=$data['no_agenda'];
											$no_agenda_in=strlen($no_agenda_k);
											if($no_agenda_in>6){
												$no_agenda_urut=((substr($no_agenda_k,-3)))+1;
												$no_lembar=substr($no_agenda_k,0,strpos($no_agenda_k,"-"));
												if($no_agenda_urut<10)
												{
													$no_agenda=$no_lembar."-5".$no_agenda_urut;
												} else
													{
														$no_agenda=$no_lembar."-".$no_agenda_urut;
													}
											} else
												{
													$no_agenda_urut=((substr($no_agenda_k,-2)))+1;
													$no_lembar=substr($no_agenda_k,0,strpos($no_agenda_k,"-"));
													if($no_agenda_urut<10)
													{
														$no_agenda=$no_lembar."-5".$no_agenda_urut;
													} else
														{
															$no_agenda=$no_lembar."-".$no_agenda_urut;
														}
												}
											
										 } else
											{
												$no_agenda_urut="50";
												//$no_lembar=floatval((substr($no_agenda_k,0,strpos($no_agenda_k,"-"))))+1;
												$no_agenda=$tgls."-".$no_agenda_urut;
											}
									}
									
								?>
								
							<input id="id_surat" type="number" class="validate" name="id_surat"  value='<?php echo $id_surat;?>' hidden required>
                            <input id="no_agenda" type="text" class="validate" name="no_agenda"  value='<?php echo $no_agenda;?>' readonly required>
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
                            <input id="kode" type="text" class="validate" name="kode" required>
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
                            <input id="no_surat" type="text" class="validate" name="no_surat" required>
                                <?php
                                    if(isset($_SESSION['no_suratk'])){
                                        $no_suratk = $_SESSION['no_suratk'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$no_suratk.'</div>';
                                        unset($_SESSION['no_suratk']);
                                    }
                                    if(isset($_SESSION['errDup'])){
                                        $errDup = $_SESSION['errDup'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$errDup.'</div>';
                                        unset($_SESSION['errDup']);
                                    }
                                ?>
                            <label for="no_surat">Nomor Surat</label>
                        </div>
						<div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">featured_play_list</i>
                            <input id="perihal" type="text" class="validate" name="perihal" required>
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
                            <input id="tujuan" type="text" class="validate" name="tujuan" required>
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
                            <input type="date" name="tgl_surat" value="<?php echo $tglx;?>" class="datepicker" required>
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
                            <textarea id="isi" class="materialize-textarea validate" name="isi" required></textarea>
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
							<?php
									$id_user=$_SESSION['id_user'];
									$query1 = "SELECT * FROM tbl_user where id_user='$id_user' ";
									$result = mysqli_query($config, $query1);
									while($data1 = mysqli_fetch_assoc($result) )
									{
										$bidang = $data1['username'];
									}
									?>
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
                                    <input class="file-path validate" type="text" placeholder="Upload file/scan gambar surat keluar">
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
                                    <small class="red-text">*Format file yang diperbolehkan hanya *.PDF dan ukuran maksimal file 2 MB!</small>
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
						<!--
						<div class="col 6">
                            <a href="?page=gtsk" class="btn-large deep-orange waves-effect waves-light">Google Calendar <i class="material-icons">clear</i></a>
							
						</div>						
					-->
					</div>

                </form>
                <!-- Form END -->

            </div>
            <!-- Row form END -->

<?php
        }
    }
	
?>


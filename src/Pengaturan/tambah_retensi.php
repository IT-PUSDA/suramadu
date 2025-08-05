<?php
    //cek session
    if(empty($_SESSION['admin']))
	{
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else 
		{
			if(isset($_REQUEST['submit']))
			{
				//validasi form kosong
				if($_REQUEST['jenis_surat'] == "" || $_REQUEST['masa_inaktif'] == "")
				{
					$_SESSION['errEmpty'] = 'ERROR! Semua form wajib diisi!';
					header("Location: ./admin.php?page=sett&sub=ret&act=add");
					die();
				} else 
					{
						$jenis_surat = $_REQUEST['jenis_surat'];
						$masa_inaktif = $_REQUEST['masa_inaktif'];
						//validasi input data
						if(!preg_match("/^[a-zA-Z0-9 ]*$/", $jenis_surat))
						{
							$_SESSION['jenis_surat'] = 'Form jenis surat hanya boleh mengandung karakter huruf dan angka';
							echo '<script language="javascript">window.history.back();</script>';
						} else 
							{
								if(!preg_match("/^[a-zA-Z0-9 ]*$/", $masa_inaktif))
								{
									$_SESSION['masa_inaktif'] = 'Form masa inaktif hanya boleh mengandung karakter huruf dan angka';
									echo '<script language="javascript">window.history.back();</script>';
								}  else 
									{
										$cek = mysqli_query($config, "SELECT * FROM tbl_inaktif WHERE jenis_surat='$jenis_surat'");
										$result = mysqli_num_rows($cek);
										if($result > 0)
										{
											$_SESSION['errJenissurat'] = 'Jenis surat sudah ada, gunakan yang lain!';
											echo '<script language="javascript">window.history.back();</script>';
										} else 
											{
												if(strlen($jenis_surat) < 5)
												{
													$_SESSION['errJenissurat5'] = 'Jenis surat minimal 5 karakter!';
													echo '<script language="javascript">window.history.back();</script>';
												} else 
													{
														if(strlen($masa_inaktif) < 5)
														{
															$_SESSION['errMasainaktif'] = 'Masa inaktif minimal 5 karakter!';
															echo '<script language="javascript">window.history.back();</script>';
														} else 
															{
																$query = mysqli_query($config, "INSERT INTO tbl_inaktif(jenis_surat,masa_inaktif) VALUES('$jenis_surat','$masa_inaktif')");
																if($query != false)
																{
																	$_SESSION['succAdd'] = 'SUKSES! Jadwal Retensi baru berhasil ditambahkan';
																	header("Location: ./admin.php?page=sett&sub=ret");
																	die();
																} else 
																	{
																		$_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
																		echo '<script language="javascript">window.history.back();</script>';
																	}
															}
													}
											}
									}
							}
                    }
                
			} else 
				{?>
					<!-- Row Start -->
					<div class="row">
					<!-- Secondary Nav START -->
						<div class="col s12">
							<nav class="secondary-nav">
								<div class="nav-wrapper blue-grey darken-1">
									<ul class="left">
										<li class="waves-effect waves-light"><a href="?page=sett&sub=ret&act=add" class="judul"><i class="material-icons">add</i> Tambah Jadwal Retensi</a></li>
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
                <form class="col s12" method="post" action="?page=sett&sub=ret&act=add">

                    <!-- Row in form START -->
                    <div class="row">
                        <div class="input-field col s6 tooltipped" data-position="top" data-tooltip="Jenis surat minimal 5 karakter [ huruf dan angka ]">
                            <i class="material-icons prefix md-prefix">text_fields</i>
                            <input id="jenis_surat" type="text" class="validate" name="jenis_surat" required>
                                <?php
                                    if(isset($_SESSION['jenis_surat'])){
                                        $jenis_surat = $_SESSION['jenis_surat'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$jenis_surat.'</div>';
                                        unset($_SESSION['jenis_surat']);
                                    }
                                    if(isset($_SESSION['errJenissurat'])){
                                        $errUsername = $_SESSION['errJenissurat'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$errJenissurat.'</div>';
                                        unset($_SESSION['errJenissurat']);
                                    }
                                    if(isset($_SESSION['errJenissurat5'])){
                                        $errUser5 = $_SESSION['errJenissurat5'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$errJenissurat5.'</div>';
                                        unset($_SESSION['errJenissurat5']);
                                    }
                                ?>
                            <label for="jenis_surat">Jenis Surat</label>
                        </div>
                        <div class="input-field col s6">
                            <i class="material-icons prefix md-prefix">text_fields</i>
                            <input id="masa_inaktif" type="text" class="validate" name="masa_inaktif" required>
                                <?php
                                    if(isset($_SESSION['masa_inaktif'])){
                                        $masa_inaktif = $_SESSION['masa_inaktif'];
                                        echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$masa_inaktif.'</div>';
                                        unset($_SESSION['masa_inaktif']);
                                    }
                                ?>
                            <label for="masa_inaktif">Masa Inaktif</label>
                        </div>
                       
                      
                    </div>
                    <br/>
                    <!-- Row in form END -->
                    <div class="row">
                        <div class="col 6">
                            <button type="submit" name="submit" class="btn-large blue waves-effect waves-light">SIMPAN <i class="material-icons">done</i></button>
                        </div>
                        <div class="col 6">
                            <a href="?page=sett&sub=ret" class="btn-large deep-orange waves-effect waves-light">BATAL <i class="material-icons">clear</i></a>
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

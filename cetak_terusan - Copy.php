<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<strong>ERROR!</strong> Anda harus login terlebih dahulu.';
        header("Location: ./");
        die();
    } else {

        echo '
        <style type="text/css">
            table {
                background: #fff;
                padding: 5px;
				
            }
			tepi{
                border-style: double;
            }
            tr, td {
                border: table-cell;
                border: 1px  solid #444;
            }
            tr,td {
                vertical-align: top!important;
            }
            #right {
                border-right: none !important;
            }
            #left {
                border-left: none !important;
            }
            .isi {
                height: 700px!important;
				font-size: 20px;
            }
            .ter {
                text-align: center;
                padding: 1.5rem 0;
                margin-bottom: .5rem;
            }
            .logoter {
                float: left;
                position: relative;
                width: 110px;
                height: 110px;
                margin: 0 0 0 1rem;
            }
            #lead {
                width: auto;
                position: relative;
                margin: 25px 0 0 75%;
            }
            .lead {
                font-weight: bold;
                text-decoration: underline;
                margin-bottom: -10px;
            }
			.kiri {
                width: 25%;
				border-right-style:hidden;
				table-layout: fixed;
			}
			.kiri_tgh {
				width: 25%;
			}
			.kanan {
				width: 25%;
				border-right-style:hidden;
				table-layout: fixed;
			}
			.kanan_tgh {
				width: 25%;
			}
            .tgh {
                text-align: center;
            }
            #nama {
                font-size: 2.1rem;
                margin-bottom: -1rem;
            }
            #kota {
                font-size: 16px;
            }
            .up {
                text-transform: uppercase;
                margin: 0;
                line-height: 2.2rem;
                font-size: 30px;
            }
            .alamat {
                margin: 0;
                font-size: 1.3rem;
                margin-bottom: .5rem;
            }
            #lbr {
                font-size: 30px;
                font-weight: bold;
            }
            .separator {
                border-bottom: 2px solid #616161;
                margin: -1.3rem 0 1.5rem;
            }
            @media print{
                body {
                    font-size: 12px;
                    color: #212121;
                }
                table {
                    width: 100%;
                    font-size: 12px;
                    color: #212121;
                }
                tr, td {
                    border: table-cell;
                    border: 1px  solid #444;
                    padding: 8px!important;

                }
                tr,td {
                    vertical-align: top!important;
                }
                #lbr {
                    font-size: 20px;
                }
                .isi {
                    height: 700px!important;
                }
				.kiri {
                    width: 25%;
					table-layout: fixed;
					border-right-style:hidden;
                }
				.kiri_tgh {
                    width: 25%;
					table-layout: fixed;
                }
				.kanan {
                    width: 25%;
					table-layout: fixed;
					border-right-style:hidden;
                }
				.kanan_tgh {
                    widht: 25%;
					table-layout: fixed;
                }
                .tgh {
                    text-align: center;
                }
                .ter {
                    text-align: center;
                    margin: -.5rem 0;
                }
                .logoter {
                    float: left;
                    position: relative;
                    width: 80px;
                    height: 80px;
                    margin: .5rem 0 0 .5rem;
                }
                #lead {
                    width: auto;
                    position: relative;
                    margin: 15px 0 0 75%;
                }
                .lead {
                    font-weight: bold;
                    text-decoration: underline;
                    margin-bottom: -10px;
                }
                #nama {
                    font-size: 20px!important;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin: -10px 0 -20px 0;
                }
                .up {
                    font-size: 17px!important;
                    font-weight: normal;
                }
                .alamat {
                    font-size: 17px!important;
                    font-weight: normal;
                    margin-bottom: -.1rem;
                }
                #kota {
                    margin-top: -15px;
                    font-size: 13px;
                }
                #lbr {
                    font-size: 17px;
                    font-weight: bold;
                }
                .separator {
                    border-bottom: 2px solid #616161;
                    margin: -1rem 0 1rem;
                }

            }
        </style>

        <body onload="window.print()">

        <!-- Container START -->
        <div class="container">
            <div id="colres">
                <div class="ter">';
                    echo '
					
					';
                    echo '
				
                </div>';
                //<div class="separator"></div>';

                $id_surat = mysqli_real_escape_string($config, $_REQUEST['id_surat']);
                $query = mysqli_query($config, "SELECT * FROM tbl_surat_masuk WHERE id_surat='$id_surat'");

                if(mysqli_num_rows($query) > 0){
                $no = 0;
                while($row = mysqli_fetch_array($query)){

                echo '
                    <table class="bordered" id="tbl" width="1200px">
                        <tbody border="double">
						  <tr>
                                <td class="tgh" id="lbr" colspan="6">';
					$query2 = mysqli_query($config, "SELECT institusi, nama, alamat, kota, logo FROM tbl_instansi");
					$id_user = $row['id_user'];
					$q = mysqli_query($config, "SELECT id_user,username FROM tbl_user  where id_user='$id_user' ");
					$data = mysqli_fetch_array($q);
                    list($institusi, $nama) = mysqli_fetch_array($query2);
        
                    if(!empty($institusi)){
                        echo '<h5 class="up"><strong>'.$institusi.'</strong></h5>';
                    } else {
                        echo '<h5 class="up">APLIKASI MANAJEMEN</h5>';
                    }
                    if(!empty($nama)){
                        echo '<h5 class="up" id="nama"><strong>'.$nama.'</strong></h5><br/>';
                    } else {
                        echo '<h5 class="up" id="nama">SURAT MASUK DAN SURAT KELUAR</h5><br/>';
                    }
              
					echo'
					</td>
					</tr>
                            <tr>
                                <td class="tgh" id="lbr" colspan="6">LEMBAR DISPOSISI</td>
                            </tr>
                           
                            <tr>';

                                $y = substr($row['tgl_surat'],0,4);
                                $m = substr($row['tgl_surat'],5,2);
                                $d = substr($row['tgl_surat'],8,2);
								
								if($m == "01"){
                                    $nm = "Januari";
                                } elseif($m == "02"){
                                    $nm = "Februari";
                                } elseif($m == "03"){
                                    $nm = "Maret";
                                } elseif($m == "04"){
                                    $nm = "April";
                                } elseif($m == "05"){
                                    $nm = "Mei";
                                } elseif($m == "06"){
                                    $nm = "Juni";
                                } elseif($m == "07"){
                                    $nm = "Juli";
                                } elseif($m == "08"){
                                    $nm = "Agustus";
                                } elseif($m == "09"){
                                    $nm = "September";
                                } elseif($m == "10"){
                                    $nm = "Oktober";
                                } elseif($m == "11"){
                                    $nm = "November";
                                } elseif($m == "12"){
                                    $nm = "Desember";
                                }
								
								$yy = substr($row['tgl_diterima'],0,4);
                                $mm = substr($row['tgl_diterima'],5,2);
                                $dd = substr($row['tgl_diterima'],8,2);
								
								if($mm == "01"){
                                    $nmm = "Januari";
                                } elseif($mm == "02"){
                                    $nmm = "Februari";
                                } elseif($mm == "03"){
                                    $nmm = "Maret";
                                } elseif($mm == "04"){
                                    $nmm = "April";
                                } elseif($mm == "05"){
                                    $nmm = "Mei";
                                } elseif($mm == "06"){
                                    $nmm = "Juni";
                                } elseif($mm == "07"){
                                    $nmm = "Juli";
                                } elseif($mm == "08"){
                                    $nmm = "Agustus";
                                } elseif($mm == "09"){
                                    $nmm = "September";
                                } elseif($mm == "10"){
                                    $nmm = "Oktober";
                                } elseif($mm == "11"){
                                    $nmm = "November";
                                } elseif($mm == "12"){
                                    $nmm = "Desember";
                                }
                                
                                echo '

                                <td class="kiri"><strong>Surat Dari</strong></td>
                                <td class="kiri_tgh">: '.$row['asal_surat'].'</td>
								<td class="kanan"><strong>Diterima Tanggal</strong></td>
								<td class="kanan_tgh">: '.$dd." ".$nmm." ".$yy.'</td>
                            </tr>
                            <tr>
                                <td class="kiri"><strong>Tanggal Surat</strong></td>
                                <td class="kiri_tgh">: '.$d." ".$nm." ".$y.'</td>
								<td class="kanan"><strong>Nomor Agenda</strong></td>
                                <td class="kanan_tgh">: '.$row['no_agenda'].'</td>
                            </tr>
                            <tr>
                                <td class="kiri"><strong>Nomor Surat</strong></td>
                                <td class="kiri_tgh">: '.$row['no_surat'].'</td>
								<td class="kanan"><strong>Diteruskan</strong></td>';
								$q2 = mysqli_query($config, "SELECT * FROM tbl_user join tbl_teruskan on tbl_user.id_user = tbl_teruskan.id_user where tbl_teruskan.id_surat='$id_surat' ");
								$data2 = mysqli_fetch_array($q2);
								echo' <td class="kanan_tgh">: Yth. '.$data2['tujuan_terusan'].'</td>';
									
							echo'		
                            </tr>
                            <tr>
                                <td class="kiri"><strong>Perihal</strong></td>
                                <td class="kiri_tgh">: '.$row['perihal'].'</td>
								<td colspan="2">1. Bidang Bina Manfaat <br/>
																2. Bidang Perencanaan Sumber Daya Air <br/>
																3. Bidang irigasi <br/>
																4. Bidang Sungai, Waduk dan Pantai <br/>
																5. Kepala UPT 	..................... <br/>
																6. Kasubag		..................... <br/>
																7. Sekpri
								</td>';
								$id_ter=$_REQUEST['id_ter'];
								$query3 = mysqli_query($config, "SELECT * FROM tbl_teruskan JOIN tbl_surat_masuk ON tbl_teruskan.id_surat = tbl_surat_masuk.id_surat 
								WHERE tbl_teruskan.id_surat='$id_surat' and tbl_teruskan.id_teruskan='$id_ter'");
							}
                            if(mysqli_num_rows($query3) > 0){
                                $no = 0;
                                $row = mysqli_fetch_array($query3);{
								$paraf = ucwords($data2['tujuan_terusan']);
                                echo '
								
                                
                            </tr>
                            <tr>
                            <tr class="isi">
                                <td colspan="6">
									<div>
										<strong><center>ISI DISPOSISI :</center></strong>
									</div>
                                </td>
                            </tr>
							<tr>
								
							</tr>
			</table>
			<table>
							<tr>
								<td width="400px">
                                    <strong>Paraf '.ucwords(strtolower($paraf)).'</strong> :
								</td>';
								
								$yyy = substr($row['tgl_terusan'],0,4);
                                $mmm = substr($row['tgl_terusan'],5,2);
                                $ddd = substr($row['tgl_terusan'],8,2);
								
								if($mmm == "01"){
                                    $nmmm = "Januari";
                                } elseif($mmm == "02"){
                                    $nmmm = "Februari";
                                } elseif($mmm == "03"){
                                    $nmmm = "Maret";
                                } elseif($mmm == "04"){
                                    $nmmm = "April";
                                } elseif($mmm == "05"){
                                    $nmmm = "Mei";
                                } elseif($mmm == "06"){
                                    $nmmm = "Juni";
                                } elseif($mmm == "07"){
                                    $nmmm = "Juli";
                                } elseif($mmm == "08"){
                                    $nmmm = "Agustus";
                                } elseif($mmm == "09"){
                                    $nmmm = "September";
                                } elseif($mmm == "10"){
                                    $nmmm = "Oktober";
                                } elseif($mmm == "11"){
                                    $nmmm = "November";
                                } elseif($mmm == "12"){
                                    $nmmm = "Desember";
                                }
								echo'
								<td width ="400px"><strong>Tgl Disposisi</strong> : </td>
								<td width="400px"><strong>Sifat</strong> : </td>
							</tr>
							';
                                
                            } 
                        } echo '
                </tbody>
            </table>
           
        </div>
        <div class="jarak2"></div>
    </div>
    <!-- Container END -->

    </body>';
    }
}
?>

<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        if(isset($_REQUEST['sub'])){
            $sub = $_REQUEST['sub'];
            switch ($sub) {
                case 'add':
                    include "tambah_tindak_lanjut.php";
                    break;
                case 'edit':
                    include "edit_tindak_lanjut.php";
                    break;
                case 'del':
                    include "hapus_tindak_lanjut.php";
                    break;
				case 'ftl':
                    include "file_tl.php";
                    break;
            }
        } else {

            //pagging
            $limit = 5;
            $pg = @$_GET['pg'];
                if(empty($pg)){
                    $curr = 0;
                    $pg = 1;
                } else {
                    $curr = ($pg - 1) * $limit;
                }

                $id_disposisi = $_REQUEST['id_disposisi'];

                $query = mysqli_query($config, "SELECT * FROM tbl_disposisi join tbl_surat_masuk on tbl_disposisi.id_surat=tbl_surat_masuk.id_surat WHERE id_disposisi='$id_disposisi'");

                if(mysqli_num_rows($query) > 0){
                    $no = 1;
                    while($row = mysqli_fetch_array($query)){

                    if($_SESSION['id_user'] == 3){
                        echo '<script language="javascript">
                                window.alert("ERROR! Anda tidak memiliki hak akses untuk melihat data ini");
                                window.location.href="./admin.php?page=tsm";
                              </script>';
                    } else {

                      echo '<!-- Row Start -->
                            <div class="row">
                                <!-- Secondary Nav START -->
                                <div class="col s12">
                                    <div class="z-depth-1">
                                        <nav class="secondary-nav">
                                            <div class="nav-wrapper blue-grey darken-1">
                                                <div class="col m12">
                                                    <ul class="left">
                                                        <li class="waves-effect waves-light hide-on-small-only"><a href="#" class="judul"><i class="material-icons">description</i> Tindak Lanjut Surat</a></li>
                                                        <li class="waves-effect waves-light">';
														if($_SESSION['admin'] ==1 OR $_SESSION['admin']== 4){
                                                            echo '<a href="?page=tsm&act=tlanjut&id_disposisi='.$row['id_disposisi'].'&sub=add"><i class="material-icons md-24">add_circle</i> Tambah Tindak Lanjut</a>';
															if($_SESSION['admin']== 4){
																	echo '<li class="waves-effect waves-light hide-on-small-only"><a href="?page=tsm"><i class="material-icons">arrow_back</i> Kembali</a></li>';
																}
															}else {
															?>
															<?php
															echo'
														</li>
                                                        <li class="waves-effect waves-light hide-on-small-only"><a href="?page=tsm"><i class="material-icons">arrow_back</i> Kembali</a></li>';
																}
														echo'
													</ul>
                                                </div>
                                            </div>
                                        </nav>
                                    </div>
                                </div>
                                <!-- Secondary Nav END -->
                            </div>
                            <!-- Row END -->

                            <!-- Perihal START -->
                            <div class="col s12">
                                <div class="card blue lighten-5">
                                    <div class="card-content">
                                        <p><p class="description">Perihal Surat:</p>'.$row['perihal'].'</p>
                                    </div>
                                </div>
                            </div>
                            <!-- Perihal END -->';

                            if(isset($_SESSION['succAdd'])){
                                $succAdd = $_SESSION['succAdd'];
                                echo '<div id="alert-message" class="row">
                                        <div class="col m12">
                                            <div class="card green lighten-5">
                                                <div class="card-content notif">
                                                    <span class="card-title green-text"><i class="material-icons md-36">done</i> '.$succAdd.'</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                                unset($_SESSION['succAdd']);
                            }
                            if(isset($_SESSION['succEdit'])){
                                $succEdit = $_SESSION['succEdit'];
                                echo '<div id="alert-message" class="row">
                                        <div class="col m12">
                                            <div class="card green lighten-5">
                                                <div class="card-content notif">
                                                    <span class="card-title green-text"><i class="material-icons md-36">done</i> '.$succEdit.'</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                                unset($_SESSION['succEdit']);
                            }
                            if(isset($_SESSION['succDel'])){
                                $succDel = $_SESSION['succDel'];
                                echo '<div id="alert-message" class="row">
                                        <div class="col m12">
                                            <div class="card green lighten-5">
                                                <div class="card-content notif">
                                                    <span class="card-title green-text"><i class="material-icons md-36">done</i> '.$succDel.'</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                                unset($_SESSION['succDel']);
                            }

                            echo '
                            <!-- Row form Start -->
                            <div class="row jarak-form">

                                <div class="col m12" id="colres">
                                    <table class="bordered" id="tbl">
                                        <thead class="blue lighten-4" id="head">
                                            <tr>
                                                <th width="6%">No</th>
												<th width="10%">Tujuan Disposisi</th>
                                                <th width="22%">Isi Disposisi</th>
                                                <th width="22%">Isi Tindak Lanjut</br>File</th>
                                                <th width="24%">Tanggal Disposisi<br/>Tanggal Tindak Lanjut</th>
                                                <th width="16%">Tindakan</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr>';
										$id_user = $_SESSION['id_user'];
										if($_SESSION['admin']==4){
											$query2 = mysqli_query($config, "SELECT * FROM tbl_tindak_lanjut JOIN tbl_disposisi 
											ON tbl_tindak_lanjut.id_disposisi = tbl_disposisi.id_disposisi WHERE tbl_tindak_lanjut.id_user='$id_user' and tbl_tindak_lanjut.id_disposisi='$id_disposisi'");
										} else {
											$query2 = mysqli_query($config, "SELECT * FROM tbl_tindak_lanjut JOIN tbl_disposisi 
											ON tbl_tindak_lanjut.id_disposisi = tbl_disposisi.id_disposisi WHERE tbl_tindak_lanjut.id_disposisi='$id_disposisi'");
										}
                                        if(mysqli_num_rows($query2) > 0){
                                            $no = 0;
                                            while($row = mysqli_fetch_array($query2)){
                                            $no++;
											 $id_tl=$row['id_tindak_lanjut'];
											 $query3 = mysqli_query($config, "SELECT username FROM tbl_tindak_lanjut join tbl_user on tbl_tindak_lanjut.id_user=tbl_user.id_user where id_tindak_lanjut=$id_tl");
                                             $row3 = mysqli_fetch_array($query3);
											 echo ' <td>'.$no.'</td>
													<td>'.$row3['username'].'</td>
                                                    <td>'.$row['isi_disposisi'].'</td>
                                                    <td>'.substr($row['isi_tindak_lanjut'],0,200).'<br/><br/><strong>File :</strong>';
															if(!empty($row['file'])){
																echo ' <strong><a href="?page=tsm&act=tlanjut&sub=ftl&id_tindak_lanjut='.$row['id_tindak_lanjut'].'">'.$row['file'].'</a></strong>';
															} else {
																echo '<em>Tidak ada file yang di upload</em>';
															} echo '
													</td>';

                                                    $y = substr($row['tgl_disposisi'],0,4);
                                                    $m = substr($row['tgl_disposisi'],5,2);
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
                                                    $d = substr($row['tgl_disposisi'],8,2);
													
													$yy = substr($row['tgl_tindak_lanjut'],0,4);
                                                    $mm = substr($row['tgl_tindak_lanjut'],5,2);
													if($mm == "01"){
                                                        $nm = "Januari";
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
                                                    $dd = substr($row['tgl_tindak_lanjut'],8,2);
													                                                    
                                                    echo '

                                                    <td>'.$d." ".$nm." ".$y.'</br>'.$dd." ".$nmm." ".$yy.'</td>
													<td>';
													if($_SESSION['admin'] ==1 OR $_SESSION['admin']== 4){
														echo'
														<a class="btn small blue waves-effect waves-light" href="?page=tsm&act=tlanjut&sub=edit&id_tindak_lanjut='.$row['id_tindak_lanjut'].'&id_disposisi='.$row['id_disposisi'].'">
                                                            <i class="material-icons">edit</i> EDIT</a>
															<a class="btn small deep-orange waves-effect waves-light" href="?page=tsm&act=tlanjut&sub=del&id_tindak_lanjut='.$row['id_tindak_lanjut'].'&id_disposisi='.$row['id_disposisi'].'">
															<i class="material-icons">delete</i> DEL</a>';
														} else {
															echo' <button class="btn small blue-grey waves-effect waves-light"><i class="material-icons">error</i> No Action</button>';
															}
														echo '</td>';
														?>
														
                                            </tr>
                                        </tbody>
										<?php
                                            }
                                        } else {
                                            echo '<tr><td colspan="5"><center><p class="add">Tidak ada data untuk ditampilkan. <u>';
											if($_SESSION['admin'] ==1 OR $_SESSION['admin']== 4){
												echo'<a href="?page=tsm&act=tlanjut&id_disposisi='.$row['id_disposisi'].'&sub=add">Tambah data baru</a>';
												}
										?>
										
											</u></p></center></td></tr>
										<?php	
                                        }
										
                                echo '</table>
                                </div>
                            </div>
                            <!-- Row form END -->';
                    }
                }
            }
        }
    }
?>

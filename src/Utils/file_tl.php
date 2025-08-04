<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        $id_tindak_lanjut = mysqli_real_escape_string($config, $_REQUEST['id_tindak_lanjut']);
        $query = mysqli_query($config, "SELECT * FROM tbl_tindak_lanjut WHERE id_tindak_lanjut='$id_tindak_lanjut'");
        if(mysqli_num_rows($query) > 0){
            while($row = mysqli_fetch_array($query)){
                echo '
                    <div class="row jarak-form">
                        <ul class="collapsible white" data-collapsible="accordion">
                            <li>
                                <div class="collapsible-header white"><i class="material-icons md-prefix md-36">expand_more</i><span class="add">Tampilkan detail data tindak lanjut</span></div>
                                    <div class="collapsible-body white">
                                        <div class="col m12 white">
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td width="13%">No. Tindak Lanjut</td>
                                                        <td width="1%">:</td>
                                                        <td width="86%">'.$row['id_tindak_lanjut'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td width="13%">Perihal</td>
                                                        <td width="1%">:</td>
                                                        <td width="86%">'.$row['perihal'].'</td>
                                                    </tr>
                                                    <td width="13%">Isi Tindak Lanjut</td>
                                                    <td width="1%">:</td>
                                                    <td width="86%">'.$row['isi_tindak_lanjut'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td width="13%">Tanggal Disposisi</td>
                                                        <td width="1%">:</td>';
                                                        
                                                        $y = substr($row['tgl_disposisi'],0,4);
                                                        $m = substr($row['tgl_disposisi'],5,2);
                                                        $d = substr($row['tgl_disposisi'],8,2);

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
                                                        echo '

                                                        <td width="86%">'.$d." ".$nm." ".$y.'</td>                                                    </tr>
                                                    <tr>
													<tr>
                                                        <td width="13%">Tanggal Tindak Lanjut</td>
                                                        <td width="1%">:</td>';
                                                        
                                                        $yy = substr($row['tgl_tindak_lanjut'],0,4);
                                                        $mm = substr($row['tgl_tindak_lanjut'],5,2);
                                                        $dd = substr($row['tgl_tindak_lanjut'],8,2);

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

                                                        <td width="86%">'.$dd." ".$nmm." ".$yy.'</td>                                                    </tr>
                                                    
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>

                        <button onclick="window.history.back()" class="btn-large blue waves-effect waves-light left"><i class="material-icons">arrow_back</i> KEMBALI</button>';

                        if(empty($row['file'])){
                            echo '';
                        } else {

                            $ekstensi = array('jpg','png','jpeg');
                            $ekstensi2 = array('doc','docx');
                            $file = $row['file'];
                            $x = explode('.', $file);
                            $eks = strtolower(end($x));

                            if(in_array($eks, $ekstensi) == true){
                                echo '<img class="gbr" data-caption="'.date('d M Y', strtotime($row['tgl_tindak_lanjut'])).'" src="./upload/tindak_lanjut/'.$row['file'].'"/>';
                            } else {

                                if(in_array($eks, $ekstensi2) == true){
                                    echo '
                                    <div class="gbr">
                                        <div class="row">
                                            <div class="col s12">
                                                <div class="col s9 left">
                                                    <div class="card">
                                                        <div class="card-content">
                                                            <p>File lampiran surat masuk ini bertipe <strong>document</strong>, silakan klik link dibawah ini untuk melihat file lampiran tersebut.</p>
                                                        </div>
                                                        <div class="card-action">
                                                            <strong>Lihat file :</strong> <a class="blue-text" href="./upload/tindak_lanjut/'.$row['file'].'" target="_blank">'.$row['file'].'</a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col s3 right">
                                                    <img class="file" src="./asset/img/word.png">
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                                } else {
                                    echo '
                                    <div class="gbr">
                                        <div class="row">
                                            <div class="col s12">
                                                <div class="col s9 left">
                                                    <div class="card">
                                                        <div class="card-content">
                                                            <p>File lampiran surat masuk ini bertipe <strong>PDF</strong>, silakan klik link dibawah ini untuk melihat file lampiran tersebut.</p>
                                                        </div>
                                                        <div class="card-action">
                                                            <strong>Lihat file :</strong> <a class="blue-text" href="./upload/tindak_lanjut/'.$row['file'].'" target="_blank">'.$row['file'].'</a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col s3 right">
                                                    <img class="file" src="./asset/img/pdf.png">
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                                }
                            }
                        } echo '
                    </div>';
            }
        }
    }
?>

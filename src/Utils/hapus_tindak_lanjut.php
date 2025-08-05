<?php
    //cek session
    if(empty($_SESSION['admin'])){
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else {

        if(isset($_SESSION['errQ'])){
            $errQ = $_SESSION['errQ'];
            echo '<div id="alert-message" class="row jarak-card">
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

    	$id_tindak_lanjut = mysqli_real_escape_string($config, $_REQUEST['id_tindak_lanjut']);
		$id_disposisi=$_REQUEST['id_disposisi'];
    	$query = mysqli_query($config, "SELECT * FROM tbl_tindak_lanjut WHERE id_tindak_lanjut='$id_tindak_lanjut'");

    	if(mysqli_num_rows($query) > 0){
            $no = 1;
            while($row = mysqli_fetch_array($query)){

            if($_SESSION['admin'] == 2 OR $_SESSION['admin'] == 3){
                echo '<script language="javascript">
                        window.alert("ERROR! Anda tidak memiliki hak akses untuk menghapus data ini");
                        window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
                      </script>';
            } else {

    		  echo '
                <!-- Row form Start -->
				<div class="row jarak-card">
				    <div class="col m12">
                    <div class="card">
                        <div class="card-content">
				        <table>
				            <thead class="red lighten-5 red-text">
				                <div class="confir red-text"><i class="material-icons md-36">error_outline</i>
				                Apakah Anda yakin akan menghapus data ini?</div>
				            </thead>

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
								<tr>
    			                    <td width="13%">Isi Tindak Lanjut</td>
    			                    <td width="1%">:</td>
    			                    <td width="86%">'.$row['isi_tindak_lanjut'].'</td>
    			                </tr>
								<tr>
    			                    <td width="13%">Tanggal Tindak Lanjut</td>
    			                    <td width="1%">:</td>
    			                    <td width="86%">'.$tgl = date('d M Y ', strtotime($row['tgl_tindak_lanjut'])).'</td>
    			                </tr>
    			                <tr>
    			                    <td width="13%">File</td>
    			                    <td width="1%">:</td>
    			                    <td width="86%">';
                                    if(!empty($row['file'])){
                                        echo ' <a class="blue-text" href="?page=tsm&act=tlanjut&id_tindak_lanjut='.$row['id_tindak_lanjut'].'">'.$row['file'].'</a>';
										
                                    } else {
                                        echo ' Tidak ada file yang diupload';
                                    } echo '</td>
    			                </tr>
    			            </tbody>
    			   		</table>
                        </div>
                        <div class="card-action">
        	                <a href="?page=tsm&act=tlanjut&id_disposisi='.$row['id_disposisi'].'&sub=del&submit=yes&id_tindak_lanjut='.$row['id_tindak_lanjut'].'" class="btn-large deep-orange waves-effect waves-light white-text">HAPUS <i class="material-icons">delete</i></a>
        		                    <a href="?page=tsm&act=disp&id_disposisi='.$row['id_disposisi'].'" class="btn-large blue waves-effect waves-light white-text">BATAL <i class="material-icons">clear</i></a>
    	                </div>
    	            </div>
                </div>
            </div>
            <!-- Row form END -->';

            	if(isset($_REQUEST['submit'])){
            		//jika ada file akan mengekseskusi script dibawah ini
                    if(!empty($row['file'])){
                        unlink("upload/tindak_lanjut/".$row['file']);
                        $query = mysqli_query($config, "DELETE FROM tbl_tindak_lanjut WHERE id_tindak_lanjut='$id_tindak_lanjut'");
                        

                		if($query == true){
                            $_SESSION['succDel'] = 'SUKSES! Data berhasil dihapus<br/>';
                            echo '<script language="javascript">
									window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
									</script>';
                            die();
                		} else {
                            $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                            echo '<script language="javascript">
										window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
										</script>';
                		}
                	} else {

                        //jika tidak ada file akan mengekseskusi script dibawah ini
                        $query = mysqli_query($config, "DELETE FROM tbl_tindak_lanjut WHERE id_tindak_lanjut='$id_tindak_lanjut'");
                      
                        if($query == true){
                            $_SESSION['succDel'] = 'SUKSES! Data berhasil dihapus<br/>';
                            echo '<script language="javascript">
									window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
									</script>';
                            die();
                        } else {
                            $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                            echo '<script language="javascript">
										window.location.href="./admin.php?page=tsm&act=tlanjut&id_disposisi='.$id_disposisi.'";
										</script>';
                        }
                    }
                }
    	    }
        }
    }
}
?>

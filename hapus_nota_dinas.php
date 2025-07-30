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

    	$id_notdin = mysqli_real_escape_string($config, $_REQUEST['id_notdin']);
    	$query = mysqli_query($config, "SELECT * FROM tbl_notdin WHERE id_notdin='$id_notdin'");

    	if(mysqli_num_rows($query) > 0){
            $no = 1;
            while($row = mysqli_fetch_array($query)){

            if($_SESSION['admin'] == 4){
                echo '<script language="javascript">
                        window.alert("ERROR! Anda tidak memiliki hak akses untuk menghapus data ini");
                        window.location.href="./admin.php?page=not";
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
				                    <td width="13%">No. Nota Dinas</td>
				                    <td width="1%">:</td>
				                    <td width="86%">'.$row['no_notdin'].'</td>
				                </tr>
							
								<tr>
    			                    <td width="13%">Asal</td>
    			                    <td width="1%">:</td>
    			                    <td width="86%">'.$row['asal_notdin'].'</td>
    			                </tr>
								<tr>
    			                    <td width="13%">Tujuan</td>
    			                    <td width="1%">:</td>
    			                    <td width="86%">'.$row['tuj_notdin'].'</td>
    			                </tr>
								<tr>
    			                    <td width="13%">Perihal</td>
    			                    <td width="1%">:</td>
    			                    <td width="86%">'.$row['hal_notdin'].'</td>
    			                </tr>
								<tr>
    			                    <td width="13%">Tanggal</td>
    			                    <td width="1%">:</td>
    			                    <td width="86%">'.$tgl = date('d M Y ', strtotime($row['tgl_notdin'])).'</td>
    			                </tr>
                                <tr>
    		                    <td width="13%">Isi</td>
    		                    <td width="1%">:</td>
    		                    <td width="86%">'.$row['isi_notdin'].'</td>
    			                </tr>
    			                <tr>
    			                    <td width="13%">File</td>
    			                    <td width="1%">:</td>
    			                    <td width="86%">';
                                    if(!empty($row['file_notdin'])){
                                        echo ' <a class="blue-text" href="./upload/notdin/'.$row['file_notdin'].'">'.$row['file_notdin'].'</a>';
                                    } else {
                                        echo ' Tidak ada file yang diupload';
                                    } echo '</td>
    			                </tr>
    			            </tbody>
    			   		</table>
                        </div>
                        <div class="card-action">
        	                <a href="?page=not&act=del&submit=yes&id_notdin='.$row['id_notdin'].'" class="btn-large deep-orange waves-effect waves-light white-text">HAPUS <i class="material-icons">delete</i></a>
        	                <a href="?page=not" class="btn-large blue waves-effect waves-light white-text">BATAL <i class="material-icons">clear</i></a>
    	                </div>
    	            </div>
                </div>
            </div>
            <!-- Row form END -->';

            	if(isset($_REQUEST['submit'])){
            		$id_notdin = $_REQUEST['id_notdin'];

                    //jika ada file akan mengekseskusi script dibawah ini
                    if(!empty($row['file_notdin'])){
                        unlink("upload/notdin/".$row['file_notdin']);
                        $query = mysqli_query($config, "DELETE FROM tbl_notdin WHERE id_notdin='$id_notdin'");

                		if($query == true){
                            $_SESSION['succDel'] = 'SUKSES! Data berhasil dihapus<br/>';
                            header("Location: ./admin.php?page=not");
                            die();
                		} else {
                            $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                            echo '<script language="javascript">
                                    window.location.href="./admin.php?page=not&act=del&id_notdin='.$id_notdin.'";
                                  </script>';
                		}
                	} else {

                        //jika tidak ada file akan mengekseskusi script dibawah ini
                        $query = mysqli_query($config, "DELETE FROM tbl_notdin WHERE id_notdin='$id_notdin'");
               
						
                        if($query == true){
                            $_SESSION['succDel'] = 'SUKSES! Data berhasil dihapus<br/>';
                            header("Location: ./admin.php?page=not");
                            die();
                        } else {
                            $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                            echo '<script language="javascript">
                                    window.location.href="./admin.php?page=not&act=del&id_notdin='.$id_notdin.'";
                                  </script>';
                        }
                    }
                }
    	    }
        }
    }
}
?>

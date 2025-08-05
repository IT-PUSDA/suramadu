<?php
    //cek session
    if(empty($_SESSION['admin']))
	{
        $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
        header("Location: ./");
        die();
    } else 
		{
					$jenis_surat = $_REQUEST['jenis_surat'];
                    $query = mysqli_query($config, "DELETE FROM tbl_inaktif WHERE jenis_surat='$jenis_surat'");
                	if($query == true)
					{
                            $_SESSION['succDel'] = 'SUKSES! Jadwal retensi berhasil dihapus<br/>';
                            header("Location: ./admin.php?page=sett&sub=ret");
                            die();
                	} else 
						{
                            $_SESSION['errQ'] = 'ERROR! Ada masalah dengan query';
                            echo '<script language="javascript">
									window.location.href="./admin.php?page=sett&sub=ret&act=del&jenis_surat='.$jenis_surat.'";
									</script>';
                		}
                
    	}
    	
?>

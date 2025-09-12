<?php
// Return JSON list of archive berkas for current operator
if(!isset($_SESSION)) session_start();
require_once __DIR__.'/../include/config.php';
header('Content-Type: application/json');
if((int)$_SESSION['admin']!==3){ echo json_encode([]); exit; }
$uid = (int)$_SESSION['id_user'];
// Ensure table exists (safety)
@mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_arsip_berkas (
	id INT AUTO_INCREMENT PRIMARY KEY,
	id_user INT NOT NULL,
	kode_klasifikasi VARCHAR(50) NOT NULL,
	nama_berkas VARCHAR(255) NOT NULL,
	uraian TEXT NULL,
	file_path VARCHAR(255) NULL,
	tgl_buat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_user (id_user), INDEX idx_kode (kode_klasifikasi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$res = mysqli_query($config,"SELECT id,kode_klasifikasi,nama_berkas,uraian FROM tbl_arsip_berkas WHERE id_user=$uid ORDER BY tgl_buat DESC LIMIT 200");
$out=[]; if($res){ while($r=mysqli_fetch_assoc($res)){ $out[]=$r; } }
echo json_encode($out);

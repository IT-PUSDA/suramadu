<?php
// Return JSON list of archive berkas for current operator
if(!isset($_SESSION)) session_start();
require_once __DIR__.'/../include/config.php';
header('Content-Type: application/json');
if((int)$_SESSION['admin']!==3){ echo json_encode([]); exit; }
$uid = (int)$_SESSION['id_user'];
$res = mysqli_query($config,"SELECT id,kode_klasifikasi,nama_berkas,uraian FROM tbl_arsip_berkas WHERE id_user=$uid ORDER BY tgl_buat DESC LIMIT 200");
$out=[]; if($res){ while($r=mysqli_fetch_assoc($res)){ $out[]=$r; } }
echo json_encode($out);

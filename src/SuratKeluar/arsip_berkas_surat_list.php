<?php
// Return HTML list of surat dalam berkas arsip tertentu (Operator only)
if(!isset($_SESSION)) session_start();
require_once __DIR__.'/../include/config.php';
header('Content-Type: text/html; charset=utf-8');
if((int)$_SESSION['admin']!==3){ http_response_code(403); echo '<div class="red-text" style="padding:8px 12px;">Akses ditolak.</div>'; exit; }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id<1){ echo '<div style="padding:8px 12px;">Parameter tidak valid.</div>'; exit; }
// Pastikan berkas milik operator ini
$uid = (int)$_SESSION['id_user'];
$cek = mysqli_query($config, "SELECT id FROM tbl_arsip_berkas WHERE id=$id AND id_user=$uid LIMIT 1");
if(!$cek || mysqli_num_rows($cek)!==1){ echo '<div style="padding:8px 12px;">Berkas tidak ditemukan atau bukan milik Anda.</div>'; exit; }

// Pastikan relasi ada (hindari duplicate column error)
$hasRel = false;
$rc = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'id_arsip_berkas'");
if ($rc && mysqli_num_rows($rc) === 1) { $hasRel = true; }
if (!$hasRel) { @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL"); }

$sql = "SELECT id_surat,no_surat,isi,file,status,tgl_surat FROM tbl_surat_keluar WHERE id_arsip_berkas=$id AND status='finished' ORDER BY id_surat DESC LIMIT 300";
$res = mysqli_query($config,$sql);
if(!$res || mysqli_num_rows($res)===0){ echo '<div style="padding:8px 12px;">Belum ada surat pada berkas ini.</div>'; exit; }

echo '<div class="collection" style="margin:0;">';
while($r = mysqli_fetch_assoc($res)){
    $no = htmlspecialchars($r['no_surat']??'', ENT_QUOTES, 'UTF-8');
    $isi = htmlspecialchars($r['isi']??'', ENT_QUOTES, 'UTF-8');
    $id_surat = (int)$r['id_surat'];
    $tgl = htmlspecialchars($r['tgl_surat']??'', ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars($r['status']??'', ENT_QUOTES, 'UTF-8');
    $file = htmlspecialchars($r['file']??'', ENT_QUOTES, 'UTF-8');
     echo '<div class="collection-item" style="display:block; padding:10px 12px; border-bottom:1px solid #eceff1;">'
       . '<div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">'
       .   '<div style="flex:1 1 auto; min-width:0;">'
       .     '<strong style="display:block;">'.($no !== '' ? $no : 'Tanpa Nomor').'</strong>'
       .     '<span style="display:block; color:#455a64;">'.($isi!==''? $isi : '-').'</span>'
       .     '<small class="grey-text">'.($tgl!==''?$tgl:'').($status!==''?' • '.$status:'').'</small>'
       .   '</div>'
       .   '<div style="flex:0 0 auto; white-space:nowrap;">'
       .     ($file!==''? '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat='.$id_surat.'" target="_blank" class="btn-flat" style="color:#0277bd;">Lihat</a>' : '<span class="grey-text">-</span>')
       .   '</div>'
       . '</div>'
         . '</div>';
}
echo '</div>';

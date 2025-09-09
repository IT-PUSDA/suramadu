<?php
ob_start();
session_start();
require_once __DIR__ . '/../include/config.php';
// Batasi error agar tidak bocor ke output JSON
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'msg'=>'Unauthorized']);
    exit;
}

$is_operator = ($_SESSION['admin'] == 3); // hanya operator
if(!$is_operator){
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'Forbidden']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if($id <= 0){
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'Bad request']);
    exit;
}

// pastikan kolom ada
$cols = [];
$colRes = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar");
if($colRes){
    while($c = mysqli_fetch_assoc($colRes)){
        $cols[] = $c['Field'];
    }
}
$toAdd=[];
if(!in_array('status',$cols)) $toAdd[] = "ADD COLUMN status ENUM('draft','finished') NOT NULL DEFAULT 'draft'";
if(!in_array('updated_by',$cols)) $toAdd[] = "ADD COLUMN updated_by VARCHAR(50) NULL";
if(!in_array('updated_at',$cols)) $toAdd[] = "ADD COLUMN updated_at DATETIME NULL";
if(!empty($toAdd)) { @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ".implode(', ',$toAdd)); }

// Ambil status sekarang
$res = mysqli_query($config, "SELECT status FROM tbl_surat_keluar WHERE id_surat=$id");
if(!$res || mysqli_num_rows($res)==0){
    http_response_code(404);
    echo json_encode(['ok'=>false,'msg'=>'Data not found']);
    exit;
}
$row = mysqli_fetch_assoc($res);
$current = $row['status'] ?: 'draft';
$new = ($current == 'finished') ? 'draft' : 'finished';

$uname = mysqli_real_escape_string($config, $_SESSION['username']);
$now = date('Y-m-d H:i:s');
if(!mysqli_query($config, "UPDATE tbl_surat_keluar SET status='$new', updated_by='$uname', updated_at='$now' WHERE id_surat=$id")){
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>'Update failed']);
    exit;
}

// Bersihkan buffer sebelum kirim JSON final
@ob_clean();
echo json_encode([
    'ok'=>true,
    'id'=>$id,
    'status'=>$new,
    'updated_at'=>date('d/m/Y H:i', strtotime($now)),
    'updated_by'=>$uname
]);
exit; // pastikan tidak ada output tambahan

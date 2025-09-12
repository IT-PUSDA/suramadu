<?php
// Centralized AJAX endpoint to assign surat to an arsip berkas
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../include/config.php';

function json_out($arr){ echo json_encode($arr); exit; }

if (empty($_SESSION['admin'])) {
    http_response_code(401);
    json_out(['ok'=>false,'error'=>'Sesi berakhir. Silakan login kembali.']);
}

$level = (int)$_SESSION['admin'];
if ($level !== 3 && $level !== 1) {
    http_response_code(403);
    json_out(['ok'=>false,'error'=>'Tidak memiliki akses.']);
}

// Ensure required POST
$id_surat = isset($_POST['id_surat']) ? (int)$_POST['id_surat'] : 0;
$id_arsip_berkas = isset($_POST['id_arsip_berkas']) ? (int)$_POST['id_arsip_berkas'] : 0;
if ($id_surat < 1 || $id_arsip_berkas < 1) {
    http_response_code(400);
    json_out(['ok'=>false,'error'=>'Parameter tidak lengkap.']);
}

// Ensure columns exist (defensive) - MySQL older versions don't support IF NOT EXISTS for ADD COLUMN/INDEX
$existingCols = [];
$resCols = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar");
if ($resCols) { while($c=mysqli_fetch_assoc($resCols)) { $existingCols[] = $c['Field']; } }
$alters = [];
if (!in_array('status',$existingCols)) $alters[] = "ADD COLUMN status ENUM('draft','finished') NOT NULL DEFAULT 'draft'";
if (!in_array('id_arsip_berkas',$existingCols)) $alters[] = "ADD COLUMN id_arsip_berkas INT NULL";
if (!in_array('updated_by',$existingCols)) $alters[] = "ADD COLUMN updated_by VARCHAR(50) NULL";
if (!in_array('updated_at',$existingCols)) $alters[] = "ADD COLUMN updated_at DATETIME NULL";
if (!empty($alters)) { @mysqli_query($config, 'ALTER TABLE tbl_surat_keluar '.implode(', ',$alters)); }
// Ensure index on id_arsip_berkas (avoid duplicate key error under strict mode)
$hasIdx = false;
$resIdx = mysqli_query($config, "SHOW INDEX FROM tbl_surat_keluar WHERE Key_name='idx_arsip_rel'");
if ($resIdx && mysqli_num_rows($resIdx) > 0) { $hasIdx = true; }
if (!$hasIdx) {
    try {
        mysqli_query($config, "CREATE INDEX idx_arsip_rel ON tbl_surat_keluar (id_arsip_berkas)");
    } catch (Throwable $e) {
        // ignore if it raced and now exists
    }
}

// Operator scope: resolve allowed ids
$id_user = (int)$_SESSION['id_user'];
$allowed_ids = [];
if ($level === 3) {
    @include_once __DIR__ . '/../include/operator_access.php';
    if (function_exists('operator_access_info')) {
        $info = operator_access_info($config, $_SESSION);
        $allowed_ids = !empty($info['allowed_ids']) ? array_map('intval', $info['allowed_ids']) : [];
    }
    if (empty($allowed_ids)) { $allowed_ids = [$id_user]; }
}

// Validate surat exists, finished, and within scope
$cond_scope = '';
if ($level === 3) {
    $in = implode(',', array_map('intval',$allowed_ids));
    $cond_scope = " AND id_user IN ($in)";
}
$qs = mysqli_query($config, "SELECT id_surat,id_user,status FROM tbl_surat_keluar WHERE id_surat=$id_surat $cond_scope LIMIT 1");
if (!$qs || mysqli_num_rows($qs) !== 1) {
    http_response_code(404);
    json_out(['ok'=>false,'error'=>'Surat tidak ditemukan atau tidak dalam kewenangan Anda.']);
}
$sur = mysqli_fetch_assoc($qs);
if (!isset($sur['status']) || $sur['status'] !== 'finished') {
    http_response_code(422);
    json_out(['ok'=>false,'error'=>'Hanya surat dengan status finished yang dapat diarsipkan.']);
}

// Validate arsip_berkas belongs to operator scope
// Pastikan tabel arsip ada
@mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_arsip_berkas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  kode_klasifikasi VARCHAR(50) NULL,
  nama_berkas VARCHAR(200) NULL,
  uraian TEXT NULL
) ENGINE=InnoDB");

$cond_berkas = '';
if ($level === 3) {
    $in = implode(',', array_map('intval',$allowed_ids));
    $cond_berkas = " AND id_user IN ($in)";
}
$qb = mysqli_query($config, "SELECT id FROM tbl_arsip_berkas WHERE id=$id_arsip_berkas $cond_berkas LIMIT 1");
if (!$qb || mysqli_num_rows($qb) !== 1) {
    http_response_code(403);
    json_out(['ok'=>false,'error'=>'Berkas arsip tidak valid untuk Anda.']);
}

// Update linkage
$username = isset($_SESSION['username']) ? mysqli_real_escape_string($config, $_SESSION['username']) : 'system';
$now = date('Y-m-d H:i:s');
$upd = mysqli_query($config, "UPDATE tbl_surat_keluar SET id_arsip_berkas=$id_arsip_berkas, updated_by='$username', updated_at='$now' WHERE id_surat=$id_surat $cond_scope LIMIT 1");
if (!$upd) {
    http_response_code(500);
    json_out(['ok'=>false,'error'=>'Gagal menyimpan data.']);
}

json_out(['ok'=>true]);
?>

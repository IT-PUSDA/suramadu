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

// After linking, attempt to upload the local file to Google Drive (archive copy) if not already uploaded
// Ensure file_drive column exists
$resCol = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'file_drive'");
if (!$resCol || mysqli_num_rows($resCol) === 0) {
    @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN file_drive VARCHAR(255) NULL");
}

$qf = mysqli_query($config, "SELECT file, file_drive FROM tbl_surat_keluar WHERE id_surat=$id_surat LIMIT 1");
if ($qf && mysqli_num_rows($qf) === 1) {
    $r = mysqli_fetch_assoc($qf);
    $existingDrive = trim((string)$r['file_drive']);
    $localFile = trim((string)$r['file']);
    if ($existingDrive === '' && $localFile !== '' && strpos($localFile, 'gdrive:fileId=') !== 0) {
        // try to find the local file in common folders
                // Resolve base path safely (fallback when BASE_PATH constant isn't defined)
                $basePath = defined('BASE_PATH') ? BASE_PATH : realpath(__DIR__ . '/../../');
                $possibleDirs = [
                    $basePath . '/upload/surat_keluar/',
                    $basePath . '/upload/notdin/',
                    $basePath . '/upload/surat_masuk/',
                    $basePath . '/upload/arsip_berkas/'
                ];
        $localPath = '';
        foreach ($possibleDirs as $d) {
            $p = $d . $localFile;
            if (file_exists($p)) { $localPath = $p; break; }
        }
        if ($localPath !== '') {
            // perform Drive upload (keep local copy)
            $gdcPath = __DIR__ . '/../Utils/GoogleDriveClient.php';
            if (file_exists($gdcPath)) {
                require_once $gdcPath;
                try {
                    // prepare simple logging for debug
                    $logDir = __DIR__ . '/../logs';
                    if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
                    $logFile = $logDir . '/arsip_upload.log';
                    $log = function($m) use ($logFile) { @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $m . PHP_EOL, FILE_APPEND); };

                    $log("[START] arsip upload id_surat={$id_surat} localFile={$localFile} localPath={$localPath}");
                    $log('CONFIG: GDRIVE_AUTH_MODE=' . (defined('GDRIVE_AUTH_MODE')?GDRIVE_AUTH_MODE:'(undef)') . ' PARENT=' . (defined('GDRIVE_PARENT_FOLDER_ID')?GDRIVE_PARENT_FOLDER_ID:'(undef)'));

                    $client = new GoogleDriveClientSimple(defined('GDRIVE_SERVICE_ACCOUNT_JSON') ? GDRIVE_SERVICE_ACCOUNT_JSON : null);
                    // Use original filename as Drive name
                    $driveName = $localFile;
                    $res = $client->uploadFile($localPath, $driveName, 'application/pdf', defined('GDRIVE_PARENT_FOLDER_ID') ? GDRIVE_PARENT_FOLDER_ID : '');

                    $log('Upload response: ' . json_encode($res));

                    if (!empty($res['fileId'])) {
                        $marker = 'gdrive:fileId=' . $res['fileId'];
                        if (!empty($res['webViewLink'])) { $marker .= '|view=' . $res['webViewLink']; }
                        $marker .= '|name=' . rawurlencode($driveName);
                        $markerEsc = mysqli_real_escape_string($config, $marker);
                        if (@mysqli_query($config, "UPDATE tbl_surat_keluar SET file_drive='$markerEsc' WHERE id_surat=$id_surat LIMIT 1")) {
                            $log("[OK] file_drive updated for id_surat={$id_surat} marker={$marker}");
                        } else {
                            $log("[WARN] failed to update DB file_drive for id_surat={$id_surat}: " . mysqli_error($config));
                        }
                    } else {
                        $log('[WARN] upload returned no fileId');
                    }
                } catch (Exception $e) {
                    // log exception but do not block main operation
                    if (!isset($log)) {
                        $logDir = __DIR__ . '/../logs';
                        if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
                        $logFile = $logDir . '/arsip_upload.log';
                        $log = function($m) use ($logFile) { @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $m . PHP_EOL, FILE_APPEND); };
                    }
                    $log('[ERROR] Exception during Drive upload: ' . $e->getMessage());
                }
            }
        }
    }
}

json_out(['ok'=>true]);
?>

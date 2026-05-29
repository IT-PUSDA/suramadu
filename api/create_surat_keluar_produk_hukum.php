<?php
// Debugging: Show errors to diagnose "Empty Response"
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define BASE_PATH to Project Root (ams/) BEFORE including config
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

header('Content-Type: application/json; charset=utf-8');
// Fix CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed, use POST']);
    exit;
}

// Wrap in try-catch to ensure we get JSON error output
try {

session_start();

// MANUALLY LOAD ENV if config.php fails to find it (Fix for path issues)
$envPath = BASE_PATH . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name); $value = trim($value);
            if (!getenv($name)) { putenv("$name=$value"); $_ENV[$name] = $value; }
        }
    }
}

require_once __DIR__ . '/../src/include/config.php';
require_once __DIR__ . '/../src/include/bidang_mapping.php';
require_once __DIR__ . '/../src/include/file_sequence.php';
@include_once __DIR__ . '/../src/Utils/GoogleDriveClient.php';

// --- Authentication Helper ---
function isAuthenticated() {
    global $config;
    
    // 1. Session Auth
    if (!empty($_SESSION['admin'])) {
        return true;
    }

    // 2. Header Auth
    $apiKeyHeader = $_SERVER['HTTP_X_API_KEY'] ?? '';
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

    if (!$authHeader && function_exists('getallheaders')) {
        $hdrs = getallheaders();
        if (!empty($hdrs['Authorization'])) { $authHeader = $hdrs['Authorization']; }
        elseif (!empty($hdrs['authorization'])) { $authHeader = $hdrs['authorization']; }
    }

    if (!$apiKeyHeader && $authHeader) {
        if (stripos($authHeader, 'Bearer ') === 0) {
            $apiKeyHeader = trim(substr($authHeader, 7));
        }
    }

    // Get expected key for Produk Hukum
    $expectedKey = '';
    if (defined('API_REQUEST_PRODUK_HUKUM_KEY')) { $expectedKey = API_REQUEST_PRODUK_HUKUM_KEY; }
    else { $expectedKey = getenv('API_REQUEST_PRODUK_HUKUM_KEY') ?: ($_ENV['API_REQUEST_PRODUK_HUKUM_KEY'] ?? ''); }
    if ($expectedKey === '') {
        $expectedKey = defined('API_REQUEST_NOMOR_KEY') ? API_REQUEST_NOMOR_KEY : (getenv('API_REQUEST_NOMOR_KEY') ?: ($_ENV['API_REQUEST_NOMOR_KEY'] ?? ''));
    }

    if ($expectedKey !== '' && $apiKeyHeader !== '' && hash_equals($expectedKey, $apiKeyHeader)) {
        if (empty($_SESSION['id_user'])) {
             $_SESSION['id_user'] = 1; 
             $_SESSION['admin'] = 1; 
             $_SESSION['nama'] = 'API User';
        }
        return true;
    }

    return false;
}

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// --- Input Handling ---
$required = ['kode', 'perihal', 'tujuan', 'tgl_surat', 'isi', 'nama_pembuat', 'pin'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' is required"]);
        exit;
    }
}

$nkode = $_POST['kode'];
$perihal = $_POST['perihal'];
$tujuan = $_POST['tujuan'];
$tgl_surat = $_POST['tgl_surat'];
$isi = $_POST['isi'];
$nama_pembuat = $_POST['nama_pembuat'];
$raw_pin = trim($_POST['pin']);

// Optional Bidang override
$bidang_input = $_POST['bidang'] ?? '';
$bidang_resolved = (isset($_SESSION['admin']) && in_array((int)$_SESSION['admin'], [3,4], true)) ? resolve_bidang_code_from_session() : null;
$bidang = ($bidang_input !== '' && $bidang_input !== null) ? $bidang_input : ($bidang_resolved ?? $bidang_input);
// Fallback if still empty
if (empty($bidang)) $bidang = '0';

// --- Validation ---
if (!(ctype_digit($raw_pin) && strlen($raw_pin) === 6)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'PIN harus berupa tepat 6 digit angka']);
    exit;
}
$pin = password_hash($raw_pin, PASSWORD_DEFAULT);

if (!preg_match('/^[0-9.]*$/', $nkode)) {
    http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Kode Klasifikasi invalid chars']); exit;
}
if (!preg_match('/^[0-9.-]*$/', $tgl_surat)) {
    http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Format tanggal invalid']); exit;
}

// --- Logic (derived from proses_tambah_surat_keluar_produk_hukum.php) ---
$id_user = $_SESSION['id_user'];
$year = date('Y', strtotime($tgl_surat));
$jenis = 'produk_hukum'; 

// 1. Calculate No Agenda
$hasJenisForQuery = false; 
$resJenisQ = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
if ($resJenisQ && mysqli_num_rows($resJenisQ) === 1) { $hasJenisForQuery = true; }

$filterJenis = $hasJenisForQuery ? " AND jenis='$jenis'" : "";
$uid = (int)$id_user;
// Note: Logic follows the original file (count per user+year+jenis)
$qcount = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE YEAR(tgl_surat)='".$year."' AND id_user='".$uid."'".$filterJenis);
$totalThisYear = 0; 
if ($qcount) { $rc = mysqli_fetch_assoc($qcount); $totalThisYear = (int)$rc['c']; }

$next = $totalThisYear + 1;
$page = (int)ceil($next / 100);
$line = (int)((($next - 1) % 100) + 1);
$lineStr = ($line === 100) ? '100' : sprintf('%02d', $line);
$no_agenda = sprintf('%02d', $page) . '-' . $lineStr;
// $no_agendak = sprintf('%02d', $page) . $lineStr; // Used for what? Seems unused in DB insert logic for produk_hukum, but let's see.

// 2. Get Next ID
$q1 = mysqli_query($config, "SELECT max(id_surat) as urut FROM tbl_surat_keluar"); 
$d1 = mysqli_fetch_array($q1); 
$id_surat = ($d1['urut'] ?? 0) + 1;

// 3. Generate No Surat
$retry_count = 0;
$is_unique = false;
$pos_code = get_sequence_code_with_sisipan($config, (int)$year, $bidang, $jenis, $tgl_surat);
$no_surat = $nkode . '/' . $pos_code . '/' . $bidang . '/' . $year;

while (!$is_unique && $retry_count < 5) {
     $q_cek = mysqli_query($config, "SELECT id_surat FROM tbl_surat_keluar WHERE no_surat = '$no_surat'");
     if ($q_cek && mysqli_num_rows($q_cek) > 0) {
         // Conflict -> Bump sequence manually
         $prefix = substr($pos_code, 0, -2); 
         $suffix = (int)substr($pos_code, -2);
         $suffix++;
         $pos_code = $prefix . sprintf('%02d', $suffix);
         $no_surat = $nkode . '/' . $pos_code . '/' . $bidang . '/' . $year;
         $retry_count++;
     } else {
         $is_unique = true;
     }
}

// Check Validation for generated No Surat
if (!preg_match('/^[a-zA-Z0-9.\/ -]*$/', $no_surat)) {
    http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Generated No Surat is invalid format']); exit;
}

// Check Duplicate (Double Check after loop)
$dup = mysqli_query($config, "SELECT 1 FROM tbl_surat_keluar WHERE no_surat='$no_surat' LIMIT 1");
if (mysqli_num_rows($dup) > 0) {
    http_response_code(409); 
    echo json_encode(['status' => 'error', 'message' => 'Nomor Surat masih terdeteksi duplicate setelah retry', 'no_surat' => $no_surat]); 
    exit;
}

// 4. Handle File Upload
$nfile = ''; 
$file_no = null;

// Helper to ensure file_no col
$hasFileNo = ensure_file_no_column($config);

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $fileVal = $_FILES['file'];
    $allowed_ext = ['pdf'];
    $x = explode('.', $fileVal['name']);
    $eks = strtolower(end($x));
    
    if (!in_array($eks, $allowed_ext)) {
        http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'File must be PDF']); exit;
    }
    if ($fileVal['size'] > 2097152) { // 2MB
        http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'File size exceeds 2MB']); exit;
    }

    $target_dir = BASE_PATH . '/upload/surat_keluar/';
    // Generate file sequence
    $file_no = next_file_sequence_for_year($config, (int)$year);
    $label = format_file_sequence_label($file_no);
    $nfile = $label.' - '.$fileVal['name'];
    $labeledName = $nfile;
    $tmpPath = $fileVal['tmp_name'];

    // GDorLocal
    if (function_exists('is_gdrive_enabled') && is_gdrive_enabled()) {
        try {
            $gdc = new GoogleDriveClientSimple(GDRIVE_SERVICE_ACCOUNT_JSON);
            $result = $gdc->uploadFile($tmpPath, $nfile, 'application/pdf', GDRIVE_PARENT_FOLDER_ID);
            $nfile = 'gdrive:fileId=' . $result['fileId'];
            if (!empty($result['webViewLink'])) { $nfile .= '|view=' . $result['webViewLink']; }
            $nfile .= '|name=' . rawurlencode($labeledName);
        } catch (Exception $e) {
            // Fallback to local
            if (!is_dir($target_dir)) { @mkdir($target_dir, 0775, true); }
            if (!move_uploaded_file($tmpPath, $target_dir.$nfile)) {
                http_response_code(500); 
                echo json_encode(['status' => 'error', 'message' => 'Upload failed: ' . $e->getMessage()]); 
                exit;
            }
        }
    } else {
        if (!is_dir($target_dir)) { @mkdir($target_dir, 0775, true); }
        if (!move_uploaded_file($tmpPath, $target_dir.$nfile)) {
            http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Upload failed locally']); exit;
        }
    }
}

// 5. Insert
// Ensure 'jenis' column exists again (just in case)
$hasJenis = false; 
$resJenis = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
if ($resJenis && mysqli_num_rows($resJenis) === 1) { $hasJenis = true; }
else {
    mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN jenis VARCHAR(20) NOT NULL DEFAULT 'umum'");
    $hasJenis = true;
}

$col_file_no = ($file_no === null) ? "NULL" : "'" . intval($file_no) . "'";

// Build Query
// Note: We respect $hasFileNo
$fields = "id_surat,no_agenda,perihal,no_surat,tujuan,kode,tgl_surat,isi,file,id_user,bidang,nama_pembuat,pin";
$values = "'$id_surat','$no_agenda','$perihal','$no_surat','$tujuan','$nkode','$tgl_surat','$isi','$nfile','$id_user','$bidang','$nama_pembuat','$pin'";

if ($hasFileNo) {
    $fields .= ",file_no";
    $values .= ",$col_file_no";
}
if ($hasJenis) {
    $fields .= ",jenis";
    $values .= ",'$jenis'";
}

$sql = "INSERT INTO tbl_surat_keluar($fields) VALUES($values)";
$ok = mysqli_query($config, $sql);

if ($ok) {
    http_response_code(201);
    echo json_encode([
        'status' => 'success', 
        'message' => 'Data created successfully',
        'data' => [
            'id_surat' => $id_surat,
            'no_surat' => $no_surat,
            'no_agenda' => $no_agenda,
            'jenis' => $jenis
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database insert failed: ' . mysqli_error($config)
    ]);
}

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal Server Error: ' . $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}

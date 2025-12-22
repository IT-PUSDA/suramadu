<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed, use POST']);
    exit;
}

// Load app config to get DB connection ($config)
require_once __DIR__ . '/../src/include/config.php';

// Accept JSON or form-encoded body
$input = [];
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($ct, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) { $data = []; }
    $input = $data;
} else {
    $input = $_POST;
}

// Map expected fields (same as form)
$fields = ['kode','perihal','tujuan','tgl_surat','isi','nama_pembuat','pin','bidang'];
foreach ($fields as $f) { if (!isset($input[$f])) { $input[$f] = ''; } }

// Authentication: allow if logged-in session OR valid API key header `X-API-Key`.
// Read API key header (case-insensitive server mapping yields HTTP_X_API_KEY)
$apiKeyHeader = $_SERVER['HTTP_X_API_KEY'] ?? '';
// Also accept Authorization: Bearer <key>
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
if (empty($_SESSION['admin'])) {
    // If config provides an API key and it matches the header, allow; otherwise reject.
    if (defined('API_REQUEST_NOMOR_KEY') && API_REQUEST_NOMOR_KEY !== '' && hash_equals(API_REQUEST_NOMOR_KEY, $apiKeyHeader)) {
        // authenticated via API key
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized: please login or provide valid X-API-Key header']);
        exit;
    }
}

// Trim inputs
foreach ($input as $k => $v) { if (is_string($v)) { $input[$k] = trim($v); } }

// Required fields validation (same required fields as form)
$required = ['kode','perihal','tujuan','tgl_surat','isi','nama_pembuat','pin'];
$missing = [];
foreach ($required as $r) { if ($input[$r] === '') { $missing[] = $r; } }
if (!empty($missing)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields', 'missing' => $missing]);
    exit;
}

// PIN format: exactly 6 digits
if (!(ctype_digit($input['pin']) && strlen($input['pin']) === 6)) {
    http_response_code(400);
    echo json_encode(['error' => 'PIN harus berupa tepat 6 digit angka']);
    exit;
}

// Basic field regex checks (similar to proses_tambah_surat_keluar.php)
if (!preg_match('/^[0-9.]*$/', $input['kode'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Field `kode` hanya boleh mengandung angka dan titik']);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9.\/ -]*$/', $input['kode'] . '/' . 'X')) {
    // no-op to keep parity with original checks; the main number format built below will be validated
}

// Resolve bidang: if session role is operator (3) or bidang (4) lock bidang from session logic if available
if (in_array((int)($_SESSION['admin'] ?? 0), [3,4], true) && function_exists('resolve_bidang_code_from_session')) {
    $bidang = resolve_bidang_code_from_session();
} else {
    $bidang = $input['bidang'] ?: '0';
}

$tgl_surat_raw = $input['tgl_surat'];
$ts = DateTime::createFromFormat('Y-m-d', $tgl_surat_raw) ?: DateTime::createFromFormat('Y-m-d H:i:s', $tgl_surat_raw) ?: new DateTime($tgl_surat_raw);
if (!$ts) {
    http_response_code(400);
    echo json_encode(['error' => 'Format tanggal tidak valid']);
    exit;
}
$tgl_surat = $ts->format('Y-m-d');
$year = $ts->format('Y');

// Determine next agenda number for the date
$escaped_date = mysqli_real_escape_string($config, $tgl_surat);
$q_agenda = mysqli_query($config, "SELECT no_agenda FROM tbl_surat_keluar WHERE tgl_surat='$escaped_date' ORDER BY id_surat DESC LIMIT 1");
$no_agenda_urut_baru = 1;
if ($q_agenda && mysqli_num_rows($q_agenda) > 0) {
    $d = mysqli_fetch_assoc($q_agenda);
    $last_no = $d['no_agenda'];
    $pos = strpos($last_no, "-");
    if ($pos !== false) {
        $last_urut = (int) substr($last_no, $pos + 1);
        $no_agenda_urut_baru = $last_urut + 1;
    }
}

// Compute day-of-year difference from Jan 1 as in original code
$tanggal_awal_tahun = new DateTime($year . '-01-01');
$selisih_hari = $ts->diff($tanggal_awal_tahun)->format('%a');

$no_agenda = $selisih_hari . '-' . sprintf('%02d', $no_agenda_urut_baru);
$no_agendak = $selisih_hari . sprintf('%02d', $no_agenda_urut_baru);

$nkode = $input['kode'];
$no_surat = $nkode . '/' . $no_agendak . '/' . $bidang . '/' . $year;

// Check collision: ensure no_surat not already in DB
$escaped_ns = mysqli_real_escape_string($config, $no_surat);
$cek = mysqli_query($config, "SELECT COUNT(*) as cnt FROM tbl_surat_keluar WHERE no_surat='$escaped_ns'");
$already = false;
if ($cek) {
    $c = mysqli_fetch_assoc($cek);
    if (!empty($c['cnt']) && (int)$c['cnt'] > 0) { $already = true; }
}

$resp = [
    'status' => $already ? 'conflict' : 'ok',
    'no_agenda' => $no_agenda,
    'no_agendak' => $no_agendak,
    'no_surat' => $no_surat,
    'year' => $year,
    'next_agenda_urut' => $no_agenda_urut_baru,
    'tgl_surat' => $tgl_surat,
];

if ($already) {
    http_response_code(409);
    $resp['message'] = 'Nomor surat sudah terpakai';
}

echo json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

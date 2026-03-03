<?php
// Load .env into environment (simple parser)
// Fix path: src/include -> src -> root
$projectRoot = dirname(__DIR__, 2);
// Ensure application uses local timezone (GMT+7)
date_default_timezone_set('Asia/Jakarta');
$envFile = $projectRoot . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) { continue; }
        if (strpos($line, '=') === false) { continue; }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ((substr($value,0,1) === '"' && substr($value,-1) === '"') || (substr($value,0,1) === "'" && substr($value,-1) === "'")) {
            $value = substr($value, 1, -1);
        }
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Database defaults (can be overridden via .env)
$host =      (getenv('DB_HOST') !== false)     ? getenv('DB_HOST')     : '10.242.100.200';
$username =  (getenv('DB_USERNAME') !== false) ? getenv('DB_USERNAME') : 'sekretariat_psda';
$password =  (getenv('DB_PASSWORD') !== false) ? getenv('DB_PASSWORD') : 'p5d4m3ndun14';
$database =  (getenv('DB_DATABASE') !== false) ? getenv('DB_DATABASE') : 'sekretariat_suramaduv2';


// --- FORCE PRODUCTION SETTINGS IF ON OFFICIAL DOMAIN ---
// Ini untuk pengaman jika ada file .env yang salah di server (misal setting localhost terbawa)
if (isset($_SERVER['SERVER_NAME']) && stripos($_SERVER['SERVER_NAME'], 'dpuair.jatimprov.go.id') !== false) {
    // Kita berada di server kantor, paksa gunakan setting IP Server
    $host = '10.242.100.200';
    $username = 'sekretariat_psda';
    $password = 'p5d4m3ndun14';
    $database = 'sekretariat_suramaduv2';
}

// TEMP DEBUG: enable error display and logging for local development (remove in production)
@ini_set('display_errors', 1);
@ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ensure logs directory exists and set php error log there
if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 1)); }
if (!is_dir(BASE_PATH . '/logs')) { @mkdir(BASE_PATH . '/logs', 0775, true); }
@ini_set('log_errors', 1);
@ini_set('error_log', BASE_PATH . '/logs/php_error.log');

// Google API configuration (read-only from environment; no hardcoded secrets)
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_OAUTH_SCOPE', 'https://www.googleapis.com/auth/calendar');
define('REDIRECT_URI', 'http://localhost/ams/gcal/google_calendar_event_sync.php');

$googleOauthURL = 'https://accounts.google.com/o/oauth2/auth?scope=' . urlencode(GOOGLE_OAUTH_SCOPE) . '&redirect_uri=' . REDIRECT_URI . '&response_type=code&client_id=' . GOOGLE_CLIENT_ID . '&access_type=online';

// Upload storage configuration (local | gdrive)
// Default to 'local' so initial uploads are saved to the server filesystem.
// Drive will still be used for archival copies (arsip) by the archive endpoint.
if (!defined('UPLOAD_STORAGE')) {
    define('UPLOAD_STORAGE', 'local');
}
// Google Drive auth mode: 'service_account' (default) or 'oauth' (use personal Google account)
if (!defined('GDRIVE_AUTH_MODE')) {
    define('GDRIVE_AUTH_MODE', 'oauth');
}
// Path to Service Account JSON credentials (downloaded from Google Cloud Console)
if (!defined('GDRIVE_SERVICE_ACCOUNT_JSON')) {
    // Updated to use user-provided credentials filename
    define('GDRIVE_SERVICE_ACCOUNT_JSON', dirname(__DIR__) . '/Utils/credentials/ams-drive-uploader-3b5ca8aa5ecf.json');
}
// OAuth (non-Workspace) configuration for uploading to personal My Drive
// Create OAuth Client (type: Web application) in Google Cloud Console with redirect URI below
if (!defined('GDRIVE_OAUTH_CLIENT_ID')) { define('GDRIVE_OAUTH_CLIENT_ID', getenv('GDRIVE_OAUTH_CLIENT_ID') ?: ''); }
if (!defined('GDRIVE_OAUTH_CLIENT_SECRET')) { define('GDRIVE_OAUTH_CLIENT_SECRET', getenv('GDRIVE_OAUTH_CLIENT_SECRET') ?: ''); }
// Adjust base URL if app not served from /ams or host differs
if (!defined('GDRIVE_OAUTH_REDIRECT_URI')) { define('GDRIVE_OAUTH_REDIRECT_URI', 'http://localhost/ams/src/Utils/gdrive_oauth_connect.php'); }
// Where to store refresh token JSON {"refresh_token":"..."}
if (!defined('GDRIVE_OAUTH_TOKEN_JSON')) { define('GDRIVE_OAUTH_TOKEN_JSON', dirname(__DIR__) . '/Utils/credentials/gdrive_oauth_token.json'); }
// Target Drive Folder ID to store files (use a specific folder for all Surat Keluar files)
if (!defined('GDRIVE_PARENT_FOLDER_ID')) {
    // Read from environment; keep empty when not configured
    define('GDRIVE_PARENT_FOLDER_ID', getenv('GDRIVE_PARENT_FOLDER_ID') ?: '');
}
// Automatically create a public link (anyone with the link can view) for uploaded files
if (!defined('GDRIVE_MAKE_FILE_PUBLIC')) {
    define('GDRIVE_MAKE_FILE_PUBLIC', false);
}
// If using Shared Drives, set this true
if (!defined('GDRIVE_SUPPORTS_ALL_DRIVES')) {
    define('GDRIVE_SUPPORTS_ALL_DRIVES', false);
}

// Delete Drive file when removing record (applies to files uploaded via this app)
if (!defined('GDRIVE_DELETE_REMOTE_ON_REMOVE')) {
    define('GDRIVE_DELETE_REMOTE_ON_REMOVE', true);
}


$config = mysqli_connect($host, $username, $password, $database);

// set connection charset explicitly to avoid unknown charset warning
// and ensure proper UTF-8 handling (use utf8mb4 for full Unicode support)
if ($config) {
    if (!mysqli_set_charset($config, 'utf8mb4')) {
        // fallback: attempt utf8
        @mysqli_set_charset($config, 'utf8');
    }
}

if (!$config) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Defensive DB migration: ensure `file_drive` column exists on tbl_surat_keluar
// This prevents "Unknown column 'file_drive'" errors in pages that SELECT it.
if ($config) {
    $ok = @mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'file_drive'");
    if (!$ok || mysqli_num_rows($ok) === 0) {
        @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN file_drive VARCHAR(255) NULL");
    }
    // ------------------------------------------------------------------
    // Ensure numbering helper structures exist so duplicates cannot slip in.
    // These simple migrations run on every request but are safe/effectively
    // no-ops after the first execution.
    @mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_date_sequence (
        tgl_surat DATE NOT NULL,
        bidang VARCHAR(50) NOT NULL,
        jenis VARCHAR(50) NOT NULL,
        seq INT NOT NULL,
        PRIMARY KEY (tgl_surat, bidang, jenis)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // add unique index on no_surat if missing (silent failure if already exists)
    @mysqli_query($config, "ALTER TABLE tbl_surat_keluar
        ADD CONSTRAINT uq_no_surat UNIQUE (no_surat)");
}

// API key for programmatic requests (read from env when available)
if (!defined('API_REQUEST_NOMOR_KEY')) {
    $envKey = getenv('API_REQUEST_NOMOR_KEY');
    // Fallback: Use hardcoded key if .env is missing on server
    $defaultKey = 'A3f9vN7qZsK2mP8xT0bL4uY6wR1cH5jD9sG2kV7pQ8zM4nR6uB1yF0wX'; 
    define('API_REQUEST_NOMOR_KEY', ($envKey !== false && $envKey !== '') ? $envKey : $defaultKey);
}

require_once __DIR__ . '/activity_logger.php';
activity_log_request($config);

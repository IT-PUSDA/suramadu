<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "ams_native";

// Google API configuration 
define('GOOGLE_CLIENT_ID', '105436136720-d3ju2cmh6erit721munbiftu0l5ah1jf.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'REMOVED_BY_GIT_HISTORY_REWRITE');
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
if (!defined('GDRIVE_OAUTH_CLIENT_ID')) { define('GDRIVE_OAUTH_CLIENT_ID', '87555883140-9jtgh4vi9870i5d6g3u3qcalm63l4ji7.apps.googleusercontent.com'); }
if (!defined('GDRIVE_OAUTH_CLIENT_SECRET')) { define('GDRIVE_OAUTH_CLIENT_SECRET', 'REMOVED_BY_GIT_HISTORY_REWRITE'); }
// Adjust base URL if app not served from /ams or host differs
if (!defined('GDRIVE_OAUTH_REDIRECT_URI')) { define('GDRIVE_OAUTH_REDIRECT_URI', 'http://localhost/ams/src/Utils/gdrive_oauth_connect.php'); }
// Where to store refresh token JSON {"refresh_token":"..."}
if (!defined('GDRIVE_OAUTH_TOKEN_JSON')) { define('GDRIVE_OAUTH_TOKEN_JSON', dirname(__DIR__) . '/Utils/credentials/gdrive_oauth_token.json'); }
// Target Drive Folder ID to store files (use a specific folder for all Surat Keluar files)
if (!defined('GDRIVE_PARENT_FOLDER_ID')) {
    // Updated to the folder you requested for archival uploads
    define('GDRIVE_PARENT_FOLDER_ID', 'REMOVED_BY_GIT_HISTORY_REWRITE'); // target folder ID
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
}

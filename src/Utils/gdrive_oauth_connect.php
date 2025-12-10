<?php
// Minimal OAuth helper to obtain refresh_token for Google Drive (personal account)
// 1) Set GDRIVE_OAUTH_CLIENT_ID, GDRIVE_OAUTH_CLIENT_SECRET, GDRIVE_OAUTH_REDIRECT_URI in config.php
// 2) Open this script in browser: http://localhost/ams/src/Utils/gdrive_oauth_connect.php
// 3) Approve consent; the script stores refresh_token at GDRIVE_OAUTH_TOKEN_JSON

session_start();
require_once __DIR__ . '/../include/config.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (empty(GDRIVE_OAUTH_CLIENT_ID) || empty(GDRIVE_OAUTH_CLIENT_SECRET)) {
    die('Konfigurasi OAuth belum lengkap. Isi GDRIVE_OAUTH_CLIENT_ID dan GDRIVE_OAUTH_CLIENT_SECRET di config.php');
}

$scope = 'https://www.googleapis.com/auth/drive.file';
$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?response_type=code'
    . '&client_id=' . urlencode(GDRIVE_OAUTH_CLIENT_ID)
    . '&redirect_uri=' . urlencode(GDRIVE_OAUTH_REDIRECT_URI)
    . '&scope=' . urlencode($scope)
    . '&access_type=offline'
    . '&prompt=consent';

if (!isset($_GET['code'])) {
    echo '<h3>Authorize Google Drive</h3>';
    echo '<p><a href="' . h($authUrl) . '">Klik di sini untuk menghubungkan Google Drive</a></p>';
    echo '<p>Pastikan URL ini sama dengan yang Anda set sebagai Authorized redirect URI di Google Cloud Console:</p>';
    echo '<code>' . h(GDRIVE_OAUTH_REDIRECT_URI) . '</code>';
    exit;
}

// Exchange code for tokens
$code = $_GET['code'];
$post = http_build_query([
    'code' => $code,
    'client_id' => GDRIVE_OAUTH_CLIENT_ID,
    'client_secret' => GDRIVE_OAUTH_CLIENT_SECRET,
    'redirect_uri' => GDRIVE_OAUTH_REDIRECT_URI,
    'grant_type' => 'authorization_code',
]);

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);
$resp = curl_exec($ch);
$codeHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false || $codeHttp >= 400) {
    http_response_code(500);
    echo 'Gagal menukar code dengan token. HTTP ' . $codeHttp . ' Resp: ' . h($resp);
    exit;
}

$tok = json_decode($resp, true);
if (!$tok || empty($tok['refresh_token'])) {
    http_response_code(500);
    echo 'Token tidak memuat refresh_token. Resp: ' . h($resp);
    exit;
}

// Save refresh token
@file_put_contents(GDRIVE_OAUTH_TOKEN_JSON, json_encode(['refresh_token' => $tok['refresh_token']], JSON_PRETTY_PRINT));

echo '<p>Berhasil menyimpan refresh_token ke: <code>' . h(GDRIVE_OAUTH_TOKEN_JSON) . '</code></p>';
echo '<p>Anda sekarang dapat set <code>GDRIVE_AUTH_MODE</code> ke <code>\'oauth\'</code> dan mencoba unggah.</p>';
echo '<p><a href="../Utils/test_gdrive.php" target="_blank">Jalankan tes upload</a></p>';

?>

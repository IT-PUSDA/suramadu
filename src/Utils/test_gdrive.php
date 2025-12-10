<?php
// Simple diagnostic to verify Google Drive config and credentials
// Visit via browser or run with: php -f src/Utils/test_gdrive.php

session_start();

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/GoogleDriveClient.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "Google Drive Diagnostic Test\n";
echo "============================\n";

echo "UPLOAD_STORAGE = " . (defined('UPLOAD_STORAGE') ? UPLOAD_STORAGE : '(undefined)') . "\n";
echo "GDRIVE_SERVICE_ACCOUNT_JSON = " . (defined('GDRIVE_SERVICE_ACCOUNT_JSON') ? GDRIVE_SERVICE_ACCOUNT_JSON : '(undefined)') . "\n";
echo "GDRIVE_PARENT_FOLDER_ID = " . (defined('GDRIVE_PARENT_FOLDER_ID') ? GDRIVE_PARENT_FOLDER_ID : '(undefined)') . "\n";
echo "GDRIVE_SUPPORTS_ALL_DRIVES = " . (defined('GDRIVE_SUPPORTS_ALL_DRIVES') && GDRIVE_SUPPORTS_ALL_DRIVES ? 'true' : 'false') . "\n";
echo "GDRIVE_MAKE_FILE_PUBLIC = " . (defined('GDRIVE_MAKE_FILE_PUBLIC') && GDRIVE_MAKE_FILE_PUBLIC ? 'true' : 'false') . "\n\n";
echo "GDRIVE_AUTH_MODE = " . (defined('GDRIVE_AUTH_MODE') ? GDRIVE_AUTH_MODE : '(service_account)') . "\n\n";

if (!is_gdrive_enabled()) {
    echo "ERROR: UPLOAD_STORAGE belum diset ke 'gdrive'.\n";
    exit(1);
}

if (defined('GDRIVE_AUTH_MODE') && GDRIVE_AUTH_MODE === 'service_account') {
    if (!file_exists(GDRIVE_SERVICE_ACCOUNT_JSON)) {
        echo "ERROR: File kredensial Service Account tidak ditemukan di: " . GDRIVE_SERVICE_ACCOUNT_JSON . "\n";
        exit(1);
    }
} else {
    if (empty(GDRIVE_OAUTH_CLIENT_ID) || empty(GDRIVE_OAUTH_CLIENT_SECRET)) {
        echo "ERROR: GDRIVE_OAUTH_CLIENT_ID/SECRET belum diisi.\n";
        exit(1);
    }
    if (!file_exists(GDRIVE_OAUTH_TOKEN_JSON)) {
        echo "ERROR: Refresh token OAuth tidak ditemukan. Buka " . GDRIVE_OAUTH_REDIRECT_URI . " untuk otorisasi.\n";
        exit(1);
    }
}

try {
    $client = new GoogleDriveClientSimple(GDRIVE_AUTH_MODE === 'service_account' ? GDRIVE_SERVICE_ACCOUNT_JSON : null);
    // Buat file sementara
    $tmp = tempnam(sys_get_temp_dir(), 'ams_gdrive_test_');
    $content = "AMS Drive test at " . date('c') . "\n";
    file_put_contents($tmp, $content);
    $fileName = 'ams_gdrive_test_' . date('Ymd_His') . '.txt';

    echo "Mengunggah file uji: {$fileName}\n";
    $res = $client->uploadFile($tmp, $fileName, 'text/plain', GDRIVE_PARENT_FOLDER_ID);
    @unlink($tmp);

    echo "SUKSES!\n";
    echo "fileId: " . ($res['fileId'] ?? '') . "\n";
    if (!empty($res['webViewLink'])) {
        echo "webViewLink: " . $res['webViewLink'] . "\n";
    } else {
        echo "Info: Link publik tidak dibuat (GDRIVE_MAKE_FILE_PUBLIC=false).\n";
        echo "Anda bisa membuka file melalui: https://drive.google.com/file/d/" . urlencode($res['fileId']) . "/view\n";
    }
    echo "\nCatatan: Jika Anda melihat file ini di folder Drive tujuan, konfigurasi sudah OK.\n";
} catch (Exception $e) {
    echo "GAGAL: " . $e->getMessage() . "\n";
    echo "\nPeriksa: \n- Folder dibagikan ke email Service Account (Editor)\n- Folder ID benar\n- GDRIVE_SUPPORTS_ALL_DRIVES sesuai (true untuk Shared Drive)\n- Ekstensi PHP curl & openssl aktif\n- Isi file JSON valid (client_email & private_key)\n";
    exit(1);
}

?>

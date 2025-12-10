<?php
// Lightweight Google Drive uploader using raw HTTP calls with Service Account JWT for small deployments
// Avoids requiring Composer. For production, using google/apiclient is recommended.
if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(dirname(__DIR__, 1))); }
require_once BASE_PATH . '/src/include/config.php';

class GoogleDriveClientSimple
{
    private $clientEmail;
    private $privateKey;
    private $tokenCacheFile;
    private $authMode; // 'service_account' | 'oauth'

    public function __construct($serviceAccountJsonPath = null)
    {
        $this->authMode = defined('GDRIVE_AUTH_MODE') ? GDRIVE_AUTH_MODE : 'service_account';

        if ($this->authMode === 'service_account') {
            if (!$serviceAccountJsonPath || !file_exists($serviceAccountJsonPath)) {
                throw new Exception('Google Service Account JSON tidak ditemukan: ' . $serviceAccountJsonPath);
            }
            $json = json_decode(file_get_contents($serviceAccountJsonPath), true);
            if (!$json || empty($json['client_email']) || empty($json['private_key'])) {
                throw new Exception('Berkas kredensial Service Account tidak valid.');
            }
            $this->clientEmail = $json['client_email'];
            $this->privateKey = $json['private_key'];
            $this->tokenCacheFile = sys_get_temp_dir() . '/gdrive_sa_token_' . md5($this->clientEmail) . '.cache';
        } else {
            // OAuth mode uses refresh_token from file, no SA JSON required
            $this->tokenCacheFile = sys_get_temp_dir() . '/gdrive_oauth_token.cache';
        }
    }

    // Create a short-lived OAuth2 access token via JWT (Service Account)
    private function getAccessToken()
    {
        // Try cache first
        if (file_exists($this->tokenCacheFile)) {
            $cached = json_decode(@file_get_contents($this->tokenCacheFile), true);
            if ($cached && isset($cached['access_token']) && isset($cached['exp']) && time() < ($cached['exp'] - 60)) {
                return $cached['access_token'];
            }
        }

        if ($this->authMode === 'service_account') {
            $now = time();
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claim = [
                'iss' => $this->clientEmail,
                'scope' => 'https://www.googleapis.com/auth/drive.file',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now
            ];
            $payload = base64_encode(json_encode($claim));
            $signingInput = $header . '.' . $payload;
            $signature = '';
            $pkey = openssl_pkey_get_private($this->privateKey);
            if (!$pkey || !openssl_sign($signingInput, $signature, $pkey, 'sha256')) {
                throw new Exception('Gagal menandatangani JWT untuk Google OAuth2');
            }
            $jwt = $signingInput . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            $postFields = http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);
            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($resp === false || $code >= 400) {
                throw new Exception('Gagal mendapatkan access token Google (SA): ' . curl_error($ch) . ' HTTP ' . $code . ' Resp: ' . $resp);
            }
            curl_close($ch);
            $data = json_decode($resp, true);
            if (!$data || empty($data['access_token'])) {
                throw new Exception('Response token tidak valid dari Google: ' . $resp);
            }
            // Cache
            @file_put_contents($this->tokenCacheFile, json_encode(['access_token' => $data['access_token'], 'exp' => time() + (int)($data['expires_in'] ?? 3600)]));
            return $data['access_token'];
        } else {
            // OAuth refresh token flow
            if (empty(GDRIVE_OAUTH_CLIENT_ID) || empty(GDRIVE_OAUTH_CLIENT_SECRET)) {
                throw new Exception('GDRIVE_OAUTH_CLIENT_ID/SECRET belum diisi.');
            }
            if (!file_exists(GDRIVE_OAUTH_TOKEN_JSON)) {
                throw new Exception('Refresh token OAuth tidak ditemukan. Buka ' . GDRIVE_OAUTH_REDIRECT_URI . ' untuk otorisasi.');
            }
            $tok = json_decode(file_get_contents(GDRIVE_OAUTH_TOKEN_JSON), true);
            if (!$tok || empty($tok['refresh_token'])) {
                throw new Exception('Berkas token OAuth tidak valid.');
            }
            $postFields = http_build_query([
                'client_id' => GDRIVE_OAUTH_CLIENT_ID,
                'client_secret' => GDRIVE_OAUTH_CLIENT_SECRET,
                'grant_type' => 'refresh_token',
                'refresh_token' => $tok['refresh_token'],
            ]);
            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($resp === false || $code >= 400) {
                throw new Exception('Gagal mendapatkan access token Google (OAuth): ' . curl_error($ch) . ' HTTP ' . $code . ' Resp: ' . $resp);
            }
            curl_close($ch);
            $data = json_decode($resp, true);
            if (!$data || empty($data['access_token'])) {
                throw new Exception('Response token tidak valid dari Google (OAuth): ' . $resp);
            }
            @file_put_contents($this->tokenCacheFile, json_encode(['access_token' => $data['access_token'], 'exp' => time() + (int)($data['expires_in'] ?? 3600)]));
            return $data['access_token'];
        }
    }

    // Upload a file to Google Drive; returns array with fileId and webViewLink (if made public)
    public function uploadFile($localPath, $fileName, $mimeType = 'application/pdf', $parentFolderId = '')
    {
        if (!file_exists($localPath)) {
            throw new Exception('File lokal tidak ditemukan: ' . $localPath);
        }
        $accessToken = $this->getAccessToken();

        // Use multipart upload
        $meta = ['name' => $fileName];
        if ($parentFolderId) { $meta['parents'] = [$parentFolderId]; }
        $boundary = 'gdrive-' . md5(uniqid('', true));
        $delimiter = "\r\n--{$boundary}\r\n";
        $closeDelim = "\r\n--{$boundary}--";
        $body = $delimiter
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . json_encode($meta)
            . $delimiter
            . "Content-Type: {$mimeType}\r\n\r\n"
            . file_get_contents($localPath)
            . $closeDelim;

        $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart' . (GDRIVE_SUPPORTS_ALL_DRIVES ? '&supportsAllDrives=true' : ''));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: multipart/related; boundary=' . $boundary,
            ],
            CURLOPT_POSTFIELDS => $body,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($resp === false || $code >= 400) {
            throw new Exception('Upload ke Google Drive gagal: HTTP ' . $code . ' ' . curl_error($ch) . ' Resp: ' . $resp);
        }
        curl_close($ch);
        $data = json_decode($resp, true);
        if (empty($data['id'])) {
            throw new Exception('Upload sukses tapi ID file tidak ditemukan. Resp: ' . $resp);
        }

        $fileId = $data['id'];
        $viewLink = '';
        if (GDRIVE_MAKE_FILE_PUBLIC) {
            // Create permission anyoneWithLink viewer
            $perm = json_encode(['role' => 'reader', 'type' => 'anyone']);
            $url = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '/permissions';
            if (GDRIVE_SUPPORTS_ALL_DRIVES) { $url .= '?supportsAllDrives=true'; }
            $ch2 = curl_init($url);
            curl_setopt_array($ch2, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => $perm
            ]);
            $resp2 = curl_exec($ch2);
            curl_close($ch2);
            $viewLink = 'https://drive.google.com/file/d/' . $fileId . '/view';
        }
        return ['fileId' => $fileId, 'webViewLink' => $viewLink];
    }

    // Delete a file from Google Drive by its fileId
    public function deleteFile($fileId)
    {
        if (empty($fileId)) {
            throw new Exception('fileId kosong');
        }
        $accessToken = $this->getAccessToken();
        $url = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId);
        if (defined('GDRIVE_SUPPORTS_ALL_DRIVES') && GDRIVE_SUPPORTS_ALL_DRIVES) {
            $url .= '?supportsAllDrives=true';
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false) {
            throw new Exception('Gagal menghapus file di Google Drive: ' . curl_error($ch));
        }
        // 204 No Content is success for delete
        if ($code !== 204) {
            // Benign if file already missing (404)
            if ($code === 404) { return false; }
            throw new Exception('Gagal menghapus file di Google Drive. HTTP ' . $code . ' Resp: ' . $resp);
        }
        return true;
    }

    // Update an existing Google Drive file's content (and optionally name)
    public function updateFile($fileId, $localPath, $newFileName = null, $mimeType = 'application/pdf')
    {
        if (empty($fileId)) { throw new Exception('fileId kosong'); }
        if (!file_exists($localPath)) { throw new Exception('File lokal tidak ditemukan: ' . $localPath); }
        $accessToken = $this->getAccessToken();

        // If renaming, first patch metadata (name)
        if (!empty($newFileName)) {
            $meta = json_encode(['name' => $newFileName]);
            $url = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId);
            if (GDRIVE_SUPPORTS_ALL_DRIVES) { $url .= '?supportsAllDrives=true'; }
            $chMeta = curl_init($url);
            curl_setopt_array($chMeta, [
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => $meta
            ]);
            $respMeta = curl_exec($chMeta);
            $codeMeta = curl_getinfo($chMeta, CURLINFO_HTTP_CODE);
            curl_close($chMeta);
            if ($respMeta === false || $codeMeta >= 400) {
                throw new Exception('Gagal memperbarui metadata file di Google Drive. HTTP ' . $codeMeta . ' Resp: ' . $respMeta);
            }
        }

        // Replace content
        $urlUpload = 'https://www.googleapis.com/upload/drive/v3/files/' . rawurlencode($fileId) . '?uploadType=media';
        if (GDRIVE_SUPPORTS_ALL_DRIVES) { $urlUpload .= '&supportsAllDrives=true'; }
        $ch = curl_init($urlUpload);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: ' . $mimeType
            ],
            CURLOPT_POSTFIELDS => file_get_contents($localPath)
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false || $code >= 400) {
            throw new Exception('Gagal memperbarui konten file di Google Drive. HTTP ' . $code . ' Resp: ' . $resp);
        }
        return true;
    }
}

// Helper to determine if Drive storage is enabled
function is_gdrive_enabled()
{
    return (defined('UPLOAD_STORAGE') && UPLOAD_STORAGE === 'gdrive');
}

?>

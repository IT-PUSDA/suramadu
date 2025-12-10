<?php
// Utility helpers for Google Drive integration

if (!function_exists('gdrive_delete_by_marker')) {
    /**
     * Delete a Google Drive file when the DB stores a Drive marker like:
     *   gdrive:fileId=<ID>|view=<url>|name=<urlencoded>
     * This respects GDRIVE_DELETE_REMOTE_ON_REMOVE.
     */
    function gdrive_delete_by_marker($fileMarker)
    {
        if (empty($fileMarker)) return;
        if (!defined('GDRIVE_DELETE_REMOTE_ON_REMOVE') || !GDRIVE_DELETE_REMOTE_ON_REMOVE) return;
        if (strpos($fileMarker, 'gdrive:fileId=') !== 0) return;

        // Extract fileId
        $fileId = '';
        $parts = explode('|', $fileMarker);
        foreach ($parts as $p) {
            if (strpos($p, 'gdrive:fileId=') === 0) {
                $fileId = substr($p, strlen('gdrive:fileId='));
                break;
            }
        }
        if ($fileId === '') return;

        // Lazy-load Drive client and delete
        $gdcPath = __DIR__ . '/../Utils/GoogleDriveClient.php';
        if (!file_exists($gdcPath)) return;
        require_once $gdcPath;
        try {
            $client = new GoogleDriveClientSimple(defined('GDRIVE_SERVICE_ACCOUNT_JSON') ? GDRIVE_SERVICE_ACCOUNT_JSON : null);
            $client->deleteFile($fileId);
        } catch (\Throwable $e) {
            // Do not block app deletion on Drive errors; optionally log if needed
        }
    }
}

?>

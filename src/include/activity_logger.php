<?php
// Helper to centralize activity logging for audit purposes
if (!function_exists('activity_log_bootstrap')) {
    function activity_log_bootstrap(mysqli $config)
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        $check = @mysqli_query($config, "SHOW TABLES LIKE 'tbl_activity_log'");
        if (!$check || mysqli_num_rows($check) === 0) {
            $sql = "CREATE TABLE IF NOT EXISTS tbl_activity_log (
                id_log BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                id_user INT UNSIGNED NULL,
                username VARCHAR(120) NULL,
                nama VARCHAR(190) NULL,
                role_level SMALLINT NULL,
                action VARCHAR(200) NULL,
                detail TEXT NULL,
                page VARCHAR(60) NULL,
                act VARCHAR(60) NULL,
                sub VARCHAR(100) NULL,
                request_method VARCHAR(10) NOT NULL DEFAULT 'GET',
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id_log),
                INDEX idx_activity_created_at (created_at),
                INDEX idx_activity_user (id_user),
                INDEX idx_activity_page (page)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            @mysqli_query($config, $sql);
        }
    }

    function activity_log_event(mysqli $config, $action, $detail = '', array $context = [])
    {
        activity_log_bootstrap($config);
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return false;
        }
        $userId = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : null;
        $username = isset($_SESSION['username']) ? (string)$_SESSION['username'] : null;
        $nama = isset($_SESSION['nama']) ? (string)$_SESSION['nama'] : null;
        $role = isset($_SESSION['admin']) ? (int)$_SESSION['admin'] : null;
        $page = isset($_GET['page']) ? substr((string)$_GET['page'], 0, 60) : null;
        $act = isset($_REQUEST['act']) ? substr((string)$_REQUEST['act'], 0, 60) : null;
        $sub = isset($_REQUEST['sub']) ? substr((string)$_REQUEST['sub'], 0, 100) : null;
        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'CLI';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? substr((string)$_SERVER['REMOTE_ADDR'], 0, 45) : null;
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
        $meta = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $meta[$key] = $value;
            }
        }
        $detailText = $detail;
        if (!empty($meta)) {
            $encoded = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded !== false) {
                $detailText = ($detailText !== '' ? $detailText . ' | ' : '') . $encoded;
            }
        }
        $stmt = mysqli_prepare($config, "INSERT INTO tbl_activity_log (id_user, username, nama, role_level, action, detail, page, act, sub, request_method, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ississssssss', $userId, $username, $nama, $role, $action, $detailText, $page, $act, $sub, $requestMethod, $ip, $agent);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    function activity_log_request(mysqli $config)
    {
        if (defined('ACTIVITY_LOG_REQUEST_RECORDED')) {
            return;
        }
        if (!isset($_SESSION) || empty($_SESSION['id_user'])) {
            return;
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
        $page = isset($_GET['page']) ? (string)$_GET['page'] : 'unknown';
        $act = isset($_REQUEST['act']) ? (string)$_REQUEST['act'] : '';
        $sub = isset($_REQUEST['sub']) ? (string)$_REQUEST['sub'] : '';
        $path = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
        $action = trim($method . ' ' . $page . ($act ? '/' . $act : '') . ($sub ? '/' . $sub : ''));
        $payload = [];
        if (!empty($_POST)) {
            foreach ($_POST as $key => $value) {
                $keyLower = strtolower((string)$key);
                if (strpos($keyLower, 'password') !== false || strpos($keyLower, 'token') !== false) {
                    continue;
                }
                if (is_scalar($value)) {
                    $payload[$key] = substr((string)$value, 0, 180);
                } elseif (is_array($value)) {
                    $payload[$key] = 'array(' . count($value) . ')';
                } else {
                    $payload[$key] = gettype($value);
                }
            }
        }
        if (!empty($_FILES)) {
            foreach ($_FILES as $key => $info) {
                if (!isset($info['name'])) {
                    continue;
                }
                $payload['file:' . $key] = $info['name'];
            }
        }
        $meta = [
            'uri' => $path,
        ];
        if (!empty($payload)) {
            $meta['payload'] = $payload;
        }
        activity_log_event($config, $action, '', $meta);
        define('ACTIVITY_LOG_REQUEST_RECORDED', true);
    }
}

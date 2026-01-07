<?php
if (!isset($_SESSION['admin']) || (int)$_SESSION['admin'] !== 1) {
    echo '<div class="card red lighten-5"><div class="card-content"><span class="red-text">Anda tidak memiliki akses ke log aktivitas.</span></div></div>';
    return;
}

$perPage = 50;
$logPage = isset($_GET['log_page']) ? max(1, (int)$_GET['log_page']) : 1;
$offset = ($logPage - 1) * $perPage;
$searchTerm = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$userFilter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$methodFilter = isset($_GET['method']) ? strtoupper(trim((string)$_GET['method'])) : '';
$startInput = isset($_GET['start']) ? trim((string)$_GET['start']) : '';
$endInput = isset($_GET['end']) ? trim((string)$_GET['end']) : '';

$startSql = '';
$endSql = '';
if ($startInput !== '') {
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $startInput);
    if ($dt) {
        $startSql = $dt->format('Y-m-d H:i:00');
    }
}
if ($endInput !== '') {
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $endInput);
    if ($dt) {
        $endSql = $dt->format('Y-m-d H:i:59');
    }
}

$conditions = ['1=1'];
if ($userFilter > 0) {
    $conditions[] = 'id_user=' . $userFilter;
}
if ($methodFilter !== '' && in_array($methodFilter, ['GET', 'POST'], true)) {
    $conditions[] = "request_method='" . mysqli_real_escape_string($config, $methodFilter) . "'";
}
if ($searchTerm !== '') {
    $safeSearch = mysqli_real_escape_string($config, $searchTerm);
    $conditions[] = "(action LIKE '%$safeSearch%' OR detail LIKE '%$safeSearch%' OR username LIKE '%$safeSearch%' OR nama LIKE '%$safeSearch%')";
}
if ($startSql !== '') {
    $conditions[] = "created_at >= '" . mysqli_real_escape_string($config, $startSql) . "'";
}
if ($endSql !== '') {
    $conditions[] = "created_at <= '" . mysqli_real_escape_string($config, $endSql) . "'";
}
$whereClause = implode(' AND ', $conditions);

$totalRows = 0;
$countRes = mysqli_query($config, "SELECT COUNT(*) AS total FROM tbl_activity_log WHERE $whereClause");
if ($countRes) {
    $row = mysqli_fetch_assoc($countRes);
    $totalRows = isset($row['total']) ? (int)$row['total'] : 0;
}
$totalPages = $totalRows > 0 ? (int)ceil($totalRows / $perPage) : 1;

$dataSql = "SELECT id_log, id_user, username, nama, role_level, action, detail, page, act, sub, request_method, ip_address, user_agent, created_at FROM tbl_activity_log WHERE $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$result = mysqli_query($config, $dataSql);

$userOptions = [];
$userRes = mysqli_query($config, "SELECT DISTINCT id_user, username, nama FROM tbl_activity_log WHERE id_user IS NOT NULL ORDER BY nama ASC");
if ($userRes) {
    while ($userRow = mysqli_fetch_assoc($userRes)) {
        $uid = (int)$userRow['id_user'];
        if ($uid === 0) {
            continue;
        }
        $label = trim(($userRow['nama'] ?? '') . ' (' . ($userRow['username'] ?? '') . ')');
        $userOptions[$uid] = $label;
    }
}

$roleLabels = [
    1 => 'Super Admin',
    2 => 'Pimpinan',
    3 => 'Operator Sekretariat',
    4 => 'Admin Bidang',
];
?>

<style>
.activity-log-wrapper { width: 100vw; position: relative; left: 50%; margin-left: -50vw; padding: 0 56px 40px; box-sizing: border-box; }
.activity-log-wrapper .row { margin: 0; }
.activity-log-wrapper .row .col { padding: 0; }
.activity-log-card { border-radius: 14px; box-shadow: 0 8px 24px rgba(23,43,77,0.14); margin: 0; }
.activity-log-card .card-content { padding: 22px 30px; }
.activity-log-card h3 { margin: 0; font-size: 24px; font-weight: 600; color: #263238; }
.bank-dok-back { background:#7f8c8d; color:#ffffff; padding:10px 15px; border-radius:5px; text-decoration:none; display:inline-block; margin-bottom:15px; transition:background 0.3s; }
.bank-dok-back:hover { background:#34495e; }

.activity-log-filters { display:flex; flex-wrap:wrap; gap:18px 24px; margin:12px 0 24px; align-items:flex-end; }
.activity-log-filters .field { display:flex; flex-direction:column; }
.activity-log-filters label { font-size:11px; font-weight:600; color:#455a64; margin-bottom:5px; letter-spacing:.5px; }
.activity-log-filters input,
.activity-log-filters select { background:#fafafa; border:1px solid #d0d7de; border-radius:8px; height:40px; padding:0 12px; min-width:200px; }
.activity-log-filters button { height:40px; line-height:40px; padding:0 18px; border-radius:20px; }

@media (max-width: 992px) {
    .activity-log-wrapper { width: 100%; left: 0; margin-left: 0; padding: 0 24px 32px; }
}
@media (max-width: 600px) {
    .activity-log-wrapper { padding: 0 12px 24px; }
    .activity-log-filters input,
    .activity-log-filters select { min-width: 160px; }
}

.table-activity { width:100%; border-collapse:collapse; }
.table-activity thead th { background:#263238; color:#ffffff; padding:10px 12px; text-align:left; }
.table-activity tbody td { padding:10px 12px; border-bottom:1px solid #e0e6ed; font-size:13px; }
.table-activity tbody tr:nth-child(even) { background:#f8fafc; }
.table-activity .col-action { width:240px; }
.table-activity .col-meta { width:220px; }
.table-activity .timestamp { font-weight:600; color:#1b5e20; }

.log-empty { text-align:center; color:#90a4ae; padding:32px; }
.pagination-log { display:flex; justify-content:flex-end; gap:8px; margin-top:18px; }
.pagination-log a,
.pagination-log span { padding:6px 12px; border-radius:6px; border:1px solid #cfd8dc; font-size:13px; color:#546e7a; text-decoration:none; }
.pagination-log .active { background:#1976d2; border-color:#1976d2; color:#ffffff; }
.pagination-log .disabled { opacity:0.4; pointer-events:none; }
</style>

<div class="activity-log-wrapper">
    <a href="index.php?page=admin" class="bank-dok-back">← Kembali ke Beranda</a>

    <div class="row" style="margin-bottom:0;">
        <div class="col s12">
            <div class="card activity-log-card">
                <div class="card-content">
                    <h3>Log Aktivitas Pengguna</h3>
                    <p style="margin:6px 0 18px; color:#607d8b;">Pantau seluruh aktivitas pengguna secara real-time hingga resolusi detik.</p>

                    <form method="GET" class="activity-log-filters" action="index.php">
                        <input type="hidden" name="page" value="admin">
                        <input type="hidden" name="act" value="activity_log">
                        <div class="field">
                            <label for="start">Mulai</label>
                            <input type="datetime-local" id="start" name="start" value="<?php echo htmlspecialchars($startInput, ENT_QUOTES); ?>">
                        </div>
                        <div class="field">
                            <label for="end">Selesai</label>
                            <input type="datetime-local" id="end" name="end" value="<?php echo htmlspecialchars($endInput, ENT_QUOTES); ?>">
                        </div>
                        <div class="field">
                            <label for="user_id">Pengguna</label>
                            <select id="user_id" name="user_id">
                                <option value="0">Semua Pengguna</option>
                                <?php foreach ($userOptions as $uid => $label): ?>
                                    <option value="<?php echo $uid; ?>" <?php echo ($uid === $userFilter) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="method">Metode</label>
                            <select id="method" name="method">
                                <option value="">Semua</option>
                                <option value="GET" <?php echo ($methodFilter === 'GET') ? 'selected' : ''; ?>>GET</option>
                                <option value="POST" <?php echo ($methodFilter === 'POST') ? 'selected' : ''; ?>>POST</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="search">Pencarian</label>
                            <input type="search" id="search" name="search" placeholder="Ketik kata kunci..." value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES); ?>">
                        </div>
                        <div>
                            <button type="submit" class="btn blue btn-pill">Terapkan</button>
                        </div>
                    </form>

                    <div style="overflow:auto;">
                        <table class="table-activity">
                            <thead>
                                <tr>
                                    <th style="width:160px;">Waktu</th>
                                    <th style="width:140px;">Pengguna</th>
                                    <th style="width:120px;">Role</th>
                                    <th class="col-action">Aksi</th>
                                    <th class="col-meta">Detail</th>
                                    <th style="width:100px;">Metode</th>
                                    <th style="width:120px;">IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                    <?php while ($log = mysqli_fetch_assoc($result)): ?>
                                        <?php
                                            $time = isset($log['created_at']) ? date('d/m/Y H:i:s', strtotime($log['created_at'])) : '-';
                                            $name = $log['nama'] ?? '-';
                                            $username = $log['username'] ?? '';
                                            $label = trim($name) !== '' ? $name : ($username ?: '-');
                                            if ($username && $label !== $username) {
                                                $label .= ' (' . $username . ')';
                                            }
                                            $roleText = '-';
                                            if (!empty($log['role_level']) && isset($roleLabels[(int)$log['role_level']])) {
                                                $roleText = $roleLabels[(int)$log['role_level']];
                                            }
                                            $detail = $log['detail'] ?? '';
                                        ?>
                                        <tr>
                                            <td><span class="timestamp"><?php echo htmlspecialchars($time, ENT_QUOTES); ?></span></td>
                                            <td><?php echo htmlspecialchars($label, ENT_QUOTES); ?></td>
                                            <td><?php echo htmlspecialchars($roleText, ENT_QUOTES); ?></td>
                                            <td>
                                                <div style="font-weight:600; color:#1e3a8a;"><?php echo htmlspecialchars($log['action'] ?? '-', ENT_QUOTES); ?></div>
                                                <div style="font-size:12px; color:#607d8b;">Halaman: <?php echo htmlspecialchars($log['page'] ?? '-', ENT_QUOTES); ?><?php echo !empty($log['act']) ? ' / ' . htmlspecialchars($log['act'], ENT_QUOTES) : ''; ?><?php echo !empty($log['sub']) ? ' / ' . htmlspecialchars($log['sub'], ENT_QUOTES) : ''; ?></div>
                                            </td>
                                            <td><?php echo $detail !== '' ? htmlspecialchars($detail, ENT_QUOTES) : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($log['request_method'] ?? '-', ENT_QUOTES); ?></td>
                                            <td><?php echo htmlspecialchars($log['ip_address'] ?? '-', ENT_QUOTES); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="log-empty">Belum ada aktivitas yang tercatat untuk filter ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-log">
                        <?php
                            $queryString = $_GET;
                            if ($logPage > 1) {
                                $queryString['log_page'] = $logPage - 1;
                                echo '<a href="' . htmlspecialchars('index.php?' . http_build_query($queryString), ENT_QUOTES) . '" class="btn-flat">&laquo; Sebelumnya</a>';
                            } else {
                                echo '<span class="disabled">&laquo; Sebelumnya</span>';
                            }
                            echo '<span class="active">Hal ' . $logPage . ' dari ' . $totalPages . '</span>';
                            if ($logPage < $totalPages) {
                                $queryString['log_page'] = $logPage + 1;
                                echo '<a href="' . htmlspecialchars('index.php?' . http_build_query($queryString), ENT_QUOTES) . '" class="btn-flat">Berikutnya &raquo;</a>';
                            } else {
                                echo '<span class="disabled">Berikutnya &raquo;</span>';
                            }
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

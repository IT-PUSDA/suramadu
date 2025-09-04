<?php
// Cek session & hak akses
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header('Location: index.php');
    die();
}
if ((int)$_SESSION['admin'] !== 1) {
    echo '<script>window.alert("ERROR! Anda tidak memiliki hak akses untuk membuka halaman ini"); window.location.href="index.php?page=logout";</script>';
    die();
}

// Direktori penyimpanan backup
$backupDir = 'backup';
if (!is_dir($backupDir)) { @mkdir($backupDir, 0775, true); }

// Helper: unduh file hasil backup (sql/zip)
if (isset($_GET['dl'])) {
    $fn = basename($_GET['dl']);
    $path = $backupDir . '/' . $fn;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['sql', 'zip'])) {
        echo '<script>window.alert("ERROR! Format file tidak didukung."); window.location.href="index.php?page=admin&act=sett&sub=back";</script>';
        die();
    }
    if (file_exists($path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $fn);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: private');
        header('Pragma: private');
        header('Content-Length: ' . filesize($path));
        ob_clean();
        flush();
        readfile($path);
        exit;
    } else {
        echo '<script>window.alert("ERROR! File sudah tidak ada"); window.location.href="index.php?page=admin&act=sett&sub=back";</script>';
        die();
    }
}

// Fungsi backup database sederhana (dump SQL)
function backup_db($host, $user, $pass, $name, $file, $tables = '*') {
    $return = '';
    $link = mysqli_connect($host, $user, $pass, $name);
    if (!$link) { return false; }

    if ($tables == '*') {
        $tables = [];
        $result = mysqli_query($link, 'SHOW TABLES');
        while ($row = mysqli_fetch_row($result)) { $tables[] = $row[0]; }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }

    foreach ($tables as $table) {
        $result = mysqli_query($link, 'SELECT * FROM ' . $table);
        if (!$result) { continue; }
        $num_fields = mysqli_num_fields($result);
        $return .= 'DROP TABLE IF EXISTS ' . $table . ';';
        $row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE ' . $table));
        $return .= "\n\n" . $row2[1] . ";\n\n";

        while ($row = mysqli_fetch_row($result)) {
            $return .= 'INSERT INTO ' . $table . ' VALUES(';
            for ($j = 0; $j < $num_fields; $j++) {
                $val = isset($row[$j]) ? addslashes($row[$j]) : '';
                $val = preg_replace("/\n/", "\\n", $val);
                $return .= '"' . $val . '"' . ($j < ($num_fields - 1) ? ',' : '');
            }
            $return .= ");\n";
        }
        $return .= "\n\n";
    }
    return (bool)file_put_contents($file, $return);
}

// Helper: human readable file size
function human_filesize($bytes, $decimals = 2) {
    $size = ['B','KB','MB','GB','TB'];
    if ($bytes <= 0) return '0 B';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf('%.' . $decimals . 'f', $bytes / pow(1024, $factor)) . ' ' . $size[$factor];
}

// Ambil daftar file upload/surat_keluar sesuai filter
function get_filtered_upload_files($config, $from = null, $to = null, $groupKey = null, $uploaderId = null) {
    $where = ["file <> ''"]; // hanya yang punya file
    if ($from && $to) {
        $f = mysqli_real_escape_string($config, $from);
        $t = mysqli_real_escape_string($config, $to);
        $where[] = "tgl_surat BETWEEN '" . $f . "' AND '" . $t . "'";
    }

    // Map grup -> username uploader
    $map = [
        'sekretariat'   => ['SEKRETARIAT', 'TU'],
        'psda'          => ['PSDA'],
        'irigasi'       => ['IRIGASI'],
        'swp'           => ['SWP'],
        'binfat'        => ['BINFAT'],
        'upt-kediri'    => ['KEDIRI'],
        'korwil-malang' => ['MALANG'],
        'korwil-surabaya'=> ['SURABAYA'],
        'upt-bojonegoro'=> ['BOJONEGORO'],
        'korwil-madiun' => ['MADIUN'],
        'upt-bondowoso' => ['BONDOWOSO'],
        'upt-lumajang'  => ['LUMAJANG'],
        'upt-pasuruan'  => ['PASURUAN'],
        'upt-madura'    => ['MADURA'],
    ];

    if ($groupKey && isset($map[$groupKey])) {
        $usernames = array_map('strtoupper', $map[$groupKey]);
        $in = "'" . implode("','", array_map(function($s) use ($config){ return mysqli_real_escape_string($config, $s); }, $usernames)) . "'";
        $res = mysqli_query($config, "SELECT id_user FROM tbl_user WHERE UPPER(username) IN ($in)");
        $ids = [];
        if ($res) { while ($r = mysqli_fetch_assoc($res)) { $ids[] = (int)$r['id_user']; } }
        if (!empty($ids)) { $where[] = 'id_user IN (' . implode(',', $ids) . ')'; }
    } elseif ($uploaderId) {
        $id = (int)$uploaderId; $where[] = 'id_user=' . $id;
    }

    $sql = 'SELECT file FROM tbl_surat_keluar WHERE ' . implode(' AND ', $where);
    $rs = mysqli_query($config, $sql);
    $files = [];
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            if (!empty($row['file'])) {
                $p = 'upload/surat_keluar/' . $row['file'];
                if (file_exists($p)) { $files[] = $p; }
            }
        }
    }
    return $files;
}

// Fungsi membuat ZIP berkas upload/surat_keluar terfilter
function zip_uploads_filtered($config, $destZip, $from = null, $to = null, $groupKey = null, $uploaderId = null) {
    if (!class_exists('ZipArchive')) {
        // Ekstensi ZIP belum aktif di PHP
        return 'NO_ZIP_EXTENSION';
    }
    $files = get_filtered_upload_files($config, $from, $to, $groupKey, $uploaderId);

    $zip = new ZipArchive();
    if ($zip->open($destZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { return false; }
    foreach ($files as $path) { if (file_exists($path)) { $zip->addFile($path, basename($path)); } }
    // Tambah logo instansi jika ada (opsional berguna untuk restore)
    if (file_exists('upload/lambang-provinsi-jawa-timur.jpg')) {
        $zip->addFile('upload/lambang-provinsi-jawa-timur.jpg', 'lambang-provinsi-jawa-timur.jpg');
    }
    $zip->close();
    return true;
}

// UI Header
echo '<div class="row"><div class="col s12"><div class="z-depth-1"><nav class="secondary-nav"><div class="nav-wrapper blue-grey darken-1"><div class="col m12"><ul class="left"><li class="waves-effect waves-light"><a href="index.php?page=admin&act=sett&sub=back" class="judul"><i class="material-icons">storage</i> Backup</a></li></ul></div></div></nav></div></div></div>';

// Handle submit backup
$resultSql = null; $resultZip = null; $sqlName = ''; $zipName = '';
if (isset($_POST['backup'])) {
    // Ambil pilihan
    $mode = !empty($_POST['mode']) ? strtolower(trim($_POST['mode'])) : 'both'; // sql | zip | both
    $doSql = ($mode === 'sql' || $mode === 'both');
    $doZip = ($mode === 'zip' || $mode === 'both');
    $from = !empty($_POST['from']) ? $_POST['from'] : null; // YYYY-MM-DD
    $to   = !empty($_POST['to']) ? $_POST['to'] : null;
    $groupKey = !empty($_POST['group_key']) ? $_POST['group_key'] : null;
    $uploaderId = !empty($_POST['uploader_id']) ? (int)$_POST['uploader_id'] : null;

    if ($doSql) {
        // Gunakan kredensial dari config.php
        global $host, $username, $password, $database;
        $sqlName = 'DB_Backup_' . date('Ymd_His') . '.sql';
        $resultSql = backup_db($host, $username, $password, $database, $backupDir . '/' . $sqlName, '*');
    }
    if ($doZip) {
        $label = $groupKey ? $groupKey : ($uploaderId ? ('user' . $uploaderId) : 'all');
        $zipName = 'Uploads_Backup_' . $label . '_' . date('Ymd_His') . '.zip';
        $resultZip = zip_uploads_filtered($config, $backupDir . '/' . $zipName, $from, $to, $groupKey, $uploaderId);
    }

    echo '<div class="row"><div class="col m12"><div class="card"><div class="card-content">';
    echo '<span class="card-title black-text"><i class="material-icons md-36 green-text">done</i> Proses backup selesai</span>';
    echo '<div class="section">';
    if ($doSql) {
        echo $resultSql ? '<p class="green-text">Database (.sql) berhasil dibuat.</p>' : '<p class="red-text">Gagal membuat backup database.</p>';
    }
    if ($doZip) {
        if ($resultZip === 'NO_ZIP_EXTENSION') {
            echo '<p class="orange-text">Tidak bisa membuat ZIP: ekstensi PHP ZipArchive belum aktif. Aktifkan ekstensi ZIP lalu coba lagi.</p>';
        } else {
            echo $resultZip ? '<p class="green-text">Berkas upload (.zip) berhasil dibuat.</p>' : '<p class="red-text">Gagal membuat arsip upload.</p>';
        }
    }
    echo '</div></div><div class="card-action">';
    if ($resultSql) { echo '<a class="btn blue" href="index.php?page=admin&act=sett&sub=back&dl=' . urlencode($sqlName) . '"><i class="material-icons left">file_download</i>Download SQL</a> '; }
    if ($resultZip === true) { echo '<a class="btn deep-orange" href="index.php?page=admin&act=sett&sub=back&dl=' . urlencode($zipName) . '"><i class="material-icons left">archive</i>Download ZIP</a>'; }
    echo '</div></div></div></div>';
} else {
    // Tampilkan form opsi backup (UI sederhana)
    // Ambil daftar uploader (bidang) untuk pilihan
    $users = mysqli_query($config, "SELECT id_user, username, nama FROM tbl_user ORDER BY username ASC");
    // State untuk mempertahankan pilihan saat preview
    $modeSel = isset($_POST['mode']) ? strtolower($_POST['mode']) : 'both';
    $fromVal = isset($_POST['from']) ? htmlspecialchars($_POST['from']) : '';
    $toVal = isset($_POST['to']) ? htmlspecialchars($_POST['to']) : '';
    $groupKeyVal = isset($_POST['group_key']) ? $_POST['group_key'] : '';
    $uploaderIdVal = isset($_POST['uploader_id']) ? (int)$_POST['uploader_id'] : 0;
    $previewRequested = isset($_POST['preview']);

    echo '<div class="row"><div class="col m12"><div class="card">';
    echo '<div class="card-content">';
    echo '<span class="card-title black-text" style="display:flex;align-items:center;gap:8px;"><i class="material-icons">storage</i> Backup</span>';
    echo '<p class="kata">Pilih mode backup. Jika memilih berkas upload (.zip), Anda bisa membatasi berdasarkan tanggal atau bidang/uploader.</p>';
    echo '</div>';
    echo '<div class="card-content" style="padding-top:0;">';
    echo '<form method="post">';

    // Pilih mode backup (lebih jelas)
    echo '<div class="row" style="margin-bottom: 4px;">
            <div class="col s12">
                <label style="font-weight:600;">Pilih Mode Backup</label>
                <p style="margin-top:6px; display:flex; flex-wrap:wrap; gap:24px;">
                    <label>
                        <input class="with-gap" name="mode" type="radio" value="sql" id="mode_sql" ' . ($modeSel==='sql' ? 'checked' : '') . '>
                        <span>Hanya Database (.sql)</span>
                    </label>
                    <label>
                        <input class="with-gap" name="mode" type="radio" value="zip" id="mode_zip" ' . ($modeSel==='zip' ? 'checked' : '') . '>
                        <span>Hanya Berkas Upload (.zip)</span>
                    </label>
                    <label>
                        <input class="with-gap" name="mode" type="radio" value="both" id="mode_both" ' . ($modeSel==='both' ? 'checked' : '') . '>
                        <span>Keduanya (.sql + .zip)</span>
                    </label>
                </p>
                <small class="grey-text">Database berisi data untuk restore; ZIP berisi file PDF dari folder upload/surat_keluar.</small>
            </div>
        </div>';

    // Filter ZIP (opsional)
    echo '<div id="zipFilters" style="margin-top: 12px;">
            <div class="row" style="margin-bottom:0;">
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix">date_range</i>
                    <input type="text" id="from" name="from" class="datepicker" value="' . $fromVal . '">
                    <label for="from">Dari tanggal (opsional)</label>
                </div>
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix">date_range</i>
                    <input type="text" id="to" name="to" class="datepicker" value="' . $toVal . '">
                    <label for="to">Sampai tanggal (opsional)</label>
                </div>
            </div>
            <div class="row" style="margin-bottom:0;">
                <div class="input-field col s12">
                    <select class="browser-default" name="group_key" id="group_key">
                        <option value="">-- Filter berdasarkan Bidang/UPT (opsional) --</option>
                        <option value="sekretariat" ' . ($groupKeyVal==='sekretariat'?'selected':'') . '>Sekretariat</option>
                        <option value="psda" ' . ($groupKeyVal==='psda'?'selected':'') . '>PSDA</option>
                        <option value="irigasi" ' . ($groupKeyVal==='irigasi'?'selected':'') . '>Irigasi</option>
                        <option value="swp" ' . ($groupKeyVal==='swp'?'selected':'') . '>SWP</option>
                        <option value="binfat" ' . ($groupKeyVal==='binfat'?'selected':'') . '>Binfat</option>
                        <option value="upt-kediri" ' . ($groupKeyVal==='upt-kediri'?'selected':'') . '>UPT Kediri</option>
                        <option value="korwil-malang" ' . ($groupKeyVal==='korwil-malang'?'selected':'') . '>Korwil Malang</option>
                        <option value="korwil-surabaya" ' . ($groupKeyVal==='korwil-surabaya'?'selected':'') . '>Korwil Surabaya</option>
                        <option value="upt-bojonegoro" ' . ($groupKeyVal==='upt-bojonegoro'?'selected':'') . '>UPT Bojonegoro</option>
                        <option value="korwil-madiun" ' . ($groupKeyVal==='korwil-madiun'?'selected':'') . '>Korwil Madiun</option>
                        <option value="upt-bondowoso" ' . ($groupKeyVal==='upt-bondowoso'?'selected':'') . '>UPT Bondowoso</option>
                        <option value="upt-lumajang" ' . ($groupKeyVal==='upt-lumajang'?'selected':'') . '>UPT Lumajang</option>
                        <option value="upt-pasuruan" ' . ($groupKeyVal==='upt-pasuruan'?'selected':'') . '>UPT Pasuruan</option>
                        <option value="upt-madura" ' . ($groupKeyVal==='upt-madura'?'selected':'') . '>UPT Madura</option>
                    </select>
                    <small class="grey-text">Jika dipilih, pilihan uploader di bawah akan diabaikan</small>
                </div>
                <div class="input-field col s12">
                    <select class="browser-default" name="uploader_id" id="uploader_id">
                        <option value="">-- Atau pilih uploader (opsional) --</option>';
    if ($users && mysqli_num_rows($users) > 0) {
        while ($u = mysqli_fetch_assoc($users)) {
            $sel = ($uploaderIdVal === (int)$u['id_user']) ? ' selected' : '';
            echo '<option value="' . (int)$u['id_user'] . '"' . $sel . '>' . htmlspecialchars($u['username']) . ' — ' . htmlspecialchars($u['nama']) . '</option>';
        }
    }
    echo '           </select>
                </div>
            </div>
        </div>';

    // Tombol di tengah (Preview & Backup)
    echo '<div class="row" style="margin-top:14px;">
            <div class="col s12 center-align" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <button type="submit" name="preview" class="btn-large grey equal-btn"><i class="material-icons left">search</i>PREVIEW</button>
                <button type="submit" name="backup" class="btn-large blue equal-btn"><i class="material-icons left">backup</i>BACKUP</button>
            </div>
        </div>';

    echo '</form></div></div></div></div>';

    // Jika diminta preview, tampilkan ringkasan & estimasi
    if ($previewRequested) {
        $groupLabels = [
            'sekretariat'   => 'Sekretariat',
            'psda'          => 'PSDA',
            'irigasi'       => 'Irigasi',
            'swp'           => 'SWP',
            'binfat'        => 'Binfat',
            'upt-kediri'    => 'UPT Kediri',
            'korwil-malang' => 'Korwil Malang',
            'korwil-surabaya'=> 'Korwil Surabaya',
            'upt-bojonegoro'=> 'UPT Bojonegoro',
            'korwil-madiun' => 'Korwil Madiun',
            'upt-bondowoso' => 'UPT Bondowoso',
            'upt-lumajang'  => 'UPT Lumajang',
            'upt-pasuruan'  => 'UPT Pasuruan',
            'upt-madura'    => 'UPT Madura',
        ];

        // ZIP estimation
        $zipCount = 0; $zipSize = 0; $files = [];
        if ($modeSel === 'zip' || $modeSel === 'both') {
            $files = get_filtered_upload_files($config, $fromVal ?: null, $toVal ?: null, $groupKeyVal ?: null, $uploaderIdVal ?: null);
            $zipCount = count($files);
            foreach ($files as $fp) { $zipSize += @filesize($fp) ?: 0; }
        }

        // SQL estimation
        $dbSize = 0; $dbSizeHuman = '-';
        if ($modeSel === 'sql' || $modeSel === 'both') {
            global $database;
            $dbEsc = mysqli_real_escape_string($config, $database);
            $q = mysqli_query($config, "SELECT SUM(data_length+index_length) AS size FROM information_schema.tables WHERE table_schema='".$dbEsc."'");
            if ($q && ($row = mysqli_fetch_assoc($q)) && !is_null($row['size'])) { $dbSize = (int)$row['size']; $dbSizeHuman = human_filesize($dbSize); }
        }

        // Get uploader label (optional)
        $uploaderLabel = '-';
        if ($uploaderIdVal) {
            $uq = mysqli_query($config, 'SELECT username, nama FROM tbl_user WHERE id_user='.(int)$uploaderIdVal.' LIMIT 1');
            if ($uq && ($ur = mysqli_fetch_assoc($uq))) { $uploaderLabel = htmlspecialchars($ur['username']) . ' — ' . htmlspecialchars($ur['nama']); }
        }

        echo '<div class="row"><div class="col m12"><div class="card"><div class="card-content">';
        echo '<span class="card-title black-text" style="display:flex;align-items:center;gap:8px;"><i class="material-icons green-text">visibility</i> Preview Backup</span>';
        echo '<div class="section">';
        echo '<ul class="collection">';
        echo '<li class="collection-item">Mode: <strong>' . strtoupper($modeSel) . '</strong></li>';
        echo '<li class="collection-item">Rentang tanggal: <strong>' . ($fromVal ? $fromVal : '-') . '</strong> s.d. <strong>' . ($toVal ? $toVal : '-') . '</strong></li>';
        echo '<li class="collection-item">Bidang/UPT: <strong>' . ($groupKeyVal ? $groupLabels[$groupKeyVal] : '-') . '</strong></li>';
        echo '<li class="collection-item">Uploader: <strong>' . $uploaderLabel . '</strong></li>';
        if ($modeSel === 'zip' || $modeSel === 'both') {
            echo '<li class="collection-item">Perkiraan berkas ZIP: <strong>' . $zipCount . ' file</strong> (~ ' . human_filesize($zipSize) . ')</li>';
        }
        if ($modeSel === 'sql' || $modeSel === 'both') {
            echo '<li class="collection-item">Perkiraan ukuran dump SQL: <strong>' . $dbSizeHuman . '</strong></li>';
        }
        echo '</ul>';
        echo '<p class="grey-text">Preview tidak membuat file apapun. Klik BACKUP untuk mengeksekusi.</p>';
        echo '</div></div></div></div>';
    }

    // Style kecil + init datepicker & toggle sederhana
        echo '<style>
            .input-field .prefix { top: 0.6rem; }
            @media (max-width: 992px){ .input-field .prefix { top: 0.9rem; } }
            .equal-btn{ min-width: 200px; }
        </style>';
        echo <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Datepicker: dukung Materialize v1 (M.Datepicker) & v0.97 (pickadate)
    var els = document.querySelectorAll('.datepicker');
    if (window.M && M.Datepicker && typeof M.Datepicker.init === 'function') {
        M.Datepicker.init(els, {format: 'yyyy-mm-dd', autoClose: true});
    } else if (window.jQuery && jQuery.fn.pickadate) {
        jQuery(function($){
            $('.datepicker').pickadate({
                selectMonths: true,
                selectYears: true,
                format: 'yyyy-mm-dd',
                formatSubmit: 'yyyy-mm-dd',
                hiddenName: true
            });
        });
    }

    // Tampilkan/hilangkan filter ZIP berdasar mode
    var zipFilters = document.getElementById('zipFilters');
    function getMode(){ var el = document.querySelector('input[name="mode"]:checked'); return el ? el.value : 'both'; }
    function applyMode(){ var m = getMode(); zipFilters.style.display = (m === 'zip' || m === 'both') ? 'block' : 'none'; }
    var radios = document.querySelectorAll('input[name="mode"]');
    [].forEach.call(radios, function(r){ r.addEventListener('change', applyMode); });
    applyMode();
});
</script>
SCRIPT;
}
?>

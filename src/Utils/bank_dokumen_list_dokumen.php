<?php
// Halaman Bank Dokumen - List/Upload Dokumen per Jenis Berkas

if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header('Location: index.php');
    die();
}

$allowed_roles = [1, 4];
if (!in_array((int)$_SESSION['admin'], $allowed_roles)) {
    $_SESSION['err'] = '<center>Anda tidak memiliki akses!</center>';
    header('Location: index.php');
    die();
}

$id_kat = isset($_GET['id_kat']) ? (int)$_GET['id_kat'] : 0;
$id_jenis = isset($_GET['id_jenis']) ? (int)$_GET['id_jenis'] : 0;
$id_user = (int)($_SESSION['id_user'] ?? 0);
$nama_user = $_SESSION['nama'] ?? 'User';

// Validasi kategori dan jenis
$jenis_check = mysqli_query($config, "SELECT j.id_jenis, j.nama_jenis, k.nama_kategori FROM tbl_bank_dokumen_jenis j JOIN tbl_bank_dokumen_kategori k ON j.id_kategori = k.id_kategori WHERE j.id_jenis='$id_jenis' AND j.id_kategori='$id_kat'");
if (!$jenis_check || mysqli_num_rows($jenis_check) === 0) {
    $_SESSION['err'] = 'Jenis berkas tidak ditemukan!';
    header('Location: index.php?page=admin&act=bank_dok');
    die();
}
$jenis_data = mysqli_fetch_assoc($jenis_check);

$search_term = trim($_GET['cari'] ?? '');

// Proses upload dokumen
if (isset($_POST['submit_dokumen'])) {
    $nama_file_input = trim($_POST['nama_file'] ?? '');
    $pembuat_input = trim($_POST['pembuat_file'] ?? $nama_user);
    $file = $_FILES['file_dokumen'] ?? null;

    $_SESSION['upload_old'] = [
        'nama_file' => $nama_file_input,
        'pembuat'   => $pembuat_input,
    ];
    $_SESSION['open_upload_modal'] = true;

    if ($nama_file_input === '' || $pembuat_input === '') {
        $_SESSION['err'] = 'Nama dokumen dan pembuat wajib diisi!';
    } elseif (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $_SESSION['err'] = 'Pilih file PDF untuk diunggah!';
    } else {
        $ukuran = (int)($file['size'] ?? 0);
        $tmp_path = $file['tmp_name'] ?? '';
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

        $mime = '';
        if ($tmp_path && is_uploaded_file($tmp_path)) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $tmp_path);
                finfo_close($finfo);
            }
            if ($mime === '' && !empty($file['type'])) {
                $mime = (string)$file['type'];
            }
        }

        $allowed_mime = ['application/pdf', 'application/x-pdf', 'applications/pdf', 'application/acrobat', 'text/pdf'];
        $mime_valid = ($mime === '' && $ext === 'pdf') || in_array(strtolower($mime), $allowed_mime, true);

        if ($ext !== 'pdf' || !$mime_valid) {
            $_SESSION['err'] = 'Hanya file PDF yang diperbolehkan!';
        } elseif ($ukuran > 2097152) { // 2MB
            $_SESSION['err'] = 'Ukuran file terlalu besar! Maksimal 2MB.';
        } else {
            $upload_dir = BASE_PATH . '/upload/bank_dokumen/' . $id_kat . '/';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }

            try {
                $random = bin2hex(random_bytes(4));
            } catch (Exception $e) {
                $random = sprintf('%04x%04x', random_int(0, 0xffff), random_int(0, 0xffff));
            }

            $nama_file_unik = time() . '_' . $random . '.pdf';
            $file_path = $upload_dir . $nama_file_unik;

            if (move_uploaded_file($tmp_path, $file_path)) {
                $nama_file_db = mysqli_real_escape_string($config, $nama_file_input);
                $pembuat_db = mysqli_real_escape_string($config, $pembuat_input);
                $file_db = mysqli_real_escape_string($config, $nama_file_unik);

                $insert = mysqli_query($config, "INSERT INTO tbl_bank_dokumen_file (id_jenis, id_kategori, nama_file, file_path, pembuat, id_user, ukuran_file, tipe_file) VALUES ('$id_jenis', '$id_kat', '$nama_file_db', '$file_db', '$pembuat_db', '$id_user', '$ukuran', 'application/pdf')");

                if ($insert) {
                    unset($_SESSION['upload_old'], $_SESSION['open_upload_modal']);
                    $_SESSION['succ'] = 'Dokumen berhasil diupload!';
                    header("Location: index.php?page=admin&act=bank_dok&sub=list_dokumen&id_jenis=$id_jenis&id_kat=$id_kat");
                    die();
                }

                $_SESSION['err'] = 'Error simpan DB: ' . mysqli_error($config);
                @unlink($file_path);
            } else {
                $_SESSION['err'] = 'Gagal menyimpan file ke server!';
            }
        }
    }
}

$query_sql = "SELECT id_file, nama_file, pembuat, ukuran_file, tgl_buat, file_path FROM tbl_bank_dokumen_file WHERE id_jenis='$id_jenis' ORDER BY tgl_buat DESC";
$query_dokumen = mysqli_query($config, $query_sql);
$total_dokumen = $query_dokumen ? mysqli_num_rows($query_dokumen) : 0;

$upload_old = $_SESSION['upload_old'] ?? [];
$reopen_modal = !empty($_SESSION['open_upload_modal']);
unset($_SESSION['upload_old'], $_SESSION['open_upload_modal']);

$default_nama_file = $upload_old['nama_file'] ?? '';
$default_pembuat = $upload_old['pembuat'] ?? $nama_user;
?>

<style>
.bank-dok-header {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: #ffffff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.bank-dok-header h2 {
    margin: 0;
    font-size: 24px;
}

.bank-dok-header .breadcrumb {
    color: rgba(255, 255, 255, 0.85);
    font-size: 13px;
    margin-top: 6px;
}

.bank-dok-back {
    background: #7f8c8d;
    color: #ffffff;
    padding: 10px 15px;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 15px;
    transition: background 0.3s;
}

.bank-dok-back:hover {
    background: #34495e;
}

.bank-dok-wrapper { width: 100vw; position: relative; left: 50%; margin-left: -50vw; padding: 0 56px 40px; box-sizing: border-box; }
.bank-dok-wrapper .row { margin: 0; }
.bank-dok-wrapper .row .col { padding: 0; }
.bank-dok-wrapper .bank-dok-card { border-radius: 14px; box-shadow: 0 8px 24px rgba(23,43,77,0.14); margin: 0; }
.bank-dok-wrapper .bank-dok-card .card-content { padding: 22px 30px; }
.bank-dok-wrapper .bank-dok-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.bank-dok-wrapper .bank-dok-card h3 { margin:0; font-size:24px; font-weight:600; color:#263238; }

@media (max-width: 992px) {
    .bank-dok-wrapper { width: 100%; left: 0; margin-left: 0; padding: 0 24px 32px; }
    .bank-dok-wrapper .bank-dok-card .card-content { padding: 20px 22px; }
}

@media (max-width: 600px) {
    .bank-dok-wrapper { padding: 0 12px 24px; }
}

table.table-arsip thead th { background: #263238; color: #ffffff; }
table.table-arsip thead th,
table.table-arsip tbody td { padding: 10px 14px; text-align: center; }
table.table-arsip tbody tr:nth-child(even) { background: #f5f8fa; }
table.table-arsip td.col-name,
table.table-arsip th.col-name { text-align: left; }
table.table-arsip td.col-meta { font-size: 13px; color: #546e7a; }

table.table-arsip { width: 100%; border-collapse: collapse; }
table.table-arsip td { word-wrap: break-word; }
table.table-arsip th.aksi,
table.table-arsip td.aksi { width: 160px; }
table.table-arsip td.aksi { padding: 10px 0; }

.filters-inline { display:flex; flex-wrap:wrap; gap:18px 28px; margin:4px 0 18px; align-items:flex-end; justify-content:center; }
.filters-inline .field { display:flex; flex-direction:column; }
.filters-inline label { font-size:11px; font-weight:600; color:#455a64; margin-bottom:5px; letter-spacing:.5px; }
.filters-inline input { background:#fafafa; border:1px solid #d0d7de; border-radius:8px; height:40px; padding:0 12px; min-width:320px; }
.filters-inline button { height:40px; line-height:40px; padding:0 18px; border-radius:20px; }

@media (max-width: 600px) {
    .filters-inline input { min-width: 180px; }
}

.file-size-tag { display:inline-block; min-width:36px; text-align:center; background:transparent; color:#37474f; padding:6px 8px; border-radius:6px; font-weight:600; }

#modalUpload { display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; }
#modalUpload .modal-content { background:#ffffff; width:90%; max-width:720px; margin:6% auto; padding:20px; border-radius:8px; position:relative; }
#closeUpload { position:absolute; right:12px; top:12px; background:transparent; border:none; font-size:18px; cursor:pointer; }
#modalUpload .form-group { margin-bottom:15px; }
#modalUpload label { display:block; margin-bottom:6px; font-weight:600; color:#34495e; }
#modalUpload input[type="text"],
#modalUpload input[type="file"] { width:100%; border:1px solid #d0d7de; border-radius:6px; padding:10px; box-sizing:border-box; }
#modalUpload input[type="file"] { padding:8px; }
#modalUpload input:focus { outline:none; border-color:#3498db; box-shadow:0 0 6px rgba(52,152,219,0.35); }
.btn-submit { background:#3498db; color:#ffffff; padding:12px 28px; border:none; border-radius:6px; cursor:pointer; font-size:14px; font-weight:600; transition:background 0.3s; }
.btn-submit:hover { background:#2980b9; }
.panduan { background:#fff3cd; border-left:4px solid #ffc107; padding:10px 15px; border-radius:4px; font-size:13px; color:#856404; margin-top:8px; }

.action-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:6px; }
.action-btn i.material-icons { font-size:18px; color:inherit; line-height:1; }
.action-view { color:#1565c0; }
.action-del { color:#e53935; }
.action-btn:hover { background:rgba(0,0,0,0.04); }
.action-btn.disabled { color:#b0bec5; cursor:default; }

.bank-dok-info { font-size:13px; color:#607d8b; margin-bottom:12px; }
.aksi-group { display:flex; align-items:center; justify-content:center; gap:16px; }
</style>

<div class="bank-dok-wrapper">
<a href="index.php?page=admin&act=bank_dok&sub=list_jenis&id_kat=<?php echo $id_kat; ?>" class="bank-dok-back">← Kembali ke <?php echo htmlspecialchars($jenis_data['nama_kategori'], ENT_QUOTES); ?></a>

<div class="bank-dok-header">
    <h2>📄 <?php echo htmlspecialchars($jenis_data['nama_jenis'], ENT_QUOTES); ?></h2>
    <div class="breadcrumb">Kategori: <?php echo htmlspecialchars($jenis_data['nama_kategori'], ENT_QUOTES); ?></div>
</div>

<?php
if (isset($_SESSION['err'])) {
    echo '<div class="card red lighten-5"><div class="card-content"><span class="red-text">' . htmlspecialchars($_SESSION['err']) . '</span></div></div>';
    unset($_SESSION['err']);
}
if (isset($_SESSION['succ'])) {
    echo '<div class="card green lighten-5"><div class="card-content"><span class="green-text">' . htmlspecialchars($_SESSION['succ']) . '</span></div></div>';
    unset($_SESSION['succ']);
}
?>

<div id="modalUpload">
    <div class="modal-content">
        <button id="closeUpload">✖</button>
        <h3>Upload Dokumen Baru</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nama_file_modal">Nama Dokumen *</label>
                <input type="text" id="nama_file_modal" name="nama_file" required placeholder="Misal: Surat Penugasan Jan 2026" value="<?php echo htmlspecialchars($default_nama_file, ENT_QUOTES); ?>">
            </div>
            <div class="form-group">
                <label for="pembuat_file_modal">Nama Pembuat *</label>
                <input type="text" id="pembuat_file_modal" name="pembuat_file" required placeholder="Masukkan nama pembuat dokumen" value="<?php echo htmlspecialchars($default_pembuat, ENT_QUOTES); ?>">
            </div>
            <div class="form-group">
                <label for="file_dokumen_modal">Pilih Dokumen (PDF maks 2MB) *</label>
                <input type="file" id="file_dokumen_modal" name="file_dokumen" accept="application/pdf" required>
                <div class="panduan">Hanya file PDF dengan ukuran maksimal 2MB yang dapat diunggah.</div>
            </div>
            <button type="submit" name="submit_dokumen" class="btn-submit">💾 Simpan Dokumen</button>
        </form>
    </div>
</div>

<div class="row" style="margin-bottom:0;">
    <div class="col s12">
        <div class="card bank-dok-card">
            <div class="card-content">
                <div class="bank-dok-card-header">
                    <h3>Daftar Dokumen</h3>
                    <button id="openUpload" class="btn blue btn-pill" style="height:40px; line-height:40px; padding:0 18px;"><i class="material-icons" style="vertical-align:middle;">file_upload</i>&nbsp;Upload Dokumen</button>
                </div>

                <div class="bank-dok-info">Kelola dokumen untuk jenis berkas ini. Klik tombol upload untuk menambahkan file baru.</div>

                <div class="filters-inline">
                    <div class="field">
                        <label for="searchDokumen">Cari Dokumen</label>
                        <input type="search" id="searchDokumen" placeholder="Ketik nama dokumen atau pembuat..." value="<?php echo htmlspecialchars($search_term, ENT_QUOTES); ?>">
                    </div>
                </div>

                <table class="striped highlight table-arsip">
                    <thead>
                        <tr>
                            <th style="width:60px;">No</th>
                            <th class="col-name">Nama Dokumen</th>
                            <th class="col-meta" style="width:180px;">Pembuat</th>
                            <th style="width:140px;">Ukuran</th>
                            <th style="width:180px;">Tanggal Unggah</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query_dokumen && $total_dokumen > 0): ?>
                            <?php
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($query_dokumen)):
                                $size_display = '-';
                                if ((int)$row['ukuran_file'] > 0) {
                                    $size_display = number_format($row['ukuran_file'] / 1048576, 2) . ' MB';
                                }
                                $file_token = $row['file_path'] ?? '';
                                $file_public = $file_token !== '' ? 'upload/bank_dokumen/' . $id_kat . '/' . rawurlencode($file_token) : '';
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td class="col-name"><strong><?php echo htmlspecialchars($row['nama_file'], ENT_QUOTES); ?></strong></td>
                                <td class="col-meta"><?php echo htmlspecialchars($row['pembuat'], ENT_QUOTES); ?></td>
                                <td><span class="file-size-tag"><?php echo $size_display; ?></span></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['tgl_buat'])), ENT_QUOTES); ?></td>
                                <td class="aksi">
                                    <div class="aksi-group">
                                        <?php if ($file_public !== ''): ?>
                                        <a href="<?php echo htmlspecialchars($file_public, ENT_QUOTES); ?>" class="action-btn action-view" title="Lihat" target="_blank" rel="noopener">
                                            <i class="material-icons">visibility</i>
                                        </a>
                                        <?php else: ?>
                                        <span class="action-btn disabled" title="File tidak tersedia">
                                            <i class="material-icons">visibility_off</i>
                                        </span>
                                        <?php endif; ?>
                                        <a href="index.php?page=admin&act=bank_dok&sub=hapus_dokumen&id_file=<?php echo (int)$row['id_file']; ?>&id_jenis=<?php echo $id_jenis; ?>&id_kat=<?php echo $id_kat; ?>" class="action-btn action-del" title="Hapus" onclick="return confirm('Yakin hapus dokumen ini?')">
                                            <i class="material-icons">delete</i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:#90a4ae; padding:28px;">
                                    Belum ada dokumen. Silakan upload dokumen baru.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var openBtn = document.getElementById('openUpload');
    var closeBtn = document.getElementById('closeUpload');
    var modal = document.getElementById('modalUpload');
    if (openBtn && modal) {
        openBtn.addEventListener('click', function(e){
            e.preventDefault();
            modal.style.display = 'block';
            var input = modal.querySelector('#nama_file_modal');
            if (input) { input.focus(); }
        });
    }
    if (closeBtn && modal) {
        closeBtn.addEventListener('click', function(){
            modal.style.display = 'none';
        });
    }
    if (modal) {
        modal.addEventListener('click', function(e){
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
    if (<?php echo $reopen_modal ? 'true' : 'false'; ?> && modal) {
        modal.style.display = 'block';
    }

    var searchInput = document.getElementById('searchDokumen');
    if (searchInput) {
        var tableBody = document.querySelector('.table-arsip tbody');
        var rows = tableBody ? Array.from(tableBody.querySelectorAll('tr')) : [];
        var filterRows = function(){
            var query = searchInput.value.toLowerCase().trim();
            rows.forEach(function(row){
                var cols = row.querySelectorAll('td');
                if (!cols || cols.length < 2) { return; }
                var nama = cols[1].innerText.toLowerCase();
                var pembuat = cols[2] ? cols[2].innerText.toLowerCase() : '';
                if (query === '' || nama.indexOf(query) !== -1 || pembuat.indexOf(query) !== -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        };
        searchInput.addEventListener('input', filterRows);
        if (searchInput.value.trim() !== '') {
            filterRows();
        }
    }
});
</script>

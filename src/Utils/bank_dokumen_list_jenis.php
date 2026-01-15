<?php
// Halaman Bank Dokumen - List Jenis Berkas per Kategori

if(empty($_SESSION['admin'])){
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: index.php");
    die();
}

$allowed_roles = [1, 3, 4];
if (!in_array((int)$_SESSION['admin'], $allowed_roles)) {
    $_SESSION['err'] = '<center>Anda tidak memiliki akses!</center>';
    header("Location: index.php");
    die();
}

$id_kat = isset($_GET['id_kat']) ? (int)$_GET['id_kat'] : 0;
$id_user = (int)$_SESSION['id_user'];

// Validasi kategori
$kat_check = mysqli_query($config, "SELECT id_kategori, nama_kategori FROM tbl_bank_dokumen_kategori WHERE id_kategori='$id_kat'");
if (mysqli_num_rows($kat_check) === 0) {
    $_SESSION['err'] = 'Kategori tidak ditemukan!';
    header("Location: index.php?page=admin&act=bank_dok");
    die();
}
$kat_data = mysqli_fetch_assoc($kat_check);

// Process form tambah jenis
if (isset($_REQUEST['submit_jenis']) && in_array((int)$_SESSION['admin'], [1, 3])) {
    $nama_jenis = mysqli_real_escape_string($config, $_REQUEST['nama_jenis'] ?? '');
    $desc_jenis = mysqli_real_escape_string($config, $_REQUEST['desc_jenis'] ?? '');
    
    if (empty($nama_jenis)) {
        $_SESSION['err'] = 'Nama Jenis Berkas harus diisi!';
    } else {
        $insert = mysqli_query($config, "INSERT INTO tbl_bank_dokumen_jenis (id_kategori, nama_jenis, deskripsi) VALUES ('$id_kat', '$nama_jenis', '$desc_jenis')");
        if ($insert) {
            $_SESSION['succ'] = 'Jenis Berkas berhasil ditambahkan!';
            header("Location: index.php?page=admin&act=bank_dok&sub=list_jenis&id_kat=$id_kat");
            die();
        } else {
            $_SESSION['err'] = 'Error: ' . mysqli_error($config);
        }
    }
}

// Process form edit jenis
if (isset($_REQUEST['submit_edit']) && in_array((int)$_SESSION['admin'], [1, 3])) {
    $edit_id = isset($_REQUEST['edit_id']) ? (int)$_REQUEST['edit_id'] : 0;
    $nama_jenis = mysqli_real_escape_string($config, $_REQUEST['nama_jenis'] ?? '');
    $desc_jenis = mysqli_real_escape_string($config, $_REQUEST['desc_jenis'] ?? '');
    if ($edit_id < 1 || empty($nama_jenis)) {
        $_SESSION['err'] = 'Parameter tidak valid atau nama kosong!';
    } else {
        $update = mysqli_query($config, "UPDATE tbl_bank_dokumen_jenis SET nama_jenis='$nama_jenis', deskripsi='$desc_jenis' WHERE id_jenis=$edit_id AND id_kategori=$id_kat");
        if ($update) {
            $_SESSION['succ'] = 'Jenis Berkas berhasil diperbarui!';
            header("Location: index.php?page=admin&act=bank_dok&sub=list_jenis&id_kat=$id_kat");
            die();
        } else {
            $_SESSION['err'] = 'Error: ' . mysqli_error($config);
        }
    }
}

// Ambil daftar jenis berkas
$query_jenis = mysqli_query($config, "SELECT id_jenis, nama_jenis, deskripsi, tgl_buat FROM tbl_bank_dokumen_jenis WHERE id_kategori='$id_kat' ORDER BY tgl_buat ASC, id_jenis ASC");

// Hitung total file per jenis
$file_count = [];
$query_count = mysqli_query($config, "SELECT id_jenis, COUNT(*) as total FROM tbl_bank_dokumen_file WHERE id_kategori='$id_kat' GROUP BY id_jenis");
while ($row = mysqli_fetch_assoc($query_count)) {
    $file_count[$row['id_jenis']] = $row['total'];
}
?>

<style>
.bank-dok-header {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.bank-dok-header h2 {
    margin: 0;
    font-size: 24px;
}

.bank-dok-back {
    background: #7f8c8d;
    color: white;
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

.table-responsive {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.bank-dok-table {
    width: 100%;
    border-collapse: collapse;
}

.bank-dok-table th {
    background: #34495e;
    color: white;
    padding: 12px;
    text-align: left;
    font-weight: 600;
}

.bank-dok-table td {
    padding: 12px;
    border-bottom: 1px solid #ecf0f1;
}

.bank-dok-table tbody tr:hover {
    background: #ecf0f1;
}

.bank-dok-table .aksi {
    text-align: center;
}

.btn-lihat, .btn-hapus {
    padding: 8px 12px;
    margin: 0 4px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s;
}

.btn-lihat {
    background: #27ae60;
    color: white;
}

.btn-lihat:hover {
    background: #229954;
}

.btn-hapus {
    background: #e74c3c;
    color: white;
}

.btn-hapus:hover {
    background: #c0392b;
}

.form-tambah {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #34495e;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #bdc3c7;
    border-radius: 4px;
    font-family: Arial, sans-serif;
    box-sizing: border-box;
}

.form-group textarea {
    resize: vertical;
    min-height: 70px;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
}

.btn-submit {
    background: #3498db;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s;
}

.btn-submit:hover {
    background: #2980b9;
}

.panduan {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 10px 15px;
    border-radius: 4px;
    font-size: 13px;
    color: #856404;
    margin-bottom: 15px;
}
</style>
<style>
/* Reuse visual style from arsip_per_bidang for consistent look */
.bank-dok-header {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.bank-dok-header h2 { margin:0; font-size:24px }
.bank-dok-back { background:#7f8c8d; color:white; padding:10px 15px; border-radius:5px; display:inline-block; margin-bottom:15px }

/* Filters inline (single centered search) */
.filters-inline{display:flex;flex-wrap:wrap;gap:18px 28px;margin:4px 0 12px;align-items:flex-end;justify-content:center}
.filters-inline .field{display:flex;flex-direction:column}
.filters-inline label{font-size:11px;font-weight:600;color:#455a64;margin-bottom:5px;letter-spacing:.5px}
.filters-inline input{background:#fafafa;border:1px solid #d0d7de;border-radius:8px;height:40px;padding:0 12px;min-width:320px}
.btn-pill{border-radius:20px}

/* Table style matching arsip — force full-width and spacing */
table.table-arsip thead th{background:#263238;color:#fff}
table.table-arsip thead th, table.table-arsip tbody td{padding:10px 14px;text-align:center}
table.table-arsip tbody tr:nth-child(even){background:#f5f8fa}
.right-actions{margin-top:-6px}
@media(max-width:600px){.filters-inline input{min-width:150px}}

/* Layout wrapper ensures content spans container width */
.bank-dok-wrapper { width: 100vw; position: relative; left: 50%; margin-left: -50vw; padding: 0 56px 40px; box-sizing: border-box; }
.bank-dok-wrapper .row { margin: 0; }
.bank-dok-wrapper .row .col { padding: 0; }
.bank-dok-wrapper .bank-dok-card { border-radius: 14px; box-shadow: 0 8px 24px rgba(23,43,77,0.14); margin: 0; }
.bank-dok-wrapper .bank-dok-card .card-content { padding: 22px 30px; }
.bank-dok-wrapper .bank-dok-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.bank-dok-wrapper .bank-dok-card h3 { margin: 0; font-size: 24px; font-weight: 600; color: #263238; }

@media (max-width: 992px) {
    .bank-dok-wrapper { width: 100%; left: 0; margin-left: 0; padding: 0 24px 32px; }
    .bank-dok-wrapper .bank-dok-card .card-content { padding: 20px 22px; }
}

@media (max-width: 600px) {
    .bank-dok-wrapper { padding: 0 12px 24px; }
}

table.table-arsip { width: 100%; border-collapse: collapse; }
table.table-arsip td { word-wrap: break-word; }
table.table-arsip th.col-desc, table.table-arsip td.col-desc { width: 220px; }
table.table-arsip td.col-desc { max-width: 220px; }
table.table-arsip th.aksi, table.table-arsip td.aksi { width: 160px; }
table.table-arsip th.aksi { text-align: center; }
table.table-arsip td.aksi { padding: 10px 0; }
.aksi-group { display: flex; align-items: center; justify-content: center; gap: 16px; }

/* Modal tweaks */
#modalTambah .modal-content{border-radius:8px;padding:18px}
#modalTambah .form-group input, #modalTambah .form-group textarea{height:38px;padding:6px 10px}

/* Action buttons: spacing and colors to match arsip page */
.action-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:6px; }
.action-btn i.material-icons{font-size:18px; color:inherit; line-height:1;}
.action-edit { color:#1976d2 }
.action-view { color:#1565c0 }
.action-del { color:#e53935 }
.action-btn:hover { background: rgba(0,0,0,0.04); }

.bank-dok-wrapper .filters-inline { justify-content: center; margin-bottom: 16px; }

/* Total Dokumen cell plain number styling (remove gray badge) */
.total-doc { display:inline-block; min-width:36px; text-align:center; background: transparent; color:#37474f; padding:6px 8px; border-radius:6px; font-weight:600; }

</style>
<div class="bank-dok-wrapper">
<a href="index.php?page=admin&act=bank_dok" class="bank-dok-back">← Kembali ke Bank Dokumen</a>

<!-- Header -->
<div class="bank-dok-header">
    <h2>📂 <?php echo htmlspecialchars($kat_data['nama_kategori'], ENT_QUOTES); ?></h2>
    <p>Kelola jenis berkas dan dokumen</p>
</div>

<!-- Error/Success Messages -->
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

<!-- Modal Tambah Jenis -->
<div id="modalTambah" class="modal" style="display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div class="modal-content" style="background:white; width:90%; max-width:720px; margin:6% auto; padding:20px; border-radius:8px; position:relative;">
        <button id="closeTambah" style="position:absolute; right:12px; top:12px; background:transparent; border:none; font-size:18px; cursor:pointer;">✖</button>
        <h3>Tambah Jenis Berkas Baru</h3>
        <form method="POST">
            <input type="hidden" id="edit_id" name="edit_id" value="">
            <div class="form-group">
                <label for="nama_jenis">Nama Jenis Berkas *</label>
                <input type="text" id="nama_jenis" name="nama_jenis" required placeholder="Misal: IT/Kepegawaian, Asuransi, dll">
                <div class="panduan">💡 Panduan: Masukkan nama jenis berkas seperti IT/Kepegawaian, Asuransi, Administrasi, dll sesuai dengan kategori.</div>
            </div>

            <div class="form-group">
                <label for="desc_jenis">Deskripsi (Opsional)</label>
                <textarea id="desc_jenis" name="desc_jenis" placeholder="Deskripsi singkat jenis berkas ini..."></textarea>
            </div>

            <button type="submit" id="submitBtn" name="submit_jenis" class="btn-submit">💾 Simpan Jenis Berkas</button>
        </form>
    </div>
</div>

<!-- Daftar Jenis Berkas -->
<div class="row" style="margin-bottom:0;">
    <div class="col s12">
        <div class="card bank-dok-card">
            <div class="card-content">
                <div class="bank-dok-card-header">
                    <h3>Daftar Jenis Berkas</h3>
                </div>

                <div class="filters-inline">
                    <div class="field">
                        <label>Cari Jenis Berkas</label>
                        <input type="search" id="searchJenis" placeholder="Ketik nama jenis berkas..." class="browser-default">
                    </div>
                    <div style="display:flex; align-items:flex-end;">
                        <?php if (in_array((int)$_SESSION['admin'], [1, 3])): ?>
                        <button id="openTambah" class="btn blue btn-pill" style="height:40px; line-height:40px; padding:0 14px;"><i class="material-icons" style="vertical-align:middle;">add</i>&nbsp;Tambah Berkas</button>
                        <?php endif; ?>
                    </div>
                </div>

                <table class="striped highlight table-arsip">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th style="width:220px;">Jenis Berkas</th>
                        <th class="col-desc">Deskripsi</th>
                        <th style="width:140px;">Total Dokumen</th>
                        <th class="aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($query_jenis)):
                        $total_doc = $file_count[$row['id_jenis']] ?? 0;
                    ?>
                    <tr data-id="<?php echo (int)$row['id_jenis']; ?>" data-nama="<?php echo htmlspecialchars($row['nama_jenis'], ENT_QUOTES); ?>" data-desc="<?php echo htmlspecialchars($row['deskripsi'] ?? '', ENT_QUOTES); ?>">
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['nama_jenis'], ENT_QUOTES); ?></strong></td>
                        <td class="col-desc"><?php echo htmlspecialchars($row['deskripsi'] ?? '-', ENT_QUOTES); ?></td>
                        <td><span class="total-doc"><?php echo $total_doc; ?></span></td>
                        <td class="aksi">
                            <div class="aksi-group">
                                <?php if (in_array((int)$_SESSION['admin'], [1, 3])): ?>
                                <a href="#" class="action-btn action-edit" title="Edit" data-id="<?php echo (int)$row['id_jenis']; ?>"><i class="material-icons">edit</i></a>
                                <?php endif; ?>
                                <a href="index.php?page=admin&act=bank_dok&sub=list_dokumen&id_jenis=<?php echo (int)$row['id_jenis']; ?>&id_kat=<?php echo $id_kat; ?>" class="action-btn action-view" title="Lihat"><i class="material-icons">visibility</i></a>
                                <?php if (in_array((int)$_SESSION['admin'], [1, 3])): ?>
                                <a href="index.php?page=admin&act=bank_dok&sub=hapus_jenis&id_jenis=<?php echo (int)$row['id_jenis']; ?>&id_kat=<?php echo $id_kat; ?>" class="action-btn action-del" title="Hapus" onclick="return confirm('Yakin hapus?')"><i class="material-icons">delete</i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if (mysqli_num_rows($query_jenis) === 0): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #999;">Belum ada jenis berkas. Silakan tambahkan jenis berkas baru.</td>
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
// Modal open/close
document.addEventListener('DOMContentLoaded', function(){
    var openBtn = document.getElementById('openTambah');
    var closeBtn = document.getElementById('closeTambah');
    var modal = document.getElementById('modalTambah');
    var modalContent = modal ? modal.querySelector('.modal-content') : null;

    if (openBtn && modal) {
        openBtn.addEventListener('click', function(e){
            e.preventDefault();
            modal.style.display = 'block';
            var input = modal.querySelector('#nama_jenis');
            if (input) input.focus();
            // prepare for add mode
            var editId = modal.querySelector('#edit_id'); if(editId) editId.value='';
            var submit = document.getElementById('submitBtn'); if(submit){ submit.name='submit_jenis'; submit.innerText='💾 Simpan Jenis Berkas'; }
            var title = modal.querySelector('h3'); if(title) title.innerText='Tambah Jenis Berkas Baru';
        });
    }

    if (closeBtn && modal) {
        closeBtn.addEventListener('click', function(){
            modal.style.display = 'none';
        });
    }

    // close when click outside content
    if (modal) {
        modal.addEventListener('click', function(e){
            if (e.target === modal) modal.style.display = 'none';
        });
    }

    // edit button handler: populate modal and switch to edit mode
    document.querySelectorAll('.action-edit').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            var id = this.getAttribute('data-id');
            if(!id) return;
            // find row data
            var tr = document.querySelector('tr[data-id="'+id+'"]');
            if(!tr) return;
            var nama = tr.getAttribute('data-nama') || '';
            var desc = tr.getAttribute('data-desc') || '';
            modal.style.display = 'block';
            var input = modal.querySelector('#nama_jenis'); if(input){ input.value = nama; input.focus(); }
            var ta = modal.querySelector('#desc_jenis'); if(ta) ta.value = desc;
            var editId = modal.querySelector('#edit_id'); if(editId) editId.value = id;
            var submit = document.getElementById('submitBtn'); if(submit){ submit.name='submit_edit'; submit.innerText='💾 Perbarui Jenis Berkas'; }
            var title = modal.querySelector('h3'); if(title) title.innerText='Edit Jenis Berkas';
        });
    });

    // Simple client-side search/filter
    var search = document.getElementById('searchJenis');
    if (search) {
        search.addEventListener('input', function(){
            var q = this.value.toLowerCase().trim();
            var table = document.querySelector('.table-arsip tbody');
            if (!table) return;
            var rows = table.querySelectorAll('tr');
            rows.forEach(function(r){
                // skip empty/placeholder rows
                var cols = r.querySelectorAll('td');
                if (!cols || cols.length < 2) return;
                var nama = cols[1].innerText.toLowerCase();
                if (nama.indexOf(q) !== -1) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php
?>

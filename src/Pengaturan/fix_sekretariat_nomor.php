<?php
// Hanya Super Admin
if (empty($_SESSION['admin']) || (int)$_SESSION['admin'] !== 1) {
    echo '<script>window.alert("Akses ditolak"); window.location.href="index.php";</script>';
    exit;
}
require_once __DIR__ . '/../include/config.php';

// UI header
echo '<div class="row"><div class="col s12"><div class="z-depth-1"><nav class="secondary-nav"><div class="nav-wrapper blue-grey darken-1"><div class="col m12"><ul class="left"><li class="waves-effect waves-light"><a href="index.php?page=admin&act=sett&sub=fixsek" class="judul"><i class="material-icons">build</i> Perbaiki Nomor Sekretariat</a></li></ul></div></div></nav></div></div></div>';

$run = isset($_POST['run_fix']);

if ($run) {
    // Kriteria: user SEKRETARIAT/TU atau bidang yang semestinya 104.1 tapi nomor berbeda di bagian bidang (segmen ke-3) => kita ganti menjadi 104.1
    // Format no_surat saat ini: {kode}/{noagendak}/{bidang}/{tahun}
    $updated = 0; $skipped = 0; $errors = 0;

    // Ambil semua baris yang terindikasi milik Sekretariat
    $rs = mysqli_query($config, "SELECT id_surat,no_surat,bidang FROM tbl_surat_keluar WHERE id_user IN (SELECT id_user FROM tbl_user WHERE UPPER(username) IN ('SEKRETARIAT','TU'))");
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $id = (int)$row['id_surat'];
            $no = $row['no_surat'];
            $currentBid = trim($row['bidang']);
            $parts = explode('/', $no);
            if (count($parts) === 4) {
                $parts[2] = '104.1'; // set bidang segmen ke-3
                $newNo = implode('/', $parts);
                // Hanya update jika berbeda atau jika kolom bidang bukan 104.1
                if ($newNo !== $no || $currentBid !== '104.1') {
                    $ok = mysqli_query($config, "UPDATE tbl_surat_keluar SET no_surat='" . mysqli_real_escape_string($config,$newNo) . "', bidang='104.1' WHERE id_surat=".$id);
                    if ($ok) { $updated++; } else { $errors++; }
                } else {
                    $skipped++;
                }
            } else {
                // Format tidak sesuai, lewati
                $skipped++;
            }
        }
    }

    echo '<div class="row"><div class="col m12"><div class="card"><div class="card-content">';
    echo '<span class="card-title">Hasil Perbaikan</span>';
    echo '<ul class="collection">';
    echo '<li class="collection-item">Berhasil diupdate: <strong>'.$updated.'</strong></li>';
    echo '<li class="collection-item">Dilewati: <strong>'.$skipped.'</strong></li>';
    echo '<li class="collection-item">Gagal: <strong>'.$errors.'</strong></li>';
    echo '</ul>';
    echo '</div></div></div></div>';
} else {
    // Form konfirmasi
    echo '<div class="row"><div class="col m12"><div class="card"><div class="card-content">';
    echo '<span class="card-title">Perbaiki Nomor Surat Sekretariat</span>';
    echo '<p>Klik tombol di bawah untuk mengembalikan kode bidang pada nomor surat lamanya Sekretariat menjadi <strong>104.1</strong> (segmen ketiga nomor).</p>';
    echo '<form method="post"><button type="submit" name="run_fix" class="btn red"><i class="material-icons left">build</i>Jalankan Perbaikan</button></form>';
    echo '</div></div></div></div>';
}
?>

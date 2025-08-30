<?php
// filepath: c:\laragon\www\ams\src\SuratKeluar\transaksi_surat_keluar.php
//cek session
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: index.php");
    die();
} else {
    $id_user = $_SESSION['id_user'];
    if ($_SESSION['admin'] == 5) {
        echo '<script language="javascript">
                    window.alert("ERROR! Anda tidak memiliki hak akses untuk membuka halaman ini");
                    window.location.href="index.php?page=logout";
                  </script>';
    } else {

        // Bagian ini menangani sub-halaman seperti tambah, edit, hapus
        if (isset($_REQUEST['sub'])) {
            $sub = $_REQUEST['sub'];
            switch ($sub) {
                case 'add':
                    include 'tambah_surat_keluar.php';
                    break;
                case 'edit':
                    include 'edit_surat_keluar.php';
                    break;
                case 'del':
                    include 'hapus_surat_keluar.php';
                    break;
                case 'proses_tambah':
                    include 'proses_tambah_surat_keluar.php';
                    break;
            }
        } else {

            // Pengaturan untuk paginasi (jumlah data per halaman)
            $query_sett = mysqli_query($config, "SELECT surat_keluar FROM tbl_sett");
            list($surat_keluar) = mysqli_fetch_array($query_sett);
            $limit = $surat_keluar;
            $pg = @$_GET['pg'];
            if (empty($pg)) {
                $curr = 0;
                $pg = 1;
            } else {
                $curr = ($pg - 1) * $limit;
            }

            // Fungsi untuk mengubah format tanggal ke format Indonesia
            if (!function_exists('indoDate')) {
                function indoDate($date)
                {
                    $bulan = array(1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember');
                    $exp = explode('-', $date);
                    return count($exp) == 3 ? $exp[2] . ' ' . $bulan[(int)$exp[1]] . ' ' . $exp[0] : $date;
                }
            }
?>

            <!-- Tampilan Header Halaman -->
            <div class="row">
                <div class="col s12">
                    <div class="z-depth-1">
                        <nav class="secondary-nav">
                            <div class="nav-wrapper blue-grey darken-1">
                                <div class="col m7">
                                    <ul class="left">
                                        <li class="waves-effect waves-light hide-on-small-only"><a href="index.php?page=admin&act=tsk" class="judul"><i class="material-icons">drafts</i> Surat Keluar</a></li>
                                        <li class="waves-effect waves-light">
                                            <!-- Tombol Tambah Data: Tampil untuk semua level admin -->
                                            <a href="index.php?page=admin&act=tsk&sub=add"><i class="material-icons md-24">add_circle</i> Tambah Data</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col m5 hide-on-med-and-down">
                                    <form method="post" action="index.php?page=admin&act=tsk">
                                        <div class="input-field round-in-box">
                                            <input id="search" type="search" name="cari" placeholder="Ketik dan tekan enter mencari data..." required>
                                            <label for="search"><i class="material-icons">search</i></label>
                                            <input type="submit" name="submit" class="hidden">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Notifikasi Sukses -->
            <?php
            if (isset($_SESSION['succAdd'])) {
                $succAdd = $_SESSION['succAdd'];
                echo '<div id="alert-message" class="row"><div class="col m12"><div class="card green lighten-5"><div class="card-content notif"><span class="card-title green-text"><i class="material-icons md-36">done</i> ' . $succAdd . '</span></div></div></div></div>';
                unset($_SESSION['succAdd']);
            }
            if (isset($_SESSION['succEdit'])) {
                $succEdit = $_SESSION['succEdit'];
                echo '<div id="alert-message" class="row"><div class="col m12"><div class="card green lighten-5"><div class="card-content notif"><span class="card-title green-text"><i class="material-icons md-36">done</i> ' . $succEdit . '</span></div></div></div></div>';
                unset($_SESSION['succEdit']);
            }
            if (isset($_SESSION['succDel'])) {
                $succDel = $_SESSION['succDel'];
                echo '<div id="alert-message" class="row"><div class="col m12"><div class="card green lighten-5"><div class="card-content notif"><span class="card-title green-text"><i class="material-icons md-36">done</i> ' . $succDel . '</span></div></div></div></div>';
                unset($_SESSION['succDel']);
            }
            ?>

            <!-- Tampilan Tabel Data -->
            <div class="row jarak-form">
                <div class="col m12" id="colres">
                    <div class="card">
                        <div class="card-content">
                            <?php
                            if (isset($_REQUEST['submit'])) {
                                $cari = mysqli_real_escape_string($config, $_REQUEST['cari']);
                                echo '<div class="card-panel blue-grey lighten-5" style="margin-bottom: 20px;"><p class="blue-grey-text">Hasil pencarian untuk: <strong class="black-text">' . stripslashes($cari) . '</strong></p></div>';
                            }
                            ?>
                            <div class="table-responsive">
                                <table class="striped highlight responsive-table" id="tbl">
                                    <thead class="blue lighten-4" id="head">
                                        <tr>
                                            <th width="12%" class="center-align no-wrap">No. Agenda<br /><small>Kode</small></th>
                                            <th width="15%">Isi Ringkas<br /><small>File</small></th>
                                            <th width="20%" class="center-align">Tujuan<br /><small>Perihal</small></th>
                                            <th width="15%" class="center-align">No. Surat<br /><small>Tgl Surat</small></th>
                                            <th width="23%" class="center-align">Pembuat<br /><small>Tgl Dibuat</small></th>
                                            <th width="10%" class="center-align">
                                                <div style="display: flex; justify-content: center; align-items: center; gap: 8px;">
                                                    Tindakan
                                                    <a class="modal-trigger tooltipped" href="#modal" data-position="left" data-tooltip="Atur jumlah data"><i class="material-icons" style="color: #333;">settings</i></a>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // PENERAPAN LOGIKA HAK AKSES DIMULAI DI SINI
                                        $is_admin_user = ($_SESSION['admin'] == 4);
                                        $base_query = "FROM tbl_surat_keluar";
                                        $where_clause = "";

                                        // 1. Filter Data: Jika Admin User, hanya tampilkan data miliknya
                                        if ($is_admin_user) {
                                            $where_clause .= " WHERE id_user='$id_user'";
                                        }

                                        // Tambahkan filter pencarian jika ada
                                        if (isset($_REQUEST['submit'])) {
                                            $cari = mysqli_real_escape_string($config, $_REQUEST['cari']);
                                            $search_condition = "(isi LIKE '%$cari%' OR perihal LIKE '%$cari%' OR tujuan LIKE '%$cari%')";
                                            $where_clause .= ($is_admin_user ? " AND " : " WHERE ") . $search_condition;
                                        }

                                        // Query untuk mengambil data sesuai hak akses
                                        $query = mysqli_query($config, "SELECT * " . $base_query . $where_clause . " ORDER BY id_surat DESC LIMIT $curr, $limit");

                                        if (mysqli_num_rows($query) > 0) {
                                            while ($row = mysqli_fetch_array($query)) {
                                                echo '
                                                <tr style="vertical-align: top;">
                                                    <td class="center-align">' . $row['no_agenda'] . '<hr class="grey lighten-3" style="margin: 4px 0;"/>' . $row['kode'] . '</td>
                                                    <td>' . $row['isi'];

                                                if (!empty($row['file'])) {
                                                    echo '<br/><br/><strong>File : </strong>';
                                                    if ($_SESSION['admin'] == 1) {
                                                        // Super Admin: langsung buka di tab baru tanpa PIN modal
                                                        echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat=' . $row['id_surat'] . '" target="_blank" rel="noopener" style="text-decoration: underline;">' . $row['file'] . '</a>';
                                                    } else {
                                                        // Selain Super Admin: gunakan PIN modal
                                                        echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat=' . $row['id_surat'] . '" class="pin-trigger" data-action-type="view" data-id-surat="' . $row['id_surat'] . '" style="text-decoration: underline;">' . $row['file'] . '</a>';
                                                    }
                                                    if (!empty($_SESSION['pinResetIds'][$row['id_surat']])) {
                                                        echo ' <span class="new badge blue" data-badge-caption="PIN diubah" title="PIN direset oleh admin"></span>';
                                                    }
                                                }

                                                echo '</td>
                                                    <td class="center-align">' . $row['tujuan'] . '<br/><small class="grey-text text-darken-1">' . $row['perihal'] . '</small></td>
                                                    <td class="center-align">' . $row['no_surat'] . '<br/><small class="grey-text text-darken-1 nowrap">' . indoDate($row['tgl_surat']) . '</small></td>
                                                    <td class="center-align">' . $row['nama_pembuat'] . '<br/><small class="grey-text text-darken-1 nowrap">' . (isset($row['tgl_dibuat']) ? date('d M Y, H:i', strtotime($row['tgl_dibuat'])) : '') . '</small></td>
                                                    <td class="center-align">';

                                                // 2. Batasi Tombol: Super Admin & Verifikator bisa semua, Admin User hanya data miliknya
                                                $can_manage = in_array($_SESSION['admin'], [1, 2, 3]); // Super Admin & Verifikator
                                                $is_owner = $row['id_user'] == $_SESSION['id_user'];

                                                if ($can_manage || $is_owner) {
                                                    echo '<div class="actions-compact" style="display: flex; justify-content: center; gap: 0px; padding-top: 5px;">';
                                                    if ($_SESSION['admin'] == 1) {
                                                        // Super Admin: langsung edit/hapus tanpa PIN modal
                                                        echo '<a class="btn small blue waves-effect waves-light" style="color:white;" href="?page=admin&act=tsk&sub=edit&id_surat=' . $row['id_surat'] . '"><i class="material-icons" style="color:white;">edit</i> EDIT</a>';
                                                        echo '<a class="btn small deep-orange waves-effect waves-light" style="color:white;" href="?page=admin&act=tsk&sub=del&id_surat=' . $row['id_surat'] . '" onclick="return confirm(\'Yakin ingin menghapus surat ini?\');"><i class="material-icons" style="color:white;">delete</i> DEL</a>';
                                                    } else {
                                                        // Selain Super Admin: gunakan PIN modal
                                                        echo '<a class="btn small blue waves-effect waves-light pin-trigger" style="color:white;" href="?page=admin&act=tsk&sub=edit&id_surat=' . $row['id_surat'] . '" data-action-type="edit" data-id-surat="' . $row['id_surat'] . '"><i class="material-icons" style="color:white;">edit</i> EDIT</a>';
                                                        echo '<a class="btn small deep-orange waves-effect waves-light pin-trigger" style="color:white;" href="?page=admin&act=tsk&sub=del&id_surat=' . $row['id_surat'] . '" data-action-type="delete" data-id-surat="' . $row['id_surat'] . '"><i class="material-icons" style="color:white;">delete</i> DEL</a>';
                                                    }
                                                    echo '</div>';
                                                } else {
                                                    echo '<div class="grey-text" style="padding-top: 15px;">-</div>';
                                                }

                                                echo '</td>
                                                </tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="5" class="center-align"><div class="card-panel grey lighten-4" style="margin: 20px;">';
                                            if (isset($_REQUEST['submit'])) {
                                                echo '<i class="material-icons large grey-text">search</i><p class="grey-text">Tidak ada data yang ditemukan untuk pencarian "<strong>' . stripslashes($cari) . '</strong>"</p>';
                                            } else {
                                                echo '<i class="material-icons large grey-text">inbox</i><p class="grey-text">Tidak ada data untuk ditampilkan.</p>';
                                            }
                                            echo '</div></td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Pengaturan Paginasi -->
            <div id="modal" class="modal">
                <div class="modal-content white">
                    <h5>Jumlah data yang ditampilkan per halaman</h5>
                    <?php
                    $query_sett_modal = mysqli_query($config, "SELECT id_sett, surat_keluar FROM tbl_sett");
                    list($id_sett, $surat_keluar_sett) = mysqli_fetch_array($query_sett_modal);
                    ?>
                    <div class="row">
                        <form method="post" action="">
                            <div class="input-field col s12">
                                <input type="hidden" value="<?php echo $id_sett; ?>" name="id_sett">
                                <div class="input-field col s1" style="float: left;"><i class="material-icons prefix md-prefix">looks_one</i></div>
                                <div class="input-field col s11 right" style="margin: -5px 0 20px;">
                                    <select class="browser-default validate" name="surat_keluar" required>
                                        <option value="<?php echo $surat_keluar_sett; ?>"><?php echo $surat_keluar_sett; ?></option>
                                        <option value="5">5</option>
                                        <option value="10">10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <div class="modal-footer white">
                                    <button type="submit" class="modal-action waves-effect waves-green btn-flat" name="simpan">Simpan</button>
                                    <?php
                                    if (isset($_REQUEST['simpan'])) {
                                        $id_sett_upd = "1";
                                        $surat_keluar_upd = $_REQUEST['surat_keluar'];
                                        $id_user_upd = $_SESSION['id_user'];
                                        $query_upd = mysqli_query($config, "UPDATE tbl_sett SET surat_keluar='$surat_keluar_upd', id_user='$id_user_upd' WHERE id_sett='$id_sett_upd'");
                                        if ($query_upd) {
                                            header("Location: index.php?page=admin&act=tsk");
                                            die();
                                        }
                                    }
                                    ?>
                                    <a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Batal</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Paginasi -->
<?php
            $query_pg = mysqli_query($config, "SELECT 1 " . $base_query . $where_clause);
            $cdata = mysqli_num_rows($query_pg);
            $cpg = ceil($cdata / $limit);

            echo '<br/><!-- Pagination START --><ul class="pagination">';
            if ($cdata > $limit) {
                if ($pg > 1) {
                    $prev = $pg - 1;
                    echo '<li><a href="index.php?page=admin&act=tsk&pg=1"><i class="material-icons md-48">first_page</i></a></li><li><a href="index.php?page=admin&act=tsk&pg=' . $prev . '"><i class="material-icons md-48">chevron_left</i></a></li>';
                } else {
                    echo '<li class="disabled"><a><i class="material-icons md-48">first_page</i></a></li><li class="disabled"><a><i class="material-icons md-48">chevron_left</i></a></li>';
                }

                if ($pg < $cpg) {
                    $next = $pg + 1;
                    echo '<li><a href="index.php?page=admin&act=tsk&pg=' . $next . '"><i class="material-icons md-48">chevron_right</i></a></li><li><a href="index.php?page=admin&act=tsk&pg=' . $cpg . '"><i class="material-icons md-48">last_page</i></a></li>';
                } else {
                    echo '<li class="disabled"><a><i class="material-icons md-48">chevron_right</i></a></li><li class="disabled"><a><i class="material-icons md-48">last_page</i></a></li>';
                }
            }
            echo '</ul><!-- Pagination END -->';
        }
    }
}
?>

<style>
    /* Utility: prevent wrapping for specific header text */
    th.no-wrap { white-space: nowrap; }
    /* Utility: no wrap inline elements */
    .nowrap { white-space: nowrap; }
    /* Compact actions: remove spacing between buttons */
    .actions-compact a.btn { margin-left: 0 !important; margin-right: 6px !important; }
    .actions-compact a.btn:last-child { margin-right: 0 !important; }

    /* Cross-browser table stability (Chrome/Edge/Firefox) */
    #tbl { table-layout: fixed; width: 100%; border-collapse: collapse; }
    #tbl thead th, #tbl tbody td { box-sizing: border-box; }
    /* Enforce column widths via CSS to be consistent across browsers */
    #tbl thead th:nth-child(1) { width: 10%; }
    #tbl thead th:nth-child(2) { width: 30%; }
    #tbl thead th:nth-child(3) { width: 14%; }
    #tbl thead th:nth-child(4) { width: 18%; }
    #tbl thead th:nth-child(5) { width: 12%; }
    #tbl thead th:nth-child(6) { width: 15%; }
    /* Make second-line text consistent across browsers */
    #tbl small { display: block; margin-top: 2px; line-height: 1.2; font-size: 0.9rem; }
    /* Better wrapping for long content like numbers/paths */
    #tbl td { overflow-wrap: anywhere; word-break: break-word; }
    /* CSS untuk Modal PIN */
    .pin-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1002;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }
    .pin-modal-container {
        background-color: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        padding: 30px;
        width: 100%;
        max-width: 450px;
        text-align: center;
        position: relative;
        transform: scale(0.9);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    .pin-modal-overlay.active .pin-modal-container {
        transform: scale(1);
        opacity: 1;
    }
    .pin-modal-close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 2rem;
        color: #9e9e9e;
        cursor: pointer;
        transition: color 0.2s;
    }
    .pin-modal-close:hover {
        color: #616161;
    }
    .pin-modal-title {
        font-size: 1.8rem;
        font-weight: 500;
        margin-bottom: 10px;
        color: #424242;
    }
    .pin-code-container {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin: 30px 0;
    }
    .pin-code-input {
        width: 45px !important;
        height: 55px !important;
        font-size: 24px !important;
        text-align: center !important;
        border: 2px solid #bdbdbd !important;
        border-radius: 8px !important;
        box-shadow: none !important;
        padding: 0 !important;
        background-color: #fff !important;
    }
    .pin-code-input:focus {
        border-color: #2196F3 !important;
        box-shadow: 0 0 8px 0 rgba(33, 150, 243, 0.5) !important;
    }
    .pin-modal-btn {
        border-radius: 25px;
        height: 45px;
        line-height: 45px;
    }
    .pin-error-message {
        color: #f44336;
        margin-top: 15px;
        font-weight: 500;
        min-height: 21px;
    }
</style>

<!-- HTML untuk Modal PIN -->
<div id="pinModal" class="pin-modal-overlay">
    <div class="pin-modal-container">
        <span class="pin-modal-close">&times;</span>
        <i class="material-icons large blue-grey-text text-darken-1">https</i>
        <h5 class="pin-modal-title">Verifikasi PIN</h5>
        <p class="grey-text text-darken-1">Aksi ini memerlukan izin. Silakan masukkan 6 digit PIN.</p>
        
        <form id="pinForm" method="POST" action="#">
            <input type="hidden" name="pin" id="fullPin">
            <div class="pin-code-container">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="tel" class="pin-code-input" maxlength="1" pattern="[0-9]" required>
                <?php endfor; ?>
            </div>
            <p id="pinErrorMessage" class="pin-error-message"></p>
            <div class="row">
                <div class="input-field col s12">
                    <button type="submit" class="btn waves-effect waves-light blue darken-1 pin-modal-btn">Verifikasi & Lanjutkan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('pinModal');
    const modalClose = modal.querySelector('.pin-modal-close');
    const pinForm = document.getElementById('pinForm');
    const pinInputs = [...pinForm.querySelectorAll('.pin-code-input')];
    const fullPinInput = document.getElementById('fullPin');
    const errorMessage = document.getElementById('pinErrorMessage');
    
    let targetUrl = '';
    let actionType = '';
    let suratId = '';

    // Fungsi untuk membuka modal
    function openModal() {
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
        pinInputs[0].focus();
    }

    // Fungsi untuk menutup modal
    function closeModal() {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
            resetModal();
        }, 300);
    }

    // Fungsi untuk mereset modal
    function resetModal() {
        pinForm.reset();
        pinInputs.forEach(input => input.value = '');
        errorMessage.textContent = '';
        targetUrl = '';
        actionType = '';
        suratId = '';
    }

    // Event listener untuk semua tombol yang butuh PIN
    document.querySelectorAll('.pin-trigger').forEach(trigger => {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            targetUrl = this.getAttribute('href');
            actionType = this.dataset.actionType;
            suratId = this.dataset.idSurat;
            openModal();
        });
    });

    // Event listener untuk tombol close
    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Logika input PIN
    pinInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value && index < pinInputs.length - 1) {
                pinInputs[index + 1].focus();
            }
            updateFullPin();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && index > 0) {
                pinInputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/\s/g, '').slice(0, 6);
            pasteData.split('').forEach((char, i) => {
                if (pinInputs[i]) pinInputs[i].value = char;
            });
            const lastInputIndex = Math.min(pasteData.length, 6) - 1;
            if (lastInputIndex >= 0) pinInputs[lastInputIndex].focus();
            updateFullPin();
        });
    });

    function updateFullPin() {
        fullPinInput.value = pinInputs.map(i => i.value).join('');
    }

    // Submit form PIN
    pinForm.addEventListener('submit', function (e) {
        e.preventDefault();
        updateFullPin();

        if (fullPinInput.value.length !== 6) {
            errorMessage.textContent = 'PIN harus terdiri dari 6 digit.';
            return;
        }

        errorMessage.textContent = '';
        const formData = new FormData();
        formData.append('id_surat', suratId);
        formData.append('pin', fullPinInput.value);

        fetch('src/Utils/verifikasi_pin_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                handleSuccessfulVerification();
            } else {
                errorMessage.textContent = data.message || 'PIN salah. Coba lagi.';
                pinInputs.forEach(input => input.value = '');
                pinInputs[0].focus();
            }
        })
        .catch(error => {
            errorMessage.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
            console.error('Error:', error);
        });
    });

    function handleSuccessfulVerification() {
        closeModal();
        
        // Sedikit penundaan agar modal sempat tertutup
        setTimeout(() => {
            if (actionType === 'delete') {
                if (confirm('PIN terverifikasi. Apakah Anda yakin ingin menghapus data ini?')) {
                    window.location.href = targetUrl;
                }
            } else if (actionType === 'view') {
                window.open(targetUrl, '_blank');
            } else { // 'edit' atau lainnya
                window.location.href = targetUrl;
            }
        }, 200);
    }
});
</script>

<?php
// ... existing code ...
// ... (rest of the file)
?>
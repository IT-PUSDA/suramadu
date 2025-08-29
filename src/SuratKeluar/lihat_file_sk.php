<?php
// filepath: c:\laragon\www\ams\src\SuratKeluar\lihat_file_sk.php
// Memulai session untuk memeriksa status login dan menyimpan status verifikasi
session_start();

// Memuat file konfigurasi database
require_once __DIR__ . '/../include/config.php';

// Keamanan: Pastikan pengguna sudah login
if (empty($_SESSION['admin'])) {
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

// Memeriksa apakah parameter id_surat ada
if (!isset($_GET['id_surat']) || empty($_GET['id_surat'])) {
    die('ERROR: ID Surat tidak ditemukan.');
}

$id_surat = mysqli_real_escape_string($config, $_GET['id_surat']);

// Mengambil data file dan pin dari database
$query = mysqli_query($config, "SELECT file, pin, nama_pembuat FROM tbl_surat_keluar WHERE id_surat='$id_surat'");

if (mysqli_num_rows($query) == 0) {
    die('ERROR: Data surat tidak ditemukan.');
}

list($file, $pin_hash, $nama_pembuat) = mysqli_fetch_array($query);

// Jika file tidak ada di database, hentikan proses
if (empty($file)) {
    die('ERROR: File untuk surat ini tidak ditemukan di database.');
}

// Fungsi untuk melayani file, akan dipanggil jika PIN benar atau tidak ada PIN
function serve_file($file)
{
    // Path absolut ke file
    $file_path = realpath(__DIR__ . '/../../upload/surat_keluar') . '/' . $file;

    if (file_exists($file_path)) {
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        // Bersihkan output buffer sebelum mengirim header
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Atur header berdasarkan tipe file
        if ($file_extension == 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        }
        
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . filesize($file_path));
        
        // Baca dan kirim file
        readfile($file_path);
        exit(); // Hentikan eksekusi setelah file dikirim
    } else {
        die('ERROR: File fisik tidak ditemukan di server.');
    }
}


// Jika PIN tidak di-set di database (untuk data lama) atau jika pengguna adalah admin, langsung tampilkan file
if (empty($pin_hash) || (isset($_SESSION['admin']) && $_SESSION['admin'] == 1)) {
    serve_file($file);
    exit();
}

// Logika verifikasi PIN dari form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['pin'])) {
        $submitted_pin = $_POST['pin'];
        if (password_verify($submitted_pin, $pin_hash)) {
            // PIN benar, panggil fungsi untuk melayani file
            serve_file($file);
            exit();
        } else {
            // PIN salah, siapkan pesan error untuk ditampilkan di form
            $error_message = "PIN yang Anda masukkan salah. Silakan coba lagi.";
        }
    } else {
        $error_message = "Kolom PIN tidak boleh kosong.";
    }
}

// Jika kode sampai di sini, berarti PIN diperlukan dan belum diverifikasi dengan benar.
// Tampilkan halaman HTML dengan form untuk memasukkan PIN.
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi PIN Dokumen</title>
    <!-- Impor Materialize CSS dan Icons -->
    <link rel="stylesheet" href="../../asset/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background-color: #e0e0e0; /* Abu-abu muda */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            width: 100%;
            max-width: 400px;
        }
        .card-panel {
            border-radius: 8px;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .btn {
            width: 100%;
            border-radius: 20px;
        }
        .error-message {
            color: #f44336; /* red */
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
        .pembuat-info {
            font-size: 0.9rem;
            color: #757575;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-panel z-depth-2">
            <div class="row">
                <div class="col s12 center-align">
                    <i class="material-icons large blue-grey-text">lock_outline</i>
                    <h5 class="card-title blue-grey-text text-darken-2">Verifikasi PIN</h5>
                    <p class="grey-text">Dokumen ini dilindungi. Silakan masukkan PIN untuk melanjutkan.</p>
                    <p class="pembuat-info">Pembuat Dokumen: <strong><?php echo htmlspecialchars($nama_pembuat); ?></strong></p>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="input-field col s12">
                        <i class="material-icons prefix">vpn_key</i>
                        <input id="pin" type="password" name="pin" class="validate" required autofocus>
                        <label for="pin">Masukkan PIN</label>
                    </div>
                </div>
                <div class="row">
                    <div class="input-field col s12">
                        <button type="submit" class="btn waves-effect waves-light blue darken-1">Buka File</button>
                    </div>
                </div>
            </form>
             <div class="row" style="margin-top: 1rem;">
                <div class="col s12 center-align">
                   <a href="javascript:history.back()" class="btn-flat waves-effect">Kembali</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Impor Materialize JS -->
    <script src="../../asset/js/jquery-2.1.1.min.js"></script>
    <script src="../../asset/js/materialize.min.js"></script>
</body>
</html>
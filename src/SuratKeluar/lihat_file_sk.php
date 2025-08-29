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
// Tampilkan halaman HTML dengan form modal untuk memasukkan PIN.
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi PIN Dokumen</title>
    <link rel="stylesheet" href="../../asset/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background-image: url('../../asset/img/background.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }
        .modal-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 30px;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        .modal-title {
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
        }
        .pin-code-input:focus {
            border-color: #2196F3 !important;
            box-shadow: 0 0 8px 0 rgba(33, 150, 243, 0.5) !important;
        }
        .btn {
            width: 100%;
            border-radius: 25px;
            height: 45px;
            line-height: 45px;
        }
        .error-message {
            color: #f44336;
            margin-top: 15px;
            font-weight: 500;
        }
        .pembuat-info {
            font-size: 0.9rem;
            color: #757575;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="modal-container">
        <i class="material-icons large blue-grey-text text-darken-1">https</i>
        <h5 class="modal-title">Verifikasi PIN</h5>
        <p class="grey-text text-darken-1">Dokumen ini dilindungi. Silakan masukkan 6 digit PIN untuk melanjutkan.</p>
        <p class="pembuat-info">Pembuat Dokumen: <strong><?php echo htmlspecialchars($nama_pembuat); ?></strong></p>

        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form id="pinForm" method="POST" action="">
            <input type="hidden" name="pin" id="fullPin">
            <div class="pin-code-container">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="tel" class="pin-code-input" maxlength="1" pattern="[0-9]" required>
                <?php endfor; ?>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <button type="submit" class="btn waves-effect waves-light blue darken-1">Buka Dokumen</button>
                </div>
            </div>
        </form>
        <div class="row" style="margin-top: 1rem;">
            <div class="col s12 center-align">
               <a href="javascript:window.close();" onclick="if(history.length > 1) {history.back(); return false;} else {window.close();}" class="btn-flat waves-effect">Kembali</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('pinForm');
            const inputs = [...form.querySelectorAll('.pin-code-input')];
            const fullPinInput = document.getElementById('fullPin');

            inputs[0].focus();
            inputs[0].select();

            inputs.forEach((input, index) => {
                input.addEventListener('input', (e) => {
                    if (input.value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                    updateFullPin();
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });

                // Handle paste
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pasteData = e.clipboardData.getData('text').replace(/\s/g, '').slice(0, 6);
                    pasteData.split('').forEach((char, i) => {
                        if (inputs[i]) {
                            inputs[i].value = char;
                        }
                    });
                    const lastInputIndex = Math.min(pasteData.length, 6) -1;
                    if(lastInputIndex >= 0) {
                       inputs[lastInputIndex].focus();
                    }
                    updateFullPin();
                });
            });

            function updateFullPin() {
                fullPinInput.value = inputs.map(i => i.value).join('');
            }
            
            form.addEventListener('submit', function(e) {
                updateFullPin();
                if (fullPinInput.value.length !== 6) {
                    e.preventDefault();
                    // Optionally show an error message
                    if (!document.querySelector('.error-message')) {
                        const errorDiv = document.createElement('p');
                        errorDiv.className = 'error-message';
                        errorDiv.textContent = 'PIN harus terdiri dari 6 digit.';
                        form.insertBefore(errorDiv, form.querySelector('.row'));
                    }
                }
            });
        });
    </script>
</body>
</html>
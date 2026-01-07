<?php
// Halaman Bank Dokumen - Menampilkan card utama (gold) dan 3 kategori (khaki)

if(empty($_SESSION['admin'])){
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: index.php");
    die();
}

// Cek akses: hanya Super Admin (1), User Sekretariat (3), Admin Sekretariat (4) yang bisa akses
$allowed_roles = [1, 3, 4];
if (!in_array((int)$_SESSION['admin'], $allowed_roles)) {
    $_SESSION['err'] = '<center>Anda tidak memiliki akses ke fitur ini!</center>';
    header("Location: index.php");
    die();
}

$uid = (int)$_SESSION['id_user'];

// Ambil data kategori
$query_kat = mysqli_query($config, "SELECT id_kategori, nama_kategori FROM tbl_bank_dokumen_kategori ORDER BY id_kategori");
$kategori = [];
if ($query_kat && mysqli_num_rows($query_kat) > 0) {
    while ($row = mysqli_fetch_assoc($query_kat)) {
        $kategori[] = $row;
    }
} else {
    // Query failed or returned no rows; leave $kategori as empty array
    if ($query_kat === false) {
        error_log('bank_dokumen: failed to fetch categories - ' . mysqli_error($config));
    }
}

// Hitung total jenis per kategori
$stats = [];
foreach ($kategori as $kat) {
    $q = mysqli_query($config, "SELECT COUNT(*) as total FROM tbl_bank_dokumen_jenis WHERE id_kategori=" . (int)$kat['id_kategori']);
    if ($q && ($r = mysqli_fetch_assoc($q))) {
        $stats[$kat['id_kategori']] = $r['total'];
    } else {
        $stats[$kat['id_kategori']] = 0;
        if ($q === false) {
            error_log('bank_dokumen: failed to count jenis for kategori ' . (int)$kat['id_kategori'] . ' - ' . mysqli_error($config));
        }
    }
}
?>

<style>
/* Gold card utama dengan glossy animation */
.card-bank-dokumen-main {
    background: linear-gradient(135deg, #FFD700 0%, #FFC700 50%, #FFD700 100%);
    border: 2px solid #DAA520;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 8px 16px rgba(218, 165, 32, 0.3);
    position: relative;
    overflow: hidden;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

/* Glossy effect overlay */
.card-bank-dokumen-main::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s ease;
}

.card-bank-dokumen-main:hover::before {
    left: 100%;
}

.card-bank-dokumen-main:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 12px 24px rgba(218, 165, 32, 0.5);
}

.card-bank-dokumen-main h2 {
    font-size: 28px;
    color: #333;
    margin: 0;
    text-shadow: 1px 1px 2px rgba(255,255,255,0.5);
    z-index: 1;
}

.card-bank-dokumen-main p {
    font-size: 14px;
    color: #555;
    margin-top: 10px;
    z-index: 1;
}

/* Container untuk kategori cards */
.bank-dokumen-categories {
    display: none;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
    animation: slideIn 0.4s ease forwards;
}

.bank-dokumen-categories.active {
    display: grid;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Khaki category cards */
.card-bank-dokumen-kat {
    background: linear-gradient(135deg, #F0E68C 0%, #FFFACD 100%);
    border: 2px solid #DAA520;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.card-bank-dokumen-kat:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    background: linear-gradient(135deg, #FFFACD 0%, #F0E68C 100%);
}

.card-bank-dokumen-kat h3 {
    font-size: 18px;
    color: #333;
    margin: 0 0 10px 0;
}

.card-bank-dokumen-kat .stat {
    font-size: 12px;
    color: #666;
}

.bank-dokumen-back-btn {
    background: #7f8c8d;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.3s;
}

.bank-dokumen-back-btn:hover {
    background: #2980b9;
}
</style>

<!-- Row Start -->
<div class="row">
    <!-- Secondary Nav START -->
    <div class="col s12">
        <nav class="secondary-nav">
            <div class="nav-wrapper blue-grey darken-1">
                <ul class="left">
                    <li class="waves-effect waves-light">
                        <a href="#" class="judul">
                            <i class="material-icons">folder_special</i> Bank Dokumen
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
    <!-- Secondary Nav END -->
</div>

<div class="row">
    <div class="col s12 m8 offset-m2">
        <!-- Main Card -->
        <div class="card-bank-dokumen-main" id="mainCardBankDokumen">
            <h2>📦 Bank Dokumen</h2>
            <p>Klik untuk membuka kategori dokumen</p>
        </div>

        <!-- Categories Container (hidden by default) -->
        <div class="bank-dokumen-categories" id="kategoriesContainer">
            <?php foreach ($kategori as $kat): ?>
            <div class="card-bank-dokumen-kat" onclick="openKategori(<?php echo (int)$kat['id_kategori']; ?>, '<?php echo htmlspecialchars($kat['nama_kategori'], ENT_QUOTES); ?>')">
                <h3><?php echo htmlspecialchars($kat['nama_kategori']); ?></h3>
                <div class="stat">Jenis Berkas: <?php echo $stats[$kat['id_kategori']] ?? 0; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Back Button (di bawah) -->
        <div style="text-align: center; margin-top: 20px;">
            <button class="bank-dokumen-back-btn" onclick="backToMain()">← Kembali</button>
        </div>
    </div>
</div>

<script>
function openMainCard() {
    const container = document.getElementById('kategoriesContainer');
    const mainCard = document.getElementById('mainCardBankDokumen');
    
    container.classList.add('active');
    mainCard.style.opacity = '0.5';
    mainCard.style.pointerEvents = 'none';
}

function backToMain() {
    // Arahkan ke beranda/halaman utama admin
    window.location.href = 'index.php?page=admin';
}

function openKategori(idKat, namaKat) {
    // Arahkan ke halaman detail kategori
    window.location.href = 'index.php?page=admin&act=bank_dok&sub=list_jenis&id_kat=' + idKat;
}

// Attach click listener to main card
document.getElementById('mainCardBankDokumen').addEventListener('click', openMainCard);
</script>

<?php
?>

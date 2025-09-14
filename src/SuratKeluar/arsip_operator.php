<?php
// Arsip Berkas Operator (level 3)
// Read-Only Proxy untuk Super Admin/Pimpinan: tambahkan query view_as=su&bidang=<key>
if (!isset($_SESSION)) { session_start(); }
require_once(BASE_PATH . '/src/include/config.php');

$readOnly = false;
$proxyBidang = null;
if (in_array((int)$_SESSION['admin'], [1,2], true)
    && isset($_GET['view_as']) && $_GET['view_as'] === 'su'
    && isset($_GET['bidang']) && $_GET['bidang'] !== '') {
    $readOnly = true;
    $proxyBidang = $_GET['bidang'];
}

if ((int)$_SESSION['admin'] !== 3 && !$readOnly) {
    $_SESSION['err'] = '<center>Akses ditolak.</center>';
    header('Location: index.php?page=admin');
    exit;
}

// Pastikan kolom relasi arsip sudah ada agar subquery COUNT tidak error
$__chk_rel = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'id_arsip_berkas'");
if(!$__chk_rel || mysqli_num_rows($__chk_rel)==0){
    // Cek apakah kolom 'status' ada untuk penempatan AFTER; jika tidak ada letakkan di akhir
    $__chk_status = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'status'");
    if($__chk_status && mysqli_num_rows($__chk_status)==1){
        @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL AFTER status, ADD INDEX idx_arsip_rel (id_arsip_berkas)");
    } else {
        @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL, ADD INDEX idx_arsip_rel (id_arsip_berkas)");
    }
}

// Auto create table if not exists (lightweight)
mysqli_query($config, "CREATE TABLE IF NOT EXISTS tbl_arsip_berkas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    kode_klasifikasi VARCHAR(50) NOT NULL,
    nama_berkas VARCHAR(255) NOT NULL,
    uraian TEXT NULL,
    file_path VARCHAR(255) NULL,
    tgl_buat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (id_user),
    INDEX idx_kode (kode_klasifikasi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Tambahkan kolom file_path hanya jika memang belum ada (hindari duplicate column fatal error)
$colCheck = mysqli_query($config, "SHOW COLUMNS FROM tbl_arsip_berkas LIKE 'file_path'");
if($colCheck && mysqli_num_rows($colCheck) === 0){
    mysqli_query($config, "ALTER TABLE tbl_arsip_berkas ADD COLUMN file_path VARCHAR(255) NULL AFTER uraian");
}

// Build operator scope (same logic as earlier mapping)
$mapping = [
    'sekretariat'   => ['SEKRETARIAT','TU'], 'psda'=>['PSDA'],'irigasi'=>['IRIGASI'],'swp'=>['SWP'],'binfat'=>['BINFAT'],
    'upt-kediri'=>['KEDIRI'],'korwil-malang'=>['MALANG'],'korwil-surabaya'=>['SURABAYA'],'upt-bojonegoro'=>['BOJONEGORO'],
    'korwil-madiun'=>['MADIUN'],'upt-bondowoso'=>['BONDOWOSO'],'upt-lumajang'=>['LUMAJANG'],'upt-pasuruan'=>['PASURUAN'],
    'upt-madura'=>['MADURA']
];
$ids=[];
if ($readOnly) {
    // Scope by bidang yang diminta
    $g = $proxyBidang;
    if (isset($mapping[$g])) {
        $tokens = array_map('strtoupper', (array)$mapping[$g]);
        $ors=[];
        foreach($tokens as $t){ $e=mysqli_real_escape_string($config,$t); $ors[]="UPPER(username) LIKE '%$e%'"; $ors[]="UPPER(nama) LIKE '%$e%'"; }
        if(!empty($ors)){
            $ru = mysqli_query($config, 'SELECT id_user FROM tbl_user WHERE ('.implode(' OR ',$ors).')');
            if($ru){ while($r=mysqli_fetch_assoc($ru)){ $ids[]=(int)$r['id_user']; } }
        }
        // Fallback via kode bidang di no_surat
        $bidangCodes = [
            'sekretariat'=>'104.1','psda'=>'104.2','swp'=>'104.3','irigasi'=>'104.4','binfat'=>'104.5',
            'upt-kediri'=>'104.6.02','korwil-malang'=>'104.6.02','korwil-surabaya'=>'104.6.02',
            'upt-bojonegoro'=>'104.6.05','korwil-madiun'=>'104.6.05','upt-bondowoso'=>'104.6.06',
            'upt-lumajang'=>'104.6.07','upt-pasuruan'=>'104.6.08','upt-madura'=>'104.6.09'
        ];
        if(isset($bidangCodes[$g])){
            $code = mysqli_real_escape_string($config,$bidangCodes[$g]);
            $rs = mysqli_query($config, "SELECT DISTINCT id_user FROM tbl_surat_keluar WHERE no_surat LIKE '%$code%'");
            if($rs){ while($r=mysqli_fetch_assoc($rs)){ $ids[]=(int)$r['id_user']; } }
        }
    }
    $ids = array_values(array_unique($ids));
    if (empty($ids)) { $ids = [0]; }
} else {
    $uname = strtoupper($_SESSION['username']);
    $namaUpper = isset($_SESSION['nama']) ? strtoupper($_SESSION['nama']) : '';
    $groupFound = null;
    foreach ($mapping as $g => $list) {
        foreach ($list as $u) { $t=strtoupper($u); if($uname===$t||strpos($uname,$t)!==false||($namaUpper&&strpos($namaUpper,$t)!==false)){ $groupFound=$g; break 2;} }
    }
    if ($groupFound===null){ $flat=str_replace(['_',' '],'',$uname); foreach($mapping as $g=>$list){ foreach($list as $u){ $tok=str_replace(['_',' '],'',strtoupper($u)); if(strpos($flat,$tok)!==false){$groupFound=$g; break 2;} } } }
    if($groupFound!==null){ $names=array_map('strtoupper',$mapping[$groupFound]); $esc=[]; foreach($names as $n){$esc[]="'".mysqli_real_escape_string($config,$n)."'";} $sqlUsers="SELECT id_user FROM tbl_user WHERE UPPER(username) IN (".implode(',',$esc).")"; $ru=mysqli_query($config,$sqlUsers); if($ru){ while($r=mysqli_fetch_assoc($ru)){ $ids[]=(int)$r['id_user']; } } }
    if(empty($ids)){$ids[]=(int)$_SESSION['id_user'];}
    if(!in_array((int)$_SESSION['id_user'],$ids,true)) { $ids[] = (int)$_SESSION['id_user']; }
}
$idList=implode(',',array_map('intval',$ids));

// Handle add form
$isAdd  = (isset($_GET['sub']) && $_GET['sub']==='add');
$isEdit = (isset($_GET['sub']) && $_GET['sub']==='edit' && isset($_GET['id']) && ctype_digit($_GET['id']));
$isView = (isset($_GET['sub']) && $_GET['sub']==='view' && isset($_GET['id']) && ctype_digit($_GET['id']));
$isPrint = (isset($_GET['sub']) && $_GET['sub']==='print' && isset($_GET['id']) && ctype_digit($_GET['id']));
$errors=[]; $successMsg='';
if (!$readOnly && $isAdd && isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_berkas']??'');
    $kode = trim($_POST['kode_klasifikasi']??'');
    $uraian = trim($_POST['uraian']??'');
    if($nama==='') $errors[]='Nama Berkas wajib diisi';
    if($kode==='') $errors[]='Kode Klasifikasi wajib dipilih';
    $storedPath = '';
    // File (optional) handling
    if(isset($_FILES['file']) && $_FILES['file']['name']!==''){
        $f = $_FILES['file'];
        if($f['error']===0){
            $ext = strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
            if($ext!=='pdf'){ $errors[]='File harus PDF.'; }
            if($f['size']>2*1024*1024){ $errors[]='Ukuran file maksimal 2MB.'; }
            if(empty($errors)){
                $dir = BASE_PATH.'/upload/arsip_berkas';
                if(!is_dir($dir)) @mkdir($dir,0775,true);
                $slug = preg_replace('/[^A-Za-z0-9_-]+/','_',substr($nama,0,40));
                $fname = date('Ymd_His').'_'.$_SESSION['id_user'].'_'.$slug.'.pdf';
                $dest = $dir.'/'.$fname;
                if(move_uploaded_file($f['tmp_name'],$dest)){
                    $storedPath = 'upload/arsip_berkas/'.$fname;
                } else { $errors[]='Gagal menyimpan file.'; }
            }
        } else {
            $errors[]='Upload file gagal.';
        }
    }
    if(empty($errors)){
        $namaEsc = mysqli_real_escape_string($config,$nama);
        $kodeEsc = mysqli_real_escape_string($config,$kode);
        $uraianEsc = mysqli_real_escape_string($config,$uraian);
        $fileEsc = $storedPath? "'".mysqli_real_escape_string($config,$storedPath)."'" : 'NULL';
        $userId = (int)$_SESSION['id_user'];
        $ins = mysqli_query($config,"INSERT INTO tbl_arsip_berkas (id_user,kode_klasifikasi,nama_berkas,uraian,file_path) VALUES ($userId,'$kodeEsc','$namaEsc','$uraianEsc',$fileEsc)");
        if($ins){ $_SESSION['succAdd']='Berkas arsip berhasil disimpan.'; header('Location: index.php?page=admin&act=arsip_op'); exit; } else { $errors[]='Gagal menyimpan data.'; }
    }
}

// Edit handling
$editRow = null;
if($isEdit){
    if ($readOnly) { header('Location: index.php?page=admin&act=arsip_op'.($readOnly?'&view_as=su&bidang='.urlencode($proxyBidang):'')); exit; }
    $editId = (int)$_GET['id'];
    $q = mysqli_query($config,"SELECT * FROM tbl_arsip_berkas WHERE id=$editId LIMIT 1");
    if($q && mysqli_num_rows($q)==1){
        $tmp = mysqli_fetch_assoc($q);
        if(!in_array((int)$tmp['id_user'],$ids,true)){
            $_SESSION['err']='<center>Akses ditolak.</center>';
            header('Location: index.php?page=admin&act=arsip_op'); exit;
        }
        $editRow = $tmp;
    } else {
        $_SESSION['err']='<center>Data tidak ditemukan.</center>';
        header('Location: index.php?page=admin&act=arsip_op'); exit;
    }
    if(isset($_POST['update'])){
        $nama = trim($_POST['nama_berkas']??'');
        $kode = trim($_POST['kode_klasifikasi']??'');
        $uraian = trim($_POST['uraian']??'');
        if($nama==='') $errors[]='Nama Berkas wajib diisi';
        if($kode==='') $errors[]='Kode Klasifikasi wajib dipilih';
        $storedPath = $editRow['file_path'];
        if(isset($_FILES['file']) && $_FILES['file']['name']!==''){
            $f = $_FILES['file'];
            if($f['error']===0){
                $ext = strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
                if($ext!=='pdf'){ $errors[]='File harus PDF.'; }
                if($f['size']>2*1024*1024){ $errors[]='Ukuran file maksimal 2MB.'; }
                if(empty($errors)){
                    $dir = BASE_PATH.'/upload/arsip_berkas';
                    if(!is_dir($dir)) @mkdir($dir,0775,true);
                    $slug = preg_replace('/[^A-Za-z0-9_-]+/','_',substr($nama,0,40));
                    $fname = 'U'.$_SESSION['id_user'].'_'.$editId.'_'.date('Ymd_His').'.pdf';
                    $dest = $dir.'/'.$fname;
                    if(move_uploaded_file($f['tmp_name'],$dest)){
                        if($storedPath && file_exists(BASE_PATH.'/'.$storedPath)) @unlink(BASE_PATH.'/'.$storedPath);
                        $storedPath = 'upload/arsip_berkas/'.$fname;
                    } else { $errors[]='Gagal menyimpan file.'; }
                }
            } else {
                $errors[]='Upload file gagal.';
            }
        }
        if(empty($errors)){
            $namaEsc = mysqli_real_escape_string($config,$nama);
            $kodeEsc = mysqli_real_escape_string($config,$kode);
            $uraianEsc = mysqli_real_escape_string($config,$uraian);
            $fileSql = $storedPath? "file_path='".mysqli_real_escape_string($config,$storedPath)."'," : '';
            $up = mysqli_query($config,"UPDATE tbl_arsip_berkas SET $fileSql kode_klasifikasi='$kodeEsc', nama_berkas='$namaEsc', uraian='$uraianEsc' WHERE id=$editId");
            if($up){ $_SESSION['succAdd']='Perubahan tersimpan.'; header('Location: index.php?page=admin&act=arsip_op'); exit; } else { $errors[]='Gagal menyimpan perubahan.'; }
        }
    }
}

// Load data klasifikasi (for select)
$klas = []; $rk = mysqli_query($config,"SELECT kode, nama FROM tbl_klasifikasi ORDER BY kode ASC"); if($rk){ while($r=mysqli_fetch_assoc($rk)){ $klas[]=$r; } }

// List (if not add)
$filterKode = isset($_GET['kode'])?trim($_GET['kode']):'';
$filterNama = isset($_GET['nama'])?trim($_GET['nama']):'';
$where = "WHERE id_user IN ($idList)";
if($filterKode!==''){ $e=mysqli_real_escape_string($config,$filterKode); $where.=" AND kode_klasifikasi LIKE '%$e%'"; }
if($filterNama!==''){ $e=mysqli_real_escape_string($config,$filterNama); $where.=" AND nama_berkas LIKE '%$e%'"; }
$sqlList = "SELECT a.id,a.kode_klasifikasi,a.nama_berkas,a.uraian,a.tgl_buat,a.file_path,(SELECT COUNT(1) FROM tbl_surat_keluar s WHERE s.id_arsip_berkas=a.id AND s.status='finished') AS jml_surat FROM tbl_arsip_berkas a $where ORDER BY a.tgl_buat DESC, a.id DESC";
$resList = mysqli_query($config,$sqlList);
$rows=[]; if($resList){ while($r=mysqli_fetch_assoc($resList)){ $rows[]=$r; } }

function indoDateShort($date){ if(!$date||$date==='0000-00-00') return '-'; $bln=[1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; $e=explode('-',$date); if(count($e)>=3) return (int)substr($e[2],0,2).' '.$bln[(int)$e[1]].' '.$e[0]; return $date; }
?>
<style>
body .container { max-width:100% !important; width:100% !important; }
body .container .table-arsip-op-wrapper{margin-left:0;margin-right:0;}
.table-arsip-op-wrapper{background:#fff;border-radius:14px;padding:24px;margin-top:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);} 
.table-arsip-op{width:100%;border-collapse:collapse;}
.table-arsip-op thead th{background:#263238;color:#fff;padding:11px 10px;font-size:13px;letter-spacing:.5px;}
.table-arsip-op tbody td{padding:9px 10px;font-size:13px;border-bottom:1px solid #eee;vertical-align:top;}
.table-arsip-op tbody tr:hover{background:#f7f9fa;}
#kode-arsip-suggest{border:1px solid #cfd8dc;border-top:none;border-radius:0 0 4px 4px; margin-top:-1px;}
#kode-arsip-suggest .collection-item{background:#fff !important; color:#1e3a52 !important; line-height:1.25rem;}
#kode-arsip-suggest .collection-item small{display:block; margin-top:2px;}
#kode-arsip-suggest .collection-item.active,#kode-arsip-suggest .collection-item:hover{background:#f5f5f5 !important; color:#0d47a1 !important;}
.badge-round-btn{background:#1976d2;color:#fff;display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:50%;box-shadow:0 3px 6px rgba(0,0,0,.25);text-decoration:none;}
.badge-round-btn:hover{background:#1e88e5;}
.filters-inline{display:flex;flex-wrap:wrap;gap:18px 28px;margin:4px 0 18px;align-items:flex-end;}
.filters-inline .field{display:flex;flex-direction:column;}
.filters-inline label{font-size:11px;font-weight:600;color:#455a64;margin-bottom:5px;letter-spacing:.5px;}
.filters-inline input{background:#fafafa;border:1px solid #d0d7de;border-radius:8px;height:40px;padding:0 12px;min-width:230px;}
@media(max-width:600px){.filters-inline input{min-width:150px;} .badge-round-btn{width:54px;height:54px;margin-top:6px;} }
.form-arsip label{font-size:12px;font-weight:600;color:#37474f;margin-bottom:6px;display:block;}
.form-arsip input[type=text], .form-arsip select, .form-arsip textarea{width:100%;background:#fafafa;border:1px solid #d0d7de;border-radius:8px;padding:10px 12px;font-size:13px;}
.form-arsip textarea{min-height:120px;resize:vertical;}
.actions-form{display:flex;gap:14px;margin-top:8px;}
.btn-pill{border-radius:28px !important;padding:0 26px;height:44px;line-height:44px;font-weight:600;letter-spacing:.4px;}
.note-req{color:#e53935;font-weight:600;}
</style>

<div class="table-arsip-op-wrapper">
    <?php if($isAdd || $isEdit): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:12px;">
            <h5 style="margin:0;display:flex;align-items:center;gap:8px;">
                <i class="material-icons"><?php echo $isEdit? 'edit' : 'add_circle'; ?></i> <?php echo $isEdit? 'Edit' : 'Tambah'; ?> Berkas Arsip
            </h5>
            <a href="index.php?page=admin&act=arsip_op<?php echo $readOnly? '&view_as=su&bidang='.urlencode($proxyBidang) : ''; ?>" class="btn grey lighten-1 btn-pill" style="color:#263238;">Kembali</a>
        </div>
        <?php if(!empty($errors)): ?>
            <div class="card-panel red lighten-5" style="border-radius:10px;padding:14px 18px;">
                <ul style="margin:0 0 0 16px;">
                    <?php foreach($errors as $er): ?><li style="color:#c62828; font-size:13px;"><?php echo htmlspecialchars($er); ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" autocomplete="off" enctype="multipart/form-data">
            <div class="row" style="margin-bottom:4px;">
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix md-prefix">description</i>
                    <input id="nama_berkas" type="text" name="nama_berkas" value="<?php echo htmlspecialchars($isEdit && !isset($_POST['nama_berkas'])?$editRow['nama_berkas']:($_POST['nama_berkas']??'')); ?>" required>
                    <label for="nama_berkas">Nama Berkas</label>
                </div>
                <div class="input-field col s12 m6 tooltipped" data-position="top" data-tooltip="Diambil dari data referensi kode klasifikasi" style="position:relative;">
                    <i class="material-icons prefix md-prefix">bookmark</i>
                    <input id="kode_klasifikasi" type="text" name="kode_klasifikasi" autocomplete="off" value="<?php echo htmlspecialchars($isEdit && !isset($_POST['kode_klasifikasi'])?$editRow['kode_klasifikasi']:($_POST['kode_klasifikasi']??'')); ?>" required>
                    <label for="kode_klasifikasi">Kode Klasifikasi</label>
                    <div id="kode-arsip-suggest" class="collection" style="position:absolute; z-index: 1000; display:none; max-height:260px; overflow:auto; left:44px; right:0; background:#fff;"></div>
                </div>
                <div class="input-field col s12">
                    <i class="material-icons prefix md-prefix">subject</i>
                    <textarea id="uraian" class="materialize-textarea" name="uraian"><?php echo htmlspecialchars($isEdit && !isset($_POST['uraian'])?$editRow['uraian']:($_POST['uraian']??'')); ?></textarea>
                    <label for="uraian">Uraian</label>
                </div>
                <?php /* Upload dihilangkan sesuai permintaan */ ?>
            </div>
            <div class="row" style="margin-top:4px;">
                <div class="col s12">
                    <?php if($isEdit): ?>
                        <button type="submit" name="update" class="btn-large blue waves-effect waves-light" style="margin-right: 1rem;">UPDATE <i class="material-icons">save</i></button>
                    <?php else: ?>
                        <button type="submit" name="simpan" class="btn-large blue waves-effect waves-light" style="margin-right: 1rem;">SIMPAN <i class="material-icons">done</i></button>
                    <?php endif; ?>
                    <a href="index.php?page=admin&act=arsip_op" class="btn-large deep-orange waves-effect waves-light">BATAL <i class="material-icons">clear</i></a>
                </div>
            </div>
        </form>
        <script>
        (function(){
            const input = document.getElementById('kode_klasifikasi');
            if(!input) return;
            const box = document.getElementById('kode-arsip-suggest');
            let idx = -1, items = [];
            function hide(){ box.style.display='none'; box.innerHTML=''; idx=-1; items=[]; }
            function render(list){
                if(!list.length){
                    box.innerHTML = '<a class="collection-item grey-text" href="#" onclick="return false;">Tidak ada hasil. Pastikan data klasifikasi sudah diimport.</a>';
                    box.style.display='block';
                    items = [];
                    return;
                }
                box.innerHTML = list.map((r,i)=>`
                    <a href="#" class="collection-item" data-kode="${r.kode.replace(/"/g,'&quot;')}">
                        <span class="blue-text" style="font-weight:600">${r.kode}</span> - ${r.nama || ''}
                        <br><small class="grey-text">${r.uraian || ''}</small>
                    </a>`).join('');
                box.style.display='block';
                items = Array.from(box.querySelectorAll('.collection-item'));
                items.forEach((el,i)=>{
                    el.addEventListener('mouseover',()=>{ setActive(i); });
                    el.addEventListener('click',(e)=>{ e.preventDefault(); pick(i); });
                });
            }
            function setActive(i){ if(items[idx]) items[idx].classList.remove('active'); idx=i; if(items[idx]) items[idx].classList.add('active'); }
            function pick(i){ if(!items[i]) return; input.value = items[i].getAttribute('data-kode'); hide(); input.focus(); M.updateTextFields(); }
            let t;
            function query(q){
                fetch('/src/Utils/klasifikasi_search.php?term='+encodeURIComponent(q||''), {credentials:'same-origin'})
                    .then(r=>r.ok?r.json():[])
                    .then(render)
                    .catch(()=>hide());
            }
            input.addEventListener('focus',()=>{ if(input.value.trim()===''){ query(''); }});
            input.addEventListener('input',()=>{
                const q = input.value.trim();
                if(q.length < 1){ query(''); return; }
                clearTimeout(t);
                t = setTimeout(()=>{ query(q); }, 180);
            });
            input.addEventListener('keydown',(e)=>{
                if(box.style.display==='none') return;
                if(e.key==='ArrowDown'){ e.preventDefault(); setActive(Math.min(idx+1, items.length-1)); }
                else if(e.key==='ArrowUp'){ e.preventDefault(); setActive(Math.max(idx-1, 0)); }
                else if(e.key==='Enter'){ if(idx>-1){ e.preventDefault(); pick(idx); } }
                else if(e.key==='Escape'){ hide(); }
            });
            document.addEventListener('click',(e)=>{ if(!box.contains(e.target) && e.target!==input){ hide(); } });
            // Activate labels if already filled
            if(input.value.trim()!==''){ M.updateTextFields(); }
        })();
        </script>
    <?php elseif($isView): ?>
        <?php
        $viewId = (int)$_GET['id'];
        // Validasi kepemilikan berkas
        $cekB = mysqli_query($config, "SELECT id, kode_klasifikasi, nama_berkas FROM tbl_arsip_berkas WHERE id=$viewId AND id_user IN ($idList) LIMIT 1");
        if(!$cekB || mysqli_num_rows($cekB)!==1){
            echo '<div class="card-panel red lighten-5" style="border-radius:10px;margin-top:14px;padding:10px 16px;color:#c62828;font-weight:600;">Berkas tidak ditemukan atau bukan milik Anda.</div>';
        } else {
            $berkas = mysqli_fetch_assoc($cekB);
            // Handle unlink (keluarkan dari berkas), bukan hapus surat
            if(!$readOnly && isset($_GET['rem']) && ctype_digit($_GET['rem'])){
                $rid = (int)$_GET['rem'];
                $username = isset($_SESSION['username']) ? mysqli_real_escape_string($config, $_SESSION['username']) : 'system';
                $now = date('Y-m-d H:i:s');
                mysqli_query($config, "UPDATE tbl_surat_keluar SET id_arsip_berkas=NULL, updated_by='$username', updated_at='$now' WHERE id_surat=$rid AND id_arsip_berkas=$viewId");
                echo '<script>location.href="index.php?page=admin&act=arsip_op&sub=view&id='.$viewId.($readOnly?'&view_as=su&bidang='.urlencode($proxyBidang):'').'";</script>';
                exit;
            }
            // Ensure needed columns exist
            $cols=[]; $rc=mysqli_query($config,"SHOW COLUMNS FROM tbl_surat_keluar"); if($rc){ while($c=mysqli_fetch_assoc($rc)){ $cols[]=$c['Field']; } }
            if(!in_array('jenis',$cols)){ @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN jenis VARCHAR(20) NOT NULL DEFAULT 'umum'"); }
            if(!in_array('updated_at',$cols)){ @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN updated_at DATETIME NULL"); }

            // Query daftar isi berkas (hanya finished)
            $qList = mysqli_query($config, "SELECT id_surat, jenis, no_surat, tgl_surat, isi, file, updated_at FROM tbl_surat_keluar WHERE id_arsip_berkas=$viewId AND status='finished' ORDER BY id_surat DESC");
            $items=[]; if($qList){ while($r=mysqli_fetch_assoc($qList)){ $items[]=$r; } }

            function mapJenis($j){
                switch($j){
                    case 'nota_dinas': return 'Nota Dinas';
                    case 'produk_hukum': return 'Produk Hukum';
                    case 'keuangan': return 'Keuangan';
                    case 'umum': default: return 'Surat Keluar';
                }
            }
            function fileIcon($fname){
                if(!$fname) return '';
                $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                if($ext==='pdf') return 'asset/img/pdf.png';
                if(in_array($ext,['doc','docx'])) return 'asset/img/word.png';
                return 'asset/img/pdf.png';
            }
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:18px;">
            <h5 style="margin:0;display:flex;align-items:center;gap:8px;">
                <i class="material-icons">visibility</i> Daftar Isi Berkas Arsip Aktif
            </h5>
            <a href="index.php?page=admin&act=arsip_op<?php echo $readOnly? '&view_as=su&bidang='.urlencode($proxyBidang) : ''; ?>" class="btn grey lighten-1 btn-pill" style="color:#263238;">Kembali</a>
        </div>
        <div class="grey-text" style="margin-top:4px;">Berkas: <strong><?php echo htmlspecialchars($berkas['kode_klasifikasi']); ?></strong> - <?php echo htmlspecialchars($berkas['nama_berkas']); ?></div>

        <div class="table-responsive" style="margin-top:14px;">
            <table class="table-arsip-op" id="tbl-isi-berkas">
                <thead>
                    <tr>
                        <th style="width:50px;" class="center-align">No</th>
                        <th>Jenis Surat</th>
                        <th>Nomor Surat</th>
                        <th>Tanggal Diarsipkan</th>
                        <th>Isi Ringkas</th>
                        <th style="width:80px;" class="center-align">File</th>
                        <th style="width:80px;" class="center-align">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($items)): ?>
                        <tr><td colspan="7" class="center-align" style="padding:18px;color:#777;">Belum ada surat pada berkas ini.</td></tr>
                    <?php else: $no=1; foreach($items as $it): ?>
                        <tr>
                            <td class="center-align"><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars(mapJenis($it['jenis'])); ?></td>
                            <td><?php echo htmlspecialchars($it['no_surat']); ?></td>
                            <td><?php echo htmlspecialchars(!empty($it['updated_at']) ? date('d M Y', strtotime($it['updated_at'])) : (!empty($it['tgl_surat']) ? date('d M Y', strtotime($it['tgl_surat'])) : '-')); ?></td>
                            <td><?php echo htmlspecialchars($it['isi']); ?></td>
                            <td class="center-align">
                                <?php if(!empty($it['file'])): $icon=fileIcon($it['file']); ?>
                                    <a href="src/SuratKeluar/lihat_file_sk.php?id_surat=<?php echo (int)$it['id_surat']; ?>" target="_blank" title="Buka File"><img src="<?php echo $icon; ?>" alt="file" style="height:28px;width:auto;"/></a>
                                <?php else: ?>
                                    <span class="btn grey lighten-3" style="padding:2px 8px;border-radius:14px;color:#455a64;font-weight:600;">TIDAK ADA</span>
                                <?php endif; ?>
                            </td>
                            <td class="center-align">
                                <a href="index.php?page=admin&act=arsip_op&sub=view&id=<?php echo $viewId; ?>&rem=<?php echo (int)$it['id_surat']; ?>" onclick="return confirm('Keluarkan surat ini dari berkas?');" class="btn-flat tooltipped" data-position="top" data-tooltip="Keluarkan" style="color:#e53935;"><i class="material-icons">delete</i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <script>document.addEventListener('DOMContentLoaded',function(){ try{ if(window.jQuery && jQuery.fn.tooltip){ jQuery('.tooltipped').tooltip({delay:10}); } else if(window.M && M.Tooltip){ M.Tooltip.init(document.querySelectorAll('.tooltipped')); } }catch(e){} });</script>
        <?php } ?>
    <?php elseif($isPrint): ?>
        <?php
    // Cetak agenda arsip untuk satu berkas, dengan filter tanggal diarsipkan
        $printId = (int)$_GET['id'];
        // Validasi kepemilikan berkas
        $cekB = mysqli_query($config, "SELECT id, kode_klasifikasi, nama_berkas FROM tbl_arsip_berkas WHERE id=$printId AND id_user IN ($idList) LIMIT 1");
        if(!$cekB || mysqli_num_rows($cekB)!==1){
            echo '<div class="card-panel red lighten-5" style="border-radius:10px;margin-top:14px;padding:10px 16px;color:#c62828;font-weight:600;">Berkas tidak ditemukan atau bukan milik Anda.</div>';
        } else {
            $berkas = mysqli_fetch_assoc($cekB);
            $from = isset($_POST['dari_tanggal']) ? trim($_POST['dari_tanggal']) : '';
            $to   = isset($_POST['sampai_tanggal']) ? trim($_POST['sampai_tanggal']) : '';
            $hasSubmit = isset($_POST['submit']);
            // Styling cetak + kop
            echo '<style>
                #kop-cetak{display:flex;align-items:center;gap:16px;margin:0 0 8px;}
                #kop-cetak .kop-logo img{width:90px;height:auto;}
                #kop-cetak .kop-text{flex:1;text-align:center;}
                #kop-cetak .ln1{font-size:20px;font-weight:400;letter-spacing:.5px;text-transform:uppercase;}
                #kop-cetak .ln2{font-size:20px;font-weight:700;text-transform:uppercase;white-space:nowrap;line-height:1.2;}
                #kop-cetak .ln3,#kop-cetak .ln4{font-size:13px;}
                .kop-sep{border-bottom:2px solid #263238;margin:6px 0 12px;}
                .agenda-title{font-weight:700;text-transform:uppercase;margin:0 0 10px}
                .agenda-sub{margin:0 0 12px}
                table.agenda-print{width:100%;border-collapse:collapse}
                .agenda-print th,.agenda-print td{border:1px solid #000;padding:6px 8px}
                .agenda-print th{background:#f4f6f8}
                @media print{
                    @page{ margin: 0.8cm 1.5cm 1.5cm 1.5cm; }
                    .no-print{display:none!important}
                    html,body{color:#000;font-size:12px;margin:0;padding:0}
                }
            </style>';
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:18px;" class="no-print">
            <h5 style="margin:0;display:flex;align-items:center;gap:8px;">
                <i class="material-icons">print</i> Cetak Agenda Arsip
            </h5>
            <a href="index.php?page=admin&act=arsip_op<?php echo $readOnly? '&view_as=su&bidang='.urlencode($proxyBidang) : ''; ?>" class="btn grey lighten-1 btn-pill" style="color:#263238;">Kembali</a>
        </div>
        <div class="grey-text no-print" style="margin-top:4px;">Berkas: <strong><?php echo htmlspecialchars($berkas['kode_klasifikasi']); ?></strong> - <?php echo htmlspecialchars($berkas['nama_berkas']); ?></div>

        <div class="row jarak-form black-text no-print">
            <form class="col s12" method="post" action="">
                <div class="input-field col s12 m4">
                    <i class="material-icons prefix md-prefix">date_range</i>
                    <input id="dari_tanggal" type="text" name="dari_tanggal" value="<?php echo htmlspecialchars($from); ?>" required>
                    <label for="dari_tanggal">Dari Tanggal</label>
                </div>
                <div class="input-field col s12 m4">
                    <i class="material-icons prefix md-prefix">date_range</i>
                    <input id="sampai_tanggal" type="text" name="sampai_tanggal" value="<?php echo htmlspecialchars($to); ?>" required>
                    <label for="sampai_tanggal">Sampai Tanggal</label>
                </div>
                <div class="col s12 m4" style="display:flex;gap:10px;align-items:center;margin-top:22px;">
                    <button type="submit" name="submit" class="btn blue btn-pill">TAMPILKAN <i class="material-icons" style="vertical-align:middle">visibility</i></button>
                    <?php if($hasSubmit): ?><button type="button" onclick="window.print()" class="btn deep-orange btn-pill"><i class="material-icons" style="vertical-align:middle">print</i> CETAK</button><?php endif; ?>
                </div>
            </form>
        </div>

        <?php
        if ($hasSubmit && $from !== '' && $to !== '') {
            // Ambil data kop instansi (opsional)
            $kop = [ 'institusi'=>'', 'nama'=>'', 'alamat'=>'', 'kota'=>'', 'website'=>'', 'email'=>'', 'logo'=>'' ];
            $qi = mysqli_query($config, "SELECT institusi,nama,alamat,kota,website,email,logo FROM tbl_instansi LIMIT 1");
            if ($qi && mysqli_num_rows($qi) === 1) { $kop = mysqli_fetch_assoc($qi); }
            $logoUrl = !empty($kop['logo']) ? ('upload/'.ltrim($kop['logo'],'/')) : 'asset/img/logo.png';
            $institusi = strtoupper(trim($kop['institusi'] ?? ''));
            $namaInst  = strtoupper(trim($kop['nama'] ?? ''));
            // Gunakan teks kop sesuai permintaan user (tiga baris berikut tetap)
            $addrLine = 'Jalan Gayung Kebonsari No.169, Ketintang, Gayungan, Surabaya, Jawa Timur 60235';
            $telLine  = 'Telepon (031) 8288179,8292419,8291711,8292234-8292047';
            $webLine  = 'Laman www.dpuair.jatimprov.go.id, Pos-el pusda@jatimprov.go.id';
            echo '<div id="kop-cetak">'
                .'<div class="kop-logo"><img src="'.htmlspecialchars($logoUrl).'" alt="logo" /></div>'
                .'<div class="kop-text">'
                    .($institusi!==''? '<div class="ln1">'.htmlspecialchars($institusi).'</div>' : '')
                    .($namaInst!==''? '<div class="ln2">'.htmlspecialchars($namaInst).'</div>' : '')
                    .'<div class="ln3">'.htmlspecialchars($addrLine).'</div>'
                    .'<div class="ln4">'.htmlspecialchars($telLine).'</div>'
                    .'<div class="ln4">'.htmlspecialchars($webLine).'</div>'
                .'</div>'
            .'</div>';
            echo '<div class="kop-sep"></div>';
            $fromEsc = mysqli_real_escape_string($config, $from);
            $toEsc   = mysqli_real_escape_string($config, $to);
            $sql = "SELECT id_surat, jenis, no_surat, tgl_surat, isi, file, updated_at
                    FROM tbl_surat_keluar
                    WHERE id_arsip_berkas=$printId AND status='finished'
                      AND DATE(COALESCE(updated_at, tgl_surat)) BETWEEN '$fromEsc' AND '$toEsc'
                    ORDER BY COALESCE(updated_at, tgl_surat) ASC, id_surat ASC";
            $q = mysqli_query($config, $sql);
            $items = [];
            if ($q) { while($r=mysqli_fetch_assoc($q)){ $items[] = $r; } }
            $dateRangeText = function($d){ if(!$d||$d==='0000-00-00') return '-'; $bln=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; $y=substr($d,0,4); $m=(int)substr($d,5,2); $day=substr($d,8,2); return $day.' '.$bln[$m].' '.$y; };
            echo '<div class="agenda">';
            // Judul menggunakan nama berkas dengan prefix "Arsip"
            echo '<h5 class="agenda-title">Arsip '.htmlspecialchars($berkas['nama_berkas']).'</h5>';
            echo '<p class="agenda-sub" style="margin-top:-6px;">Kode Klasifikasi: <strong>'.htmlspecialchars($berkas['kode_klasifikasi']).'</strong></p>';
            echo '<p class="agenda-sub">Periode: <strong>'.$dateRangeText($from).'</strong> s.d. <strong>'.$dateRangeText($to).'</strong></p>';
            echo '<table class="agenda-print"><thead><tr>
                    <th style="width:40px;">No</th>
                    <th>Jenis</th>
                    <th>No. Surat</th>
                    <th>Tanggal Diarsipkan</th>
                    <th>Isi Ringkas</th>
                </tr></thead><tbody>';
            if (empty($items)) {
                echo '<tr><td colspan="5" style="text-align:center;color:#777;">Tidak ada data pada rentang tanggal.</td></tr>';
            } else {
                $no = 1;
                foreach ($items as $it) {
                    $jenis = ($it['jenis']==='nota_dinas'?'Nota Dinas':($it['jenis']==='produk_hukum'?'Produk Hukum':($it['jenis']==='keuangan'?'Keuangan':'Surat Keluar')));
                    $tgl = !empty($it['updated_at']) ? date('d M Y', strtotime($it['updated_at'])) : (!empty($it['tgl_surat']) ? date('d M Y', strtotime($it['tgl_surat'])) : '-');
                    echo '<tr>'
                       .'<td style="text-align:center;">'.$no++.'</td>'
                       .'<td>'.htmlspecialchars($jenis).'</td>'
                       .'<td>'.htmlspecialchars($it['no_surat']).'</td>'
                       .'<td>'.htmlspecialchars($tgl).'</td>'
                       .'<td>'.htmlspecialchars($it['isi']).'</td>'
                       .'</tr>';
                }
            }
            echo '</tbody></table></div>';
        }
        ?>
        <?php }
        ?>
    <?php else: ?>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:18px;">
            <h5 style="margin:0;display:flex;align-items:center;gap:8px;">
                <i class="material-icons">archive</i> Arsip Berkas Bidang
            </h5>
            <?php if(!$readOnly): ?>
            <a href="index.php?page=admin&act=arsip_op&sub=add" class="badge-round-btn" title="Tambah Berkas"><i class="material-icons">add</i></a>
            <?php endif; ?>
        </div>
        <?php if(isset($_SESSION['succAdd'])): ?><div class="card-panel green lighten-5" style="border-radius:10px;margin-top:14px;padding:10px 16px;color:#2e7d32;font-weight:600;"><?php echo $_SESSION['succAdd']; unset($_SESSION['succAdd']); ?></div><?php endif; ?>
        <form method="get" style="margin-top:16px;">
            <input type="hidden" name="page" value="admin" />
            <input type="hidden" name="act" value="arsip_op" />
            <div class="filters-inline">
                <div class="field">
                    <label>Cari Kode Klasifikasi</label>
                    <input type="text" name="kode" value="<?php echo htmlspecialchars($filterKode); ?>" placeholder="Ketik kode..." />
                </div>
                <div class="field">
                    <label>Cari Nama Berkas</label>
                    <input type="text" name="nama" value="<?php echo htmlspecialchars($filterNama); ?>" placeholder="Ketik nama..." />
                </div>
                <div class="field" style="align-self:flex-end;">
                    <button class="btn blue btn-pill" style="height:40px;line-height:40px;">Filter</button>
                </div>
            </div>
        </form>
        <div class="table-responsive" style="margin-top:2px;">
            <table class="table-arsip-op">
                <thead>
                    <tr>
                        <th style="width:50px;" class="center-align">No</th>
                        <th style="min-width:140px;">Kode Klasifikasi</th>
                        <th style="min-width:230px;">Nama Berkas</th>
                        <th style="min-width:160px;">Tanggal Buat Berkas</th>
                        <th style="min-width:80px;">Total</th>
                        <th style="min-width:140px;" class="center-align">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($rows)): ?>
                        <tr><td colspan="6" class="center-align" style="padding:18px;color:#777;">Belum ada berkas arsip.</td></tr>
                    <?php else: $no=1; foreach($rows as $r): ?>
                        <tr>
                            <td class="center-align"><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($r['kode_klasifikasi']); ?></td>
                            <td><?php echo htmlspecialchars($r['nama_berkas']); ?></td>
                            <td><?php echo indoDateShort(substr($r['tgl_buat'],0,10)); ?></td>
                            <td class="center-align"><?php echo (int)$r['jml_surat']; ?></td>
                            <td class="center-align" style="display:flex;gap:4px;justify-content:center;flex-wrap:wrap;">
                                <a href="index.php?page=admin&act=arsip_op&sub=view&id=<?php echo (int)$r['id']; ?><?php echo $readOnly? '&view_as=su&bidang='.urlencode($proxyBidang) : ''; ?>" class="btn-flat tooltipped" data-position="top" data-tooltip="Lihat Surat Arsip" style="color:#0277bd;"><i class="material-icons">visibility</i></a>
                                <?php if(!$readOnly): ?>
                                <a href="index.php?page=admin&act=arsip_op&sub=edit&id=<?php echo (int)$r['id']; ?>" class="btn-flat tooltipped" data-position="top" data-tooltip="Edit" style="color:#ef6c00;"><i class="material-icons">edit</i></a>
                                <a href="index.php?page=admin&act=arsip_op&sub=print&id=<?php echo (int)$r['id']; ?>" class="btn-flat tooltipped" data-position="top" data-tooltip="Cetak Agenda" style="color:#1565c0;"><i class="material-icons">print</i></a>
                                <a href="index.php?page=admin&act=arsip_op&del=<?php echo (int)$r['id']; ?>" onclick="return confirm('Hapus berkas arsip ini?');" class="btn-flat tooltipped" data-position="top" data-tooltip="Hapus" style="color:#e53935;"><i class="material-icons">delete</i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php // Proses hapus sederhana
        if(!$readOnly && isset($_GET['del']) && ctype_digit($_GET['del'])){
            $did = (int)$_GET['del'];
            $cek = mysqli_query($config,"SELECT id_user,file_path FROM tbl_arsip_berkas WHERE id=$did");
            if($cek && mysqli_num_rows($cek)==1){ $d=mysqli_fetch_assoc($cek); if((int)$d['id_user']===(int)$_SESSION['id_user']){ if(!empty($d['file_path']) && file_exists(BASE_PATH.'/'.$d['file_path'])) @unlink(BASE_PATH.'/'.$d['file_path']); mysqli_query($config,"DELETE FROM tbl_arsip_berkas WHERE id=$did"); echo '<script>location.href="index.php?page=admin&act=arsip_op";</script>'; } }
        } ?>
    <?php endif; ?>
</div>

<!-- Modal daftar surat pada berkas -->
<div id="modalListSurat" class="modal" style="max-height:80%;">
    <div class="modal-content" style="padding-bottom:8px;">
        <h5>Daftar Surat pada Berkas</h5>
        <div id="wrapListSurat" style="max-height:360px; overflow:auto; border:1px solid #eceff1; border-radius:6px;">
            <div class="progress"><div class="indeterminate"></div></div>
        </div>
        <div class="right-align" style="margin-top:10px;">
            <a href="#" class="modal-close btn-flat">Tutup</a>
        </div>
    </div>
    <script>
    function lihatSurat(id){
        const el = document.getElementById('modalListSurat');
        document.getElementById('wrapListSurat').innerHTML = '<div class="progress"><div class="indeterminate"></div></div>';
        try{
            if (window.jQuery && jQuery.fn.openModal) {
                jQuery('#modalListSurat').openModal();
            } else if (window.M && M.Modal) {
                const inst = M.Modal.getInstance(el) || M.Modal.init(el,{dismissible:true});
                inst.open();
            } else {
                el.style.display='block';
            }
        }catch(e){ el.style.display='block'; }
        fetch('src/SuratKeluar/arsip_berkas_surat_list.php?id='+encodeURIComponent(id), {credentials:'same-origin'})
            .then(r=>r.text())
            .then(html=>{ document.getElementById('wrapListSurat').innerHTML=html; })
            .catch(()=>{ document.getElementById('wrapListSurat').innerHTML='<div class="red-text" style="padding:12px;">Gagal memuat.</div>'; });
        return false;
    }
    </script>
</div>

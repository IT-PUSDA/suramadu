<?php
// Transaksi Surat Keluar - Keuangan
if (empty($_SESSION['admin'])) { $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>'; header('Location: index.php'); die(); }

$id_user = $_SESSION['id_user'];
if (isset($_REQUEST['sub'])) {
    switch ($_REQUEST['sub']) {
        case 'add_keuangan': include 'tambah_surat_keluar_keuangan.php'; break;
        case 'edit': include 'edit_surat_keluar.php'; break;
        case 'del': include 'hapus_surat_keluar.php'; break;
        case 'proses_tambah_keuangan': include 'proses_tambah_surat_keluar_keuangan.php'; break;
        default: include 'tambah_surat_keluar_keuangan.php'; break;
    }
    return;
}

$query_sett = mysqli_query($config, "SELECT surat_keluar FROM tbl_sett");
list($surat_keluar) = mysqli_fetch_array($query_sett);
$limit = $surat_keluar; $pg = @$_GET['pg']; $curr = empty($pg) ? 0 : (($pg - 1) * $limit);

if (!function_exists('indoDate')) {
    function indoDate($date){ $bulan=[1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember']; $e=explode('-', $date); return count($e)==3 ? $e[2].' '.$bulan[(int)$e[1]].' '.$e[0] : $date; }
}

$hasJenis = false; $resJenis = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'"); if ($resJenis && mysqli_num_rows($resJenis) === 1) { $hasJenis = true; } else { mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN jenis VARCHAR(20) NOT NULL DEFAULT 'umum'"); $chk=mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'"); if ($chk && mysqli_num_rows($chk) === 1) { $hasJenis = true; } }

?>

<div class="row">
    <div class="col s12">
        <div class="z-depth-1">
            <nav class="secondary-nav">
                <div class="nav-wrapper blue-grey darken-1">
                    <div class="col m7">
                        <ul class="left">
                            <li class="waves-effect waves-light hide-on-small-only"><a href="index.php?page=admin&act=tsk_keu" class="judul"><i class="material-icons">drafts</i> Surat Keluar - Keuangan</a></li>
                            <li class="waves-effect waves-light"><a href="index.php?page=admin&act=tsk_keu&sub=add_keuangan"><i class="material-icons md-24">add_circle</i> Tambah Data</a></li>
                        </ul>
                    </div>
                    <div class="col m5 hide-on-med-and-down">
                        <form method="post" action="index.php?page=admin&act=tsk_keu">
                            <div class="input-field round-in-box">
                                <input id="search" type="search" name="cari" placeholder="Ketik untuk mencari data..." autocomplete="off" required>
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

<?php foreach (['succAdd','succEdit','succDel'] as $k) { if (isset($_SESSION[$k])) { $msg=$_SESSION[$k]; echo '<div id="alert-message" class="row"><div class="col m12"><div class="card green lighten-5"><div class="card-content notif"><span class="card-title green-text"><i class="material-icons md-36">done</i> '.$msg.'</span></div></div></div></div>'; unset($_SESSION[$k]); } } ?>

<?php
$is_admin_user = ($_SESSION['admin'] == 4); // Bidang
$is_operator   = ($_SESSION['admin'] == 3); // Operator
if (!function_exists('operator_access_info')) { @include_once __DIR__ . '/../include/operator_access.php'; }
$base_query = "FROM tbl_surat_keluar"; $where_clause = '';
if ($hasJenis) { $where_clause .= " WHERE jenis='keuangan'"; }
// Data scoping
$operator_allowed_ids = [];
if ($is_operator) {
  $opInfo = operator_access_info($config, $_SESSION);
  $operator_allowed_ids = $opInfo['allowed_ids'];
  if (empty($operator_allowed_ids)) $operator_allowed_ids[]=(int)$id_user;
  $where_clause .= ($where_clause? ' AND ':' WHERE ') . ' id_user IN (' . implode(',', array_map('intval',$operator_allowed_ids)) . ')';
} elseif ($is_admin_user) { $where_clause .= ($where_clause ? ' AND ' : ' WHERE ') . " id_user='".intval($id_user)."'"; }

$map = [ 'sekretariat'=>['SEKRETARIAT','TU'], 'psda'=>['PSDA'], 'irigasi'=>['IRIGASI'], 'swp'=>['SWP'], 'binfat'=>['BINFAT'], 'upt-kediri'=>['KEDIRI'], 'korwil-malang'=>['MALANG'], 'korwil-surabaya'=>['SURABAYA'], 'upt-bojonegoro'=>['BOJONEGORO'], 'korwil-madiun'=>['MADIUN'], 'upt-bondowoso'=>['BONDOWOSO'], 'upt-lumajang'=>['LUMAJANG'], 'upt-pasuruan'=>['PASURUAN'], 'upt-madura'=>['MADURA'] ];
if (isset($_GET['filter_bidang']) && $_GET['filter_bidang'] !== '' && isset($map[$_GET['filter_bidang']])) {
    $usernames = array_map('strtoupper', $map[$_GET['filter_bidang']]);
    $in = "'" . implode("','", array_map(function($s) use ($config){ return mysqli_real_escape_string($config, $s); }, $usernames)) . "'";
    $res = mysqli_query($config, "SELECT id_user, UPPER(username) AS uname FROM tbl_user WHERE UPPER(username) IN ($in)");
    $ids = []; if ($res) { while ($r = mysqli_fetch_assoc($res)) { $ids[] = (int)$r['id_user']; } }
    if ($ids) { $where_clause .= ($where_clause ? ' AND ' : ' WHERE ') . ' id_user IN (' . implode(',', $ids) . ')'; }
}

if (isset($_REQUEST['submit'])) { $cari = mysqli_real_escape_string($config, $_REQUEST['cari']); $search = "(isi LIKE '%$cari%' OR perihal LIKE '%$cari%' OR tujuan LIKE '%$cari%' OR No_Surat LIKE '%$cari%')"; $where_clause .= ($where_clause ? ' AND ' : ' WHERE ') . $search; }

$query = mysqli_query($config, "SELECT * " . $base_query . $where_clause . " ORDER BY id_surat DESC LIMIT $curr, $limit");

// Quick-switch per jenis jika filter bidang aktif
$filterKey = isset($_GET['filter_bidang']) ? $_GET['filter_bidang'] : '';
if ($filterKey && isset($map[$filterKey])) {
  $usernames = array_map('strtoupper', $map[$filterKey]);
  $in = "'" . implode("','", array_map(function($s) use ($config){ return mysqli_real_escape_string($config, $s); }, $usernames)) . "'";
  $res = mysqli_query($config, "SELECT id_user FROM tbl_user WHERE UPPER(username) IN ($in)");
  $ids = []; if ($res) { while ($r = mysqli_fetch_assoc($res)) { $ids[] = (int)$r['id_user']; } }
  if ($ids) {
    $idList = implode(',', array_map('intval', $ids));
    $countUmum = $countND = $countPH = $countKEU = 0;
    $qU = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_user IN ($idList) AND jenis='umum'");
    $qN = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_user IN ($idList) AND jenis='nota_dinas'");
    $qP = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_user IN ($idList) AND jenis='produk_hukum'");
    $qK = mysqli_query($config, "SELECT COUNT(*) AS c FROM tbl_surat_keluar WHERE id_user IN ($idList) AND jenis='keuangan'");
    if ($qU) { $countUmum = (int)mysqli_fetch_assoc($qU)['c']; }
    if ($qN) { $countND = (int)mysqli_fetch_assoc($qN)['c']; }
    if ($qP) { $countPH = (int)mysqli_fetch_assoc($qP)['c']; }
    if ($qK) { $countKEU = (int)mysqli_fetch_assoc($qK)['c']; }
    echo '<div class="row" style="margin: 6px 0 12px;">'
      . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card lime darken-1 hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">label</i> Umum</span><h6 class="white-text hs-sub">' . number_format($countUmum) . ' SURAT</h6></div></div></a></div>'
      . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk_nd&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card teal hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">assignment</i> Nota Dinas</span><h6 class="white-text hs-sub">' . number_format($countND) . ' SURAT</h6></div></div></a></div>'
      . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk_ph&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card deep-orange hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">gavel</i> Produk Hukum</span><h6 class="white-text hs-sub">' . number_format($countPH) . ' SURAT</h6></div></div></a></div>'
      . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk_keu&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card indigo hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">attach_money</i> Keuangan</span><h6 class="white-text hs-sub">' . number_format($countKEU) . ' SURAT</h6></div></div></a></div>'
      . '</div>';
  }
}
?>

<div class="row jarak-form">
  <div class="col m12" id="colres">
    <div class="card">
      <div class="card-content">
        <div class="table-responsive">
          <table class="striped highlight responsive-table" id="tbl">
            <thead class="blue lighten-4" id="head">
              <tr>
                <th width="12%" class="center-align no-wrap">No. Agenda<br /><small>Kode</small></th>
                <th width="15%">Isi Ringkas<br /><small>File</small></th>
                <th width="20%" class="center-align">Tujuan<br /><small>Perihal</small></th>
                <th width="15%" class="center-align">No. Surat<br /><small>Tgl Surat</small></th>
                <th width="23%" class="center-align">Pembuat<br /><small>Tgl Dibuat</small></th>
                <th width="10%" class="center-align">Tindakan</th>
              </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($query)>0) { while ($row = mysqli_fetch_array($query)) { ?>
              <tr style="vertical-align: top;">
                <td class="center-align"><?php echo $row['no_agenda']; ?><hr class="grey lighten-3" style="margin:4px 0;"/><?php echo $row['kode']; ?></td>
                <td><?php echo $row['isi']; if (!empty($row['file'])) { echo '<br/><br/><strong>File : </strong>'; $is_operator_file = $is_operator && !empty($operator_allowed_ids) && in_array((int)$row['id_user'],$operator_allowed_ids,true); if ($_SESSION['admin']==1 || $_SESSION['admin']==2 || $is_operator_file) { echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat='.$row['id_surat'].'" target="_blank" rel="noopener" style="text-decoration: underline;">'.$row['file'].'</a>'; } else { echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat='.$row['id_surat'].'" class="pin-trigger" data-action-type="view" data-id-surat="'.$row['id_surat'].'" style="text-decoration: underline;">'.$row['file'].'</a>'; } } ?></td>
                <td class="center-align"><?php echo $row['tujuan']; ?><br/><small class="grey-text text-darken-1"><?php echo $row['perihal']; ?></small></td>
                <td class="center-align"><?php echo $row['no_surat']; ?><br/><small class="grey-text text-darken-1 nowrap"><?php echo indoDate($row['tgl_surat']); ?></small></td>
                <td class="center-align"><?php echo $row['nama_pembuat'] ?? ''; ?><br/><small class="grey-text text-darken-1 nowrap"><?php echo isset($row['tgl_dibuat']) ? date('d M Y, H:i', strtotime($row['tgl_dibuat'])) : ''; ?></small></td>
                <td class="center-align">
                  <?php if ($_SESSION['admin']==2) { echo '<div class="grey-text" style="padding-top: 15px;">-</div>'; } else { $can_manage = in_array($_SESSION['admin'], [1]); $is_owner = ($row['id_user'] == $_SESSION['id_user']); $is_operator_owner = $is_operator && !empty($operator_allowed_ids) && in_array((int)$row['id_user'],$operator_allowed_ids,true); if ($can_manage || $is_owner || $is_operator_owner) { echo '<div class="actions-compact" style="display:flex; justify-content:center; gap:0px; padding-top:5px;">'; if ($_SESSION['admin']==1 || $is_operator_owner) { echo '<a class="btn small blue waves-effect waves-light" style="color:white;" href="?page=admin&act=tsk_keu&sub=edit&id_surat='.$row['id_surat'].'"><i class="material-icons" style="color:white;">edit</i> EDIT</a>'; echo '<a class="btn small deep-orange waves-effect waves-light" style="color:white;" href="?page=admin&act=tsk_keu&sub=del&id_surat='.$row['id_surat'].'" onclick="return confirm(\'Yakin ingin menghapus surat ini?\');"><i class="material-icons" style="color:white;">delete</i> DEL</a>'; } else { echo '<a class="btn small blue waves-effect waves-light pin-trigger" style="color:white;" href="?page=admin&act=tsk_keu&sub=edit&id_surat='.$row['id_surat'].'" data-action-type="edit" data-id-surat="'.$row['id_surat'].'"><i class="material-icons" style="color:white;">edit</i> EDIT</a>'; echo '<a class="btn small deep-orange waves-effect waves-light pin-trigger" style="color:white;" href="?page=admin&act=tsk_keu&sub=del&id_surat='.$row['id_surat'].'" data-action-type="delete" data-id-surat="'.$row['id_surat'].'"><i class="material-icons" style="color:white;">delete</i> DEL</a>'; } echo '</div>'; } else { echo '<div class="grey-text" style="padding-top: 15px;">-</div>'; } } ?>
                </td>
              </tr>
            <?php } } else { echo '<tr><td colspan="6" class="center-align"><div class="card-panel grey lighten-4" style="margin: 20px;"><i class="material-icons large grey-text">inbox</i><p class="grey-text">Tidak ada data untuk ditampilkan.</p></div></td></tr>'; } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $query_pg = mysqli_query($config, "SELECT 1 " . $base_query . $where_clause); $cdata = mysqli_num_rows($query_pg); $cpg = ceil($cdata / $limit); $extra=''; if (!empty($_GET['filter_bidang'])) { $extra .= '&filter_bidang=' . urlencode($_GET['filter_bidang']); } echo '<br/><div class="center-align" style="margin:12px 0 8px;"><ul class="pagination pager">'; if ($cdata>$limit) { if ($pg>1) { $prev=$pg-1; echo '<li><a href="index.php?page=admin&act=tsk_keu&pg=1'.$extra+'"><i class="material-icons md-48">first_page</i></a></li><li><a href="index.php?page=admin&act=tsk_keu&pg='.$prev.$extra+'"><i class="material-icons md-48">chevron_left</i></a></li>'; } else { echo '<li class="disabled"><a><i class="material-icons md-48">first_page</i></a></li><li class="disabled"><a><i class="material-icons md-48">chevron_left</i></a></li>'; } if ($pg<$cpg) { $next=$pg+1; echo '<li><a href="index.php?page=admin&act=tsk_keu&pg='.$next.$extra+'"><i class="material-icons md-48">chevron_right</i></a></li><li><a href="index.php?page=admin&act=tsk_keu&pg='.$cpg.$extra+'"><i class="material-icons md-48">last_page</i></a></li>'; } else { echo '<li class="disabled"><a><i class="material-icons md-48">chevron_right</i></a></li><li class="disabled"><a><i class="material-icons md-48">last_page</i></a></li>'; } } echo '</ul></div>'; ?>

<style>
    th.no-wrap { white-space: nowrap; }
    .nowrap { white-space: nowrap; }
    .actions-compact a.btn { margin-left:0!important; margin-right:6px!important; }
    .actions-compact a.btn:last-child { margin-right:0!important; }
    #tbl { table-layout: fixed; width:100%; border-collapse: collapse; }
    #tbl thead th, #tbl tbody td { box-sizing: border-box; }
    #tbl thead th:nth-child(1){ width:10%; }
    #tbl thead th:nth-child(2){ width:30%; }
    #tbl thead th:nth-child(3){ width:14%; }
    #tbl thead th:nth-child(4){ width:18%; }
    #tbl thead th:nth-child(5){ width:12%; }
    #tbl thead th:nth-child(6){ width:15%; }
</style>

<!-- PIN modal + styles (match transaksi_surat_keluar.php) -->
<style>
    .pin-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); display: none; justify-content: center; align-items: center; z-index: 1002; backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); }
    .pin-modal-container { background-color: rgba(255, 255, 255, 0.95); border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); padding: 30px; width: 100%; max-width: 450px; text-align: center; position: relative; transform: scale(0.9); opacity: 0; transition: transform 0.3s ease, opacity 0.3s ease; }
    .pin-modal-overlay.active .pin-modal-container { transform: scale(1); opacity: 1; }
    .pin-modal-close { position: absolute; top: 10px; right: 15px; font-size: 2rem; color: #9e9e9e; cursor: pointer; transition: color 0.2s; }
    .pin-modal-close:hover { color: #616161; }
    .pin-modal-title { font-size: 1.8rem; font-weight: 500; margin-bottom: 10px; color: #424242; }
    .pin-code-container { display: flex; justify-content: center; gap: 10px; margin: 30px 0; }
    .pin-code-input { width: 45px !important; height: 55px !important; font-size: 24px !important; text-align: center !important; border: 2px solid #bdbdbd !important; border-radius: 8px !important; box-shadow: none !important; padding: 0 !important; background-color: #fff !important; }
    .pin-code-input:focus { border-color: #2196F3 !important; box-shadow: 0 0 8px 0 rgba(33, 150, 243, 0.5) !important; }
    .pin-modal-btn { border-radius: 25px; height: 45px; line-height: 45px; }
    .pin-error-message { color: #f44336; margin-top: 15px; font-weight: 500; min-height: 21px; }
    .pagination.pager { display:inline-flex; align-items:center; }
    .pagination.pager li { margin: 0 3px; }
    .pagination.pager li a { display:inline-flex; align-items:center; justify-content:center; gap:4px; border:1px solid rgba(0,0,0,.12); border-radius:10px; background:#fff; color:#455a64; height:36px; min-width:36px; padding:0 10px; box-shadow:0 1px 2px rgba(0,0,0,.08); }
    .pagination.pager li.active a { background:#1e88e5; color:#fff; border-color:#1e88e5; }
    .pagination.pager li.disabled a { background:#f5f5f5; color:#bdbdbd; border-color:rgba(0,0,0,.08); pointer-events:none; }
    .pagination.pager i.material-icons { font-size:20px; line-height:36px; height:36px; }
    .pagination.pager i.material-icons.md-48 { font-size:20px; }
</style>

<div id="pinModal" class="pin-modal-overlay">
  <div class="pin-modal-container">
    <span class="pin-modal-close">&times;</span>
    <i class="material-icons large blue-grey-text text-darken-1">https</i>
    <h5 class="pin-modal-title">Verifikasi PIN</h5>
    <p class="grey-text text-darken-1">Aksi ini memerlukan izin. Silakan masukkan 6 digit PIN.</p>
    <form id="pinForm" method="POST" action="#">
      <input type="hidden" name="pin" id="fullPin">
      <div class="pin-code-container">
        <input type="tel" class="pin-code-input" maxlength="1" pattern="[0-9]" required>
        <input type="tel" class="pin-code-input" maxlength="1" pattern="[0-9]" required>
        <input type="tel" class="pin-code-input" maxlength="1" pattern="[0-9]" required>
        <input type="tel" class="pin-code-input" maxlength="1" pattern="[0-9]" required>
        <input type="tel" class="pin-code-input" maxlength="1" pattern="[0-9]" required>
        <input type="tel" class="pin-code-input" maxlength="1" pattern="[0-9]" required>
      </div>
      <p id="pinErrorMessage" class="pin-error-message"></p>
      <div class="row"><div class="input-field col s12"><button type="submit" class="btn waves-effect waves-light blue darken-1 pin-modal-btn">Verifikasi & Lanjutkan</button></div></div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const modal=document.getElementById('pinModal'); const modalClose=modal.querySelector('.pin-modal-close'); const pinForm=document.getElementById('pinForm'); const pinInputs=[...pinForm.querySelectorAll('.pin-code-input')]; const fullPinInput=document.getElementById('fullPin'); const errorMessage=document.getElementById('pinErrorMessage'); let targetUrl='', actionType='', suratId='';
  function openModal(){ modal.style.display='flex'; setTimeout(()=>modal.classList.add('active'),10); pinInputs[0].focus(); }
  function closeModal(){ modal.classList.remove('active'); setTimeout(()=>{ modal.style.display='none'; pinForm.reset(); pinInputs.forEach(i=>i.value=''); errorMessage.textContent=''; targetUrl=''; actionType=''; suratId=''; },300); }
  function updateFullPin(){ fullPinInput.value = pinInputs.map(i=>i.value).join(''); }
  function attachPin(){ document.querySelectorAll('.pin-trigger').forEach(a=>a.addEventListener('click', function(e){ e.preventDefault(); targetUrl=this.getAttribute('href'); actionType=this.dataset.actionType; suratId=this.dataset.idSurat; openModal(); })); }
  attachPin();
  modalClose.addEventListener('click', closeModal); modal.addEventListener('click', e=>{ if(e.target===modal) closeModal(); });
  pinInputs.forEach((input,idx)=>{ input.addEventListener('input', ()=>{ if(input.value && idx<pinInputs.length-1) pinInputs[idx+1].focus(); updateFullPin(); }); input.addEventListener('keydown', e=>{ if(e.key==='Backspace' && !input.value && idx>0) pinInputs[idx-1].focus(); }); input.addEventListener('paste', e=>{ e.preventDefault(); const t=(e.clipboardData||window.clipboardData).getData('text').replace(/\s/g,'').slice(0,6); t.split('').forEach((c,i)=>{ if(pinInputs[i]) pinInputs[i].value=c; }); const li=Math.min(t.length,6)-1; if(li>=0) pinInputs[li].focus(); updateFullPin(); }); });
  pinForm.addEventListener('submit', function(e){ e.preventDefault(); updateFullPin(); if(fullPinInput.value.length!==6){ errorMessage.textContent='PIN harus terdiri dari 6 digit.'; return; } errorMessage.textContent=''; const fd=new FormData(); fd.append('id_surat', suratId); fd.append('pin', fullPinInput.value); fetch('src/Utils/verifikasi_pin_ajax.php',{method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.success){ closeModal(); setTimeout(()=>{ if(actionType==='delete'){ if(confirm('PIN terverifikasi. Apakah Anda yakin ingin menghapus data ini?')) window.location.href=targetUrl; } else if(actionType==='view'){ window.open(targetUrl,'_blank'); } else { window.location.href=targetUrl; } },200); } else { errorMessage.textContent=d.message||'PIN salah. Coba lagi.'; pinInputs.forEach(i=>i.value=''); pinInputs[0].focus(); } }).catch(()=>{ errorMessage.textContent='Terjadi kesalahan. Silakan coba lagi.'; }); });
});
</script>

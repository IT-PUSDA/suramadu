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
<script>document.body.classList.add('page-surat-keluar');</script>
<style>body.page-surat-keluar .container{max-width:100%!important;width:100%!important;padding-left:18px;padding-right:18px}.full-bleed{width:100%;max-width:100%;margin:0 auto}.full-bleed.row{margin-left:0;margin-right:0}@media(max-width:600px){body.page-surat-keluar .container{padding-left:10px;padding-right:10px}}</style>

<div class="row full-bleed">
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
                        <script>
                        // Delegated copy-to-clipboard handler for nomor surat
                        document.addEventListener('click', function (e) {
                          var btn = e.target.closest && e.target.closest('.copy-no-surat');
                          if (!btn) return;
                          e.preventDefault();
                          var nomor = btn.getAttribute('data-nomor') || '';
                          if (!nomor) return;
                          function notify(msg){ if (window.M && M.toast) { M.toast({html: msg, displayLength: 2000}); } else { alert(msg); } }
                          function fallbackCopy(text){ var ta=document.createElement('textarea'); ta.value=text; ta.style.position='fixed'; ta.style.left='-9999px'; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); notify('Nomor surat disalin: '+text); }catch(e){ alert('Gagal menyalin nomor surat. Silakan salin manual: '+text); } document.body.removeChild(ta); }
                          if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(nomor).then(function(){ notify('Nomor surat disalin: ' + nomor); }).catch(function(){ fallbackCopy(nomor); });
                          } else { fallbackCopy(nomor); }
                        });
                        </script>
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
  . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card lime darken-1 hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">label</i> Umum</span><h6 class="white-text hs-sub">' . number_format((int)$countUmum) . ' SURAT</h6></div></div></a></div>'
  . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk_nd&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card teal hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">assignment</i> Nota Dinas</span><h6 class="white-text hs-sub">' . number_format((int)$countND) . ' SURAT</h6></div></div></a></div>'
  . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk_ph&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card deep-orange hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">gavel</i> Produk Hukum</span><h6 class="white-text hs-sub">' . number_format((int)$countPH) . ' SURAT</h6></div></div></a></div>'
  . '<div class="col s12 m6 l3"><a href="index.php?page=admin&act=tsk_keu&filter_bidang=' . urlencode($filterKey) . '" class="hs-link"><div class="card indigo hs-card"><div class="card-content"><span class="card-title white-text"><i class="material-icons md-24">attach_money</i> Keuangan</span><h6 class="white-text hs-sub">' . number_format((int)$countKEU) . ' SURAT</h6></div></div></a></div>'
      . '</div>';
  }
}
?>

<div class="row jarak-form full-bleed">
  <div class="col m12" id="colres">
    <div class="card">
      <div class="card-content">
        <div class="table-responsive">
          <table class="striped highlight responsive-table" id="tbl">
            <thead class="blue lighten-4" id="head">
              <tr>
                <th class="center-align no-wrap" style="width:3%">No</th>
                <th style="width:30%">Isi Ringkas<br /><small>File</small></th>
                <th class="center-align" style="width:14%">Tujuan<br /><small>Perihal</small></th>
                <th class="center-align" style="width:18%">No. Surat<br /><small>Tgl Surat</small></th>
                <th class="center-align" style="width:12%">Pembuat<br /><small>Tgl Dibuat</small></th>
                <th class="center-align" style="width:10%">Status</th>
                <th class="center-align" style="width:17%">Aksi</th>
              </tr>
            </thead>
            <tbody id="tbody-data">
            <?php if (mysqli_num_rows($query)>0) { $seq=$curr+1; while ($row = mysqli_fetch_array($query)) { ?>
              <tr style="vertical-align: top;">
                <td class="center-align"><strong><?php echo $seq; ?></strong></td>
                <td><?php echo $row['isi'];
                    $driveMarker = !empty($row['file_drive']) ? $row['file_drive'] : ( (!empty($row['file']) && strpos($row['file'],'gdrive:fileId=')===0) ? $row['file'] : '');
                    $hasAnyFile = (!empty($row['file']) || !empty($driveMarker));
                    if ($hasAnyFile) { echo '<br/><br/><strong>File : </strong>'; $is_operator_file = $is_operator && !empty($operator_allowed_ids) && in_array((int)$row['id_user'],$operator_allowed_ids,true);
                    $linkText = !empty($row['file']) ? $row['file'] : '';
                    if (!empty($driveMarker)) {
                        $label = (isset($row['file_no']) && $row['file_no'] !== null && $row['file_no'] !== '') ? str_pad((string)$row['file_no'], 4, '0', STR_PAD_LEFT) : 'Lampiran';
                        $fullName = null; if (preg_match('/\\|name=([^|]+)/', $driveMarker, $m)) { $fullName = rawurldecode($m[1]); }
                        $linkText = $fullName ? $fullName : ('Berkas ' . $label);
                    }
                  if ($_SESSION['admin']==1 || $_SESSION['admin']==2 || $is_operator_file) {
                    echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat='.$row['id_surat'].'" target="_blank" rel="noopener" style="text-decoration: underline;">'.htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8').'</a>';
                  } else {
                    echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat='.$row['id_surat'].'" class="pin-trigger" data-action-type="view" data-id-surat="'.$row['id_surat'].'" style="text-decoration: underline;">'.htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8').'</a>';
                  }
                  if (!empty($_SESSION['pinResetIds'][$row['id_surat']])) { echo ' <span class="new badge blue" data-badge-caption="PIN diubah"></span>'; } } ?></td>
                <td class="center-align"><?php echo $row['tujuan']; ?><br/><small class="grey-text text-darken-1"><?php echo $row['perihal']; ?></small></td>
                <td class="center-align">
                  <?php echo htmlspecialchars($row['no_surat'], ENT_QUOTES, 'UTF-8'); ?>
                  <a href="#" class="copy-no-surat tooltipped" data-id="<?php echo (int)$row['id_surat']; ?>" data-nomor="<?php echo htmlspecialchars($row['no_surat'], ENT_QUOTES, 'UTF-8'); ?>" data-position="top" data-tooltip="Salin Nomor" style="margin-left:8px;display:inline-block;vertical-align:middle;text-decoration:none;color:#1976d2;">
                    <i class="material-icons" style="font-size:18px;vertical-align:middle">content_copy</i>
                  </a>
                  <br/><small class="grey-text text-darken-1 nowrap"><?php echo indoDate($row['tgl_surat']); ?></small>
                </td>
                <td class="center-align"><?php echo $row['nama_pembuat'] ?? ''; ?><br/><small class="grey-text text-darken-1 nowrap"><?php echo isset($row['tgl_dibuat']) ? date('d M Y, H:i', strtotime($row['tgl_dibuat'])) : ''; ?></small></td>
                <?php $status_raw = isset($row['status'])?$row['status']:( !empty($row['file']) ? 'finished':'draft'); $icon_file = ($status_raw=='finished')?'finished.png':'draft.png'; if(empty($__printedStatusStyleKEU)){ echo '<style>.status-cell{padding:2px 0!important;} .status-cell .status-wrap{display:flex;align-items:center;justify-content:center;height:50px;} .status-cell img.status-icon{height:48px;position:relative;top:8px;filter:drop-shadow(0 1px 2px rgba(0,0,0,.15));} .actions-compact{align-items:center;min-height:46px;} .action-round{background:#1976d2!important;border-radius:50%;width:46px;height:46px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.15);transition:.25s} .action-round i{line-height:46px;font-size:22px;color:#fff} .action-round.delete{background:#e64a19!important;} .action-round.toggle{background:#2e7d32!important;} .action-round:hover{filter:brightness(1.08);} .action-round:active{transform:scale(.92);} @media(max-width:600px){.status-cell .status-wrap{height:46px;} .status-cell img.status-icon{height:44px;top:6px;} .action-round{width:40px;height:40px;} .action-round i{font-size:20px;line-height:40px;}}</style>'; $__printedStatusStyleKEU=true; } ?>
                <td class="center-align status-cell"><div class="status-wrap"><img class="status-icon status-icon-<?php echo $row['id_surat']; ?>" src="asset/img/<?php echo $icon_file; ?>" alt="status" /></div></td>
                <td class="center-align"><?php if ($_SESSION['admin']==2) { echo '<div class="grey-text" style="padding-top: 15px;">-</div>'; } else { $can_manage = in_array($_SESSION['admin'], [1]); $is_owner = ($row['id_user'] == $_SESSION['id_user']); $is_operator_owner = $is_operator && !empty($operator_allowed_ids) && in_array((int)$row['id_user'],$operator_allowed_ids,true); if ($can_manage || $is_owner || $is_operator_owner) { echo '<div class="actions-compact" style="display:flex;justify-content:center;flex-wrap:wrap;padding-top:2px;">'; $btnBase='data-position="top"'; if(empty($__printedActionStyleKEU)){ echo '<style>.action-round{background:#1976d2;border-radius:50%;width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.15);transition:.25s;margin:3px 4px} .action-round i{line-height:44px;font-size:22px;color:#fff} .action-round.delete{background:#e64a19} .action-round.toggle{background:#2e7d32} .action-round.arch{background:#fbc02d!important} .action-round.arch.done{background:#9e9e9e!important} .action-round:hover{filter:brightness(1.08);} .action-round:active{transform:scale(.92);} @media(max-width:600px){.action-round{width:40px;height:40px;} .action-round i{font-size:20px;line-height:40px;}}</style>'; $__printedActionStyleKEU=true; } $is_operator_level = ($_SESSION['admin']==3); if($is_operator_level){ $toggleTitle = ($status_raw=='finished')?'Set Draft':'Set Finished'; echo '<a class="waves-effect waves-light tooltipped action-round toggle" '.$btnBase.' data-tooltip="'.$toggleTitle.'" href="#" onclick="return toggleStatus('.$row['id_surat'].', event);"><i class="material-icons">autorenew</i></a>'; } if ($_SESSION['admin']==1 || $is_operator_owner) { echo '<a class="waves-effect waves-light tooltipped action-round" '.$btnBase.' data-tooltip="Edit" href="?page=admin&act=tsk_keu&sub=edit&id_surat='.$row['id_surat'].'"><i class="material-icons">edit</i></a>'; echo '<a class="waves-effect waves-light tooltipped action-round delete" '.$btnBase.' data-tooltip="Hapus" href="?page=admin&act=tsk_keu&sub=del&id_surat='.$row['id_surat'].'" onclick="return confirm(\'Yakin ingin menghapus surat ini?\');"><i class="material-icons">delete</i></a>'; if($is_operator_level){ $alreadyArsip=!empty($row['id_arsip_berkas']); $archTip=$alreadyArsip?'Sudah diarsipkan':'Arsipkan'; $archCls=$alreadyArsip?' arch done':' arch'; $archOnclick=$alreadyArsip?'return false;':'return openArsipModal('.$row['id_surat'].');'; echo '<a class="waves-effect waves-light tooltipped action-round'.$archCls.'" '.$btnBase.' data-tooltip="'.$archTip.'" href="#" onclick="'.$archOnclick.'"><i class="material-icons">archive</i></a>'; } } else { echo '<a class="waves-effect waves-light tooltipped action-round pin-trigger" '.$btnBase.' data-tooltip="Edit (PIN)" href="?page=admin&act=tsk_keu&sub=edit&id_surat='.$row['id_surat'].'" data-action-type="edit" data-id-surat="'.$row['id_surat'].'"><i class="material-icons">edit</i></a>'; echo '<a class="waves-effect waves-light tooltipped action-round delete pin-trigger" '.$btnBase.' data-tooltip="Hapus (PIN)" href="?page=admin&act=tsk_keu&sub=del&id_surat='.$row['id_surat'].'" data-action-type="delete" data-id-surat="'.$row['id_surat'].'"><i class="material-icons">delete</i></a>'; if($is_operator_level){ $alreadyArsip=!empty($row['id_arsip_berkas']); $archTip=$alreadyArsip?'Sudah diarsipkan':'Arsipkan'; $archCls=$alreadyArsip?' arch done':' arch'; $archOnclick=$alreadyArsip?'return false;':'return openArsipModal('.$row['id_surat'].');'; echo '<a class="waves-effect waves-light tooltipped action-round'.$archCls.'" '.$btnBase.' data-tooltip="'.$archTip.'" href="#" onclick="'.$archOnclick.'"><i class="material-icons">archive</i></a>'; } } echo '</div>'; } else { echo '<div class="grey-text" style="padding-top: 15px;">-</div>'; } } ?></td>
              </tr>
            <?php $seq++; } } else { echo '<tr><td colspan="7" class="center-align"><div class="card-panel grey lighten-4" style="margin: 20px;"><i class="material-icons large grey-text">inbox</i><p class="grey-text">Tidak ada data untuk ditampilkan.</p></div></td></tr>'; } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Live search Keuangan
document.addEventListener('DOMContentLoaded', function(){
  const searchInput=document.getElementById('search');
  const tbody=document.getElementById('tbody-data');
  if(!searchInput||!tbody) return;
  const originalHTML=tbody.innerHTML; const jenis='keuangan';
  const infoPanelId='search-info-panel';
  if(!document.getElementById(infoPanelId)){
    const panel=document.createElement('div'); panel.id=infoPanelId; panel.className='card-panel blue-grey lighten-5'; panel.style.cssText='margin-bottom:20px;display:none'; panel.innerHTML='<p class="blue-grey-text">Hasil pencarian untuk: <strong class="black-text" id="search-info-text"></strong></p>';
    const cardContent=tbody.closest('.card-content'); if(cardContent) cardContent.insertBefore(panel, cardContent.firstChild);
  }
  const infoPanel=document.getElementById(infoPanelId); const infoText=document.getElementById('search-info-text');
  function debounce(fn,w){ let t; return function(...a){ clearTimeout(t); t=setTimeout(()=>fn.apply(this,a),w);} }
  function getFilterBidangParam(){ const p=new URLSearchParams(window.location.search); const v=p.get('filter_bidang'); return v?`&filter_bidang=${encodeURIComponent(v)}`:''; }
  function updateInfo(q){ if(q){ infoText.textContent=q; infoPanel.style.display='block'; } else { infoText.textContent=''; infoPanel.style.display='none'; } }
  function rebindPins(){ if(typeof attachPinHandlers==='function'){ attachPinHandlers(); } else if(typeof attachPin==='function'){ attachPin(); } }
  const doSearch=debounce(()=>{ const q=searchInput.value.trim(); if(q===''){ tbody.innerHTML=originalHTML; updateInfo(''); rebindPins(); return; } updateInfo(q); fetch(`src/SuratKeluar/ajax_search_surat_keluar.php?jenis=${jenis}&cari=${encodeURIComponent(q)}${getFilterBidangParam()}`,{credentials:'same-origin'}).then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.text(); }).then(html=>{ tbody.innerHTML=html; rebindPins(); }).catch(err=>console.error('Live search error (KEU):',err)); },300);
  searchInput.addEventListener('input', doSearch);
});
</script>

<?php $query_pg = mysqli_query($config, "SELECT 1 " . $base_query . $where_clause); $cdata = mysqli_num_rows($query_pg); $cpg = ceil($cdata / $limit); $extra=''; if (!empty($_GET['filter_bidang'])) { $extra .= '&filter_bidang=' . urlencode($_GET['filter_bidang']); } echo '<br/><div class="center-align" style="margin:12px 0 8px;"><ul class="pagination pager">'; if ($cdata>$limit) { if ($pg>1) { $prev=$pg-1; echo '<li><a href="index.php?page=admin&act=tsk_keu&pg=1'.$extra+'"><i class="material-icons md-48">first_page</i></a></li><li><a href="index.php?page=admin&act=tsk_keu&pg='.$prev.$extra+'"><i class="material-icons md-48">chevron_left</i></a></li>'; } else { echo '<li class="disabled"><a><i class="material-icons md-48">first_page</i></a></li><li class="disabled"><a><i class="material-icons md-48">chevron_left</i></a></li>'; } if ($pg<$cpg) { $next=$pg+1; echo '<li><a href="index.php?page=admin&act=tsk_keu&pg='.$next.$extra+'"><i class="material-icons md-48">chevron_right</i></a></li><li><a href="index.php?page=admin&act=tsk_keu&pg='.$cpg.$extra+'"><i class="material-icons md-48">last_page</i></a></li>'; } else { echo '<li class="disabled"><a><i class="material-icons md-48">chevron_right</i></a></li><li class="disabled"><a><i class="material-icons md-48">last_page</i></a></li>'; } } echo '</ul></div>'; ?>

<style>
    th.no-wrap { white-space: nowrap; }
    .nowrap { white-space: nowrap; }
    .actions-compact a.btn { margin-left:0!important; margin-right:6px!important; }
    .actions-compact a.btn:last-child { margin-right:0!important; }
    #tbl { table-layout: fixed; width:100%; border-collapse: collapse; }
    #tbl thead th, #tbl tbody td { box-sizing: border-box; }
    #tbl thead th:nth-child(1) { width: 3%; }
    #tbl thead th:nth-child(2) { width: 30%; }
    #tbl thead th:nth-child(3) { width: 14%; }
    #tbl thead th:nth-child(4) { width: 18%; }
    #tbl thead th:nth-child(5) { width: 12%; }
    #tbl thead th:nth-child(6) { width: 10%; }
    #tbl thead th:nth-child(7) { width: 17%; }
</style>

<?php if ($is_operator): ?>
<style>
  /* Compact mode khusus Operator */
  .table-compact #tbl thead th,
  .table-compact #tbl tbody td { padding: 8px 10px !important; }
  .table-compact #tbl tbody tr { line-height: 1.2; }
  .table-compact .status-cell .status-wrap { height: 36px; }
  .table-compact .status-cell img.status-icon { height: 32px; top: 0; }
  .table-compact .actions-compact { min-height: 38px; }
  .table-compact .action-round { width: 36px; height: 36px; }
  .table-compact .action-round i { font-size: 18px; line-height: 36px; }
  /* Judul kolom baris kedua (small) sedikit diperkecil */
  .table-compact thead small { font-size: 90%; }
  /* Kurangi margin tambahan di dalam sel isi */
  .table-compact td small { line-height: 1.2; }
  /* Hilangkan ruang besar di bawah tabel */
  .table-compact .card-content { padding-bottom: 12px; }
  @media (max-width: 600px) {
    .table-compact #tbl thead th,
    .table-compact #tbl tbody td { padding: 8px 8px !important; }
  }
  /* Rapikan badge */
  .table-compact .badge, .table-compact .new.badge { transform: scale(0.9); }
  /* Kurangi spasi link file */
  .table-compact a { line-height: 1.2; }
  .table-compact strong { font-weight: 600; }
  .table-compact .nowrap { white-space: nowrap; }
  .table-compact .no-wrap { white-space: nowrap; }
  .table-compact .status-cell { padding: 0 !important; }
  .table-compact .status-cell .status-wrap { margin: 0; }
  .table-compact .status-cell img { display: inline-block; }
  .table-compact .actions-compact { padding-top: 0 !important; }
  .table-compact .actions-compact .action-round { margin: 2px 3px; }
  .table-compact .actions-compact .action-round.delete { background: #e64a19 !important; }
  .table-compact .actions-compact .action-round.toggle { background: #2e7d32 !important; }
  .table-compact .actions-compact .action-round.arch { background: #fbc02d !important; }
  .table-compact .actions-compact .action-round.arch.done { background: #9e9e9e !important; }
</style>
<?php endif; ?>

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
<script>
function toggleStatus(id,ev){ if(!id) return false; const e=ev||window.event; const btn=e&&e.currentTarget?e.currentTarget:null; if(btn) btn.classList.add('disabled'); fetch('src/SuratKeluar/update_status.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(id)}) .then(async r=>{const t=await r.text(); try{return JSON.parse(t);}catch(err){console.error('Raw response toggleStatus KEU:',t); throw new Error('Response bukan JSON valid');}}) .then(j=>{ if(!j.ok) throw new Error(j.msg||'Gagal'); const img=document.querySelector('.status-icon-'+id); if(img){ img.src='asset/img/'+(j.status==='finished'?'finished.png':'draft.png'); } if(btn && btn.getAttribute){ btn.setAttribute('data-tooltip', j.status==='finished'?'Set Draft':'Set Finished'); if(typeof M!=='undefined' && M.Tooltip){ const inst=M.Tooltip.getInstance(btn); if(inst) inst.destroy(); M.Tooltip.init(btn,{}); } } }) .catch(err=>alert('Gagal toggle status: '+err.message)) .finally(()=>{ if(btn) btn.classList.remove('disabled'); }); return false; }
</script>

<?php
// Pastikan kolom relasi arsip tersedia pada tbl_surat_keluar
$__chk_rel_keu = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'id_arsip_berkas'");
if(!$__chk_rel_keu || mysqli_num_rows($__chk_rel_keu) == 0){
    @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN id_arsip_berkas INT NULL AFTER status, ADD INDEX idx_arsip_rel (id_arsip_berkas)");
}

// Handler AJAX sederhana untuk mengarsipkan surat (Operator saja)
if(isset($_POST['__ajax']) && $_POST['__ajax'] === 'arsip_surat' && $is_operator){
    header('Content-Type: application/json');
    $idSurat = (int)($_POST['id_surat'] ?? 0);
    $idArsip = (int)($_POST['id_arsip'] ?? 0);
    if($idSurat < 1 || $idArsip < 1){ echo json_encode(['ok'=>false,'msg'=>'Data tidak valid']); exit; }
    // Cek surat berada pada scope operator dan sudah finished
    $cek = mysqli_query($config, "SELECT id_surat,id_user,status,id_arsip_berkas FROM tbl_surat_keluar WHERE id_surat=$idSurat LIMIT 1");
    if(!$cek || mysqli_num_rows($cek) != 1){ echo json_encode(['ok'=>false,'msg'=>'Surat tidak ditemukan']); exit; }
    $s = mysqli_fetch_assoc($cek);
    if($s['status'] != 'finished'){ echo json_encode(['ok'=>false,'msg'=>'Surat belum berstatus finished']); exit; }
    if(!in_array((int)$s['id_user'], $operator_allowed_ids, true)){ echo json_encode(['ok'=>false,'msg'=>'Akses ditolak']); exit; }
    // Cek arsip milik operator bersangkutan
    $cekArsip = mysqli_query($config, "SELECT id,id_user FROM tbl_arsip_berkas WHERE id=$idArsip LIMIT 1");
    if(!$cekArsip || mysqli_num_rows($cekArsip) != 1){ echo json_encode(['ok'=>false,'msg'=>'Berkas arsip tidak ditemukan']); exit; }
    $a = mysqli_fetch_assoc($cekArsip);
    if((int)$a['id_user'] !== (int)$_SESSION['id_user']){ echo json_encode(['ok'=>false,'msg'=>'Berkas arsip bukan milik Anda']); exit; }
    if($s['id_arsip_berkas'] && (int)$s['id_arsip_berkas'] === $idArsip){ echo json_encode(['ok'=>true,'msg'=>'Sudah terarsip']); exit; }
    if(mysqli_query($config, "UPDATE tbl_surat_keluar SET id_arsip_berkas=$idArsip WHERE id_surat=$idSurat")){
        echo json_encode(['ok'=>true,'msg'=>'Surat berhasil diarsipkan']);
    } else {
        echo json_encode(['ok'=>false,'msg'=>'Gagal menyimpan']);
    }
    exit;
}
?>

<!-- Modal pilih arsip berkas (Operator) -->
<div id="arsipModal" class="modal" style="max-height:80%;">
  <div class="modal-content" style="padding-bottom:8px;">
    <h5>Pilih Berkas Arsip</h5>
    <div id="arsipList" style="max-height:320px; overflow:auto; border:1px solid #eceff1; border-radius:6px;">
      <div class="progress"><div class="indeterminate"></div></div>
    </div>
    <div style="margin-top:14px;" class="right-align">
      <a href="#!" class="modal-close btn-flat">Tutup</a>
    </div>
  </div>
  <script>
  // Arsip modal logic khusus halaman Keuangan
  (function(){
    let arsipTargetSurat = 0;
    window.openArsipModal = function(id){
      arsipTargetSurat = id;
      const modalEl = document.getElementById('arsipModal');
      const inst = (window.M && M.Modal.getInstance(modalEl)) || (window.M && M.Modal.init(modalEl,{dismissible:true}));
      document.getElementById('arsipList').innerHTML = '<div class="progress"><div class="indeterminate"></div></div>';
      if(inst) inst.open();
      fetch('src/SuratKeluar/arsip_list_ajax.php', {credentials:'same-origin'})
        .then(r=>r.ok?r.json():[])
        .then(d=>renderArsipList(d))
        .catch(()=>{ document.getElementById('arsipList').innerHTML='<div class="red-text" style="padding:12px;">Gagal memuat.</div>'; });
      return false;
    };
    function renderArsipList(data){
      if(!Array.isArray(data) || !data.length){ document.getElementById('arsipList').innerHTML='<div style="padding:12px;">Belum ada berkas arsip.</div>'; return; }
      const html = data.map(r=>`\n            <a href="#" data-id="${r.id}" class="collection-item" style="display:block;padding:10px 14px;border-bottom:1px solid #eceff1;">\n                <strong>${r.kode_klasifikasi}</strong> - ${r.nama_berkas}<br>\n                <small class="grey-text">${r.uraian||''}</small>\n            </a>`).join('');
      document.getElementById('arsipList').innerHTML = '<div class="collection" style="margin:0;">'+html+'</div>';
      document.querySelectorAll('#arsipList a.collection-item').forEach(a=>{
        a.addEventListener('click', function(e){ e.preventDefault(); pilihArsip(parseInt(this.getAttribute('data-id'),10)); });
      });
    }
    function pilihArsip(id){
      if(!arsipTargetSurat) return;
      const fd = new FormData();
      fd.append('__ajax','arsip_surat');
      fd.append('id_surat', arsipTargetSurat);
      fd.append('id_arsip', id);
      fetch(window.location.href, {method:'POST', body: fd, credentials:'same-origin'})
        .then(r=>r.json())
        .then(j=>{ if(j.ok){ location.reload(); } else { alert(j.msg||'Gagal'); } })
        .catch(()=>alert('Gagal'));
    }
  })();
  </script>
</div>

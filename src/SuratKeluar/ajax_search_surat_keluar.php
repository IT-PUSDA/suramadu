<?php
// Live search AJAX endpoint (Surat Keluar) diselaraskan dengan tampilan utama
session_start();
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/html; charset=utf-8');

if (empty($_SESSION['admin'])) {
    http_response_code(401);
    echo '<tr><td colspan="7" class="center-align">Sesi berakhir. Silakan login kembali.</td></tr>';
    exit;
}

// Helper tanggal Indonesia
if (!function_exists('indoDate')) {
    function indoDate($date) {
        $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        $exp = explode('-', $date);
        return count($exp)==3 ? $exp[2].' '.$bulan[(int)$exp[1]].' '.$exp[0] : $date;
    }
}
// Safe escape helper (hindari deprecated: htmlspecialchars(null))
if (!function_exists('esc')) {
    function esc($v) {
        if ($v === null) { $v = ''; }
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$id_user        = (int)$_SESSION['id_user'];
$level          = (int)$_SESSION['admin'];
$is_bidang      = ($level == 4); // Bidang
$is_operator    = ($level == 3); // Operator
$jenis          = isset($_GET['jenis']) && $_GET['jenis'] !== '' ? preg_replace('/[^a-z_]/','', $_GET['jenis']) : 'umum';

// Ambil limit dari setting
$limit = 10;
$qs = mysqli_query($config, "SELECT surat_keluar FROM tbl_sett");
if ($qs) { list($l) = mysqli_fetch_array($qs); if (!empty($l)) $limit = (int)$l; }

$pg = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$curr = ($pg - 1) * $limit;

// Basis query & filter
$base_query = "FROM tbl_surat_keluar";
$where = '';

// BATASAN AKSES
// Operator: id_user dalam kelompoknya
// Bidang   : hanya dirinya
// Lain (super admin) : semua
$operator_allowed_ids = [];
if ($is_operator) {
    // Gunakan helper central kalau ada
    if (!function_exists('operator_access_info')) { @include_once __DIR__ . '/../include/operator_access.php'; }
    if (function_exists('operator_access_info')) {
        $info = operator_access_info($config, $_SESSION);
        $operator_allowed_ids = $info['allowed_ids'];
    }
    if (empty($operator_allowed_ids)) { $operator_allowed_ids[] = $id_user; }
    $in = implode(',', array_map('intval', $operator_allowed_ids));
    $where .= ($where? ' AND ':' WHERE ') . " id_user IN ($in)";
} elseif ($is_bidang) {
    $where .= ($where? ' AND ':' WHERE ') . " id_user=".$id_user;
}

// Filter bidang (untuk super admin)
$map = [
    'sekretariat'=>['SEKRETARIAT','TU'], 'psda'=>['PSDA'],'irigasi'=>['IRIGASI'],'swp'=>['SWP'],'binfat'=>['BINFAT'],
    'upt-kediri'=>['KEDIRI'],'korwil-malang'=>['MALANG'],'korwil-surabaya'=>['SURABAYA'],'upt-bojonegoro'=>['BOJONEGORO'],
    'korwil-madiun'=>['MADIUN'],'upt-bondowoso'=>['BONDOWOSO'],'upt-lumajang'=>['LUMAJANG'],'upt-pasuruan'=>['PASURUAN'],'upt-madura'=>['MADURA']
];
if (!empty($_GET['filter_bidang'])) {
    $fk = $_GET['filter_bidang'];
    if (isset($map[$fk])) {
        $usernames = array_map('strtoupper',$map[$fk]);
        $inUser = "'".implode("','", array_map(function($u) use ($config){return mysqli_real_escape_string($config,$u);}, $usernames))."'";
        $res = mysqli_query($config, "SELECT id_user FROM tbl_user WHERE UPPER(username) IN ($inUser)");
        $ids = [];
        if ($res) { while($r=mysqli_fetch_assoc($res)) { $ids[] = (int)$r['id_user']; } }
        if (!empty($ids)) {
            $where .= ($where? ' AND ':' WHERE ') . " id_user IN (".implode(',', $ids).")";
        }
    }
}

// Pastikan kolom jenis ada lalu filter berdasarkan jenis (default umum)
$chkJenis = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar LIKE 'jenis'");
if ($chkJenis && mysqli_num_rows($chkJenis) !== 1) {
    @mysqli_query($config, "ALTER TABLE tbl_surat_keluar ADD COLUMN jenis VARCHAR(20) NOT NULL DEFAULT 'umum'");
}
$where .= ($where? ' AND ':' WHERE ') . " jenis='".mysqli_real_escape_string($config,$jenis)."'";

// Kondisi pencarian
if (isset($_GET['cari']) && $_GET['cari'] !== '') {
    $cari = mysqli_real_escape_string($config, $_GET['cari']);
    $cond = "(isi LIKE '%$cari%' OR perihal LIKE '%$cari%' OR tujuan LIKE '%$cari%' OR no_surat LIKE '%$cari%' OR kode LIKE '%$cari%' OR no_agenda LIKE '%$cari%')";
    $where .= ' AND ' . $cond;
}

// Kolom status dkk (sekali jalan jika belum ada)
$existingCols = [];
$resCols = mysqli_query($config, "SHOW COLUMNS FROM tbl_surat_keluar");
if ($resCols) { while($c=mysqli_fetch_assoc($resCols)) { $existingCols[] = $c['Field']; }}
$alter = [];
if (!in_array('status',$existingCols)) $alter[] = "ADD COLUMN status ENUM('draft','finished') NOT NULL DEFAULT 'draft'";
if (!in_array('updated_by',$existingCols)) $alter[] = "ADD COLUMN updated_by VARCHAR(50) NULL";
if (!in_array('updated_at',$existingCols)) $alter[] = "ADD COLUMN updated_at DATETIME NULL";
if (!in_array('id_arsip_berkas',$existingCols)) $alter[] = "ADD COLUMN id_arsip_berkas INT NULL, ADD INDEX idx_arsip_rel (id_arsip_berkas)";
if (!empty($alter)) { @mysqli_query($config, 'ALTER TABLE tbl_surat_keluar '.implode(', ',$alter)); }

// Ambil data
$sql = "SELECT * $base_query $where ORDER BY id_surat DESC LIMIT $curr,$limit";
$q = mysqli_query($config, $sql);

if ($q && mysqli_num_rows($q) > 0) {
    $seq = $curr + 1;
    $printedStyle = false;
    while ($row = mysqli_fetch_assoc($q)) {
        $status_raw = isset($row['status']) ? $row['status'] : (!empty($row['file']) ? 'finished' : 'draft');
        $icon_file = ($status_raw == 'finished') ? 'finished.png' : 'draft.png';
        if(!$printedStyle){
            echo '<style>.status-cell{padding:2px 0!important}.status-cell .status-wrap{display:flex;align-items:center;justify-content:center;height:50px}.status-cell img.status-icon{height:48px;max-height:48px;width:auto;position:relative;top:8px;filter:drop-shadow(0 1px 2px rgba(0,0,0,.15))}.action-round{background:#1976d2;border-radius:50%;width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.15);transition:.25s}.action-round i{line-height:44px;font-size:22px;color:#fff}.action-round.delete{background:#e64a19}.action-round.toggle{background:#2e7d32}.action-round:hover{filter:brightness(1.08)}.action-round:active{transform:scale(.92)}@media (max-width:600px){.action-round{width:40px;height:40px}.action-round i{font-size:20px;line-height:40px}.status-cell .status-wrap{height:46px}.status-cell img.status-icon{height:44px;top:6px}}</style>';
            $printedStyle = true;
        }
        echo '<tr style="vertical-align:top;">';
        echo '<td class="center-align"><strong>'.$seq.'</strong></td>';
    echo '<td>'.esc($row['isi']);
        $driveMarker = !empty($row['file_drive']) ? $row['file_drive'] : ( (!empty($row['file']) && strpos($row['file'],'gdrive:fileId=')===0) ? $row['file'] : '');
        if(!empty($row['file']) || !empty($driveMarker)){
            echo '<br/><br/><strong>File : </strong>';
            $is_operator_file = $is_operator && !empty($operator_allowed_ids) && in_array((int)$row['id_user'],$operator_allowed_ids,true);
            // Prefer full labeled name from Drive marker when available, fallback to "Berkas <label>"
            $linkText = !empty($row['file']) ? $row['file'] : '';
            if (!empty($driveMarker)) {
                $label = (isset($row['file_no']) && $row['file_no'] !== null && $row['file_no'] !== '') ? str_pad((string)$row['file_no'], 4, '0', STR_PAD_LEFT) : 'Lampiran';
                $fullName = null; if (preg_match('/\\|name=([^|]+)/', $driveMarker, $m)) { $fullName = rawurldecode($m[1]); }
                $linkText = $fullName ? $fullName : ('Berkas ' . $label);
            }
            if (in_array($level,[1,2]) || $is_operator_file) {
                echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat='.$row['id_surat'].'" target="_blank" rel="noopener" style="text-decoration:underline;">'.esc($linkText).'</a>';
            } else {
                echo '<a href="src/SuratKeluar/lihat_file_sk.php?id_surat='.$row['id_surat'].'" class="pin-trigger" data-action-type="view" data-id-surat="'.$row['id_surat'].'" style="text-decoration:underline;">'.esc($linkText).'</a>';
            }
            if (!empty($_SESSION['pinResetIds'][$row['id_surat']])) {
                echo ' <span class="new badge blue" data-badge-caption="PIN diubah" title="PIN direset oleh admin"></span>';
            }
        }
        echo '</td>';
    echo '<td class="center-align">'.esc($row['tujuan']).'<br/><small class="grey-text text-darken-1">'.esc($row['perihal']).'</small></td>';
    $tglSuratOut = !empty($row['tgl_surat']) ? indoDate($row['tgl_surat']) : '-';
    echo '<td class="center-align">'.esc($row['no_surat']).'<br/><small class="grey-text text-darken-1 nowrap">'.$tglSuratOut.'</small></td>';
    echo '<td class="center-align">'.esc($row['nama_pembuat']).'<br/><small class="grey-text text-darken-1 nowrap">'.(!empty($row['tgl_dibuat'])?date('d M Y, H:i',strtotime($row['tgl_dibuat'])):'').'</small></td>';
        echo '<td class="center-align status-cell"><div class="status-wrap"><img class="status-icon status-icon-'.$row['id_surat'].'" src="asset/img/'.$icon_file.'" alt="status"/></div></td>';

        // Tombol Aksi
        echo '<td class="center-align">';
        $can_manage = ($level==1); // super admin penuh
        $is_owner = ($row['id_user'] == $id_user);
        $is_operator_owner = $is_operator && !empty($operator_allowed_ids) && in_array((int)$row['id_user'],$operator_allowed_ids,true);
        if ($level==2) {
            echo '<div class="grey-text" style="padding-top:15px;">-</div>';
        } elseif ($can_manage || $is_owner || $is_operator_owner) {
            echo '<div class="actions-compact" style="display:flex;justify-content:center;gap:10px;padding-top:2px;flex-wrap:wrap;">';
            $is_operator_level = ($level==3);
            if($is_operator_level){
                $toggleTitle = ($status_raw=='finished') ? 'Set Draft' : 'Set Finished';
                echo '<a class="waves-effect waves-light tooltipped action-round toggle" data-position="top" data-tooltip="'.$toggleTitle.'" href="#" onclick="return toggleStatus('.(int)$row['id_surat'].', event);"><i class="material-icons">autorenew</i></a>';
            }
            if ($level==1 || $is_operator_owner) {
                echo '<a class="waves-effect waves-light tooltipped action-round" data-position="top" data-tooltip="Edit" href="?page=admin&act=tsk&sub=edit&id_surat='.(int)$row['id_surat'].'"><i class="material-icons">edit</i></a>';
                echo '<a class="waves-effect waves-light tooltipped action-round delete" data-position="top" data-tooltip="Hapus" href="?page=admin&act=tsk&sub=del&id_surat='.(int)$row['id_surat'].'" onclick="return confirm(\'Yakin ingin menghapus surat ini?\');"><i class="material-icons">delete</i></a>';
                if($is_operator_level){
                    $alreadyArsip = !empty($row['id_arsip_berkas']);
                    $archTip = $alreadyArsip ? 'Sudah diarsipkan' : 'Arsipkan';
                    $archCls = $alreadyArsip ? ' arch done' : ' arch';
                    if ($alreadyArsip) {
                        $archOnclick = 'return false;';
                    } else {
                        $sid = (int)$row['id_surat'];
                        $archOnclick = "try{if(window.openArsipModal){return openArsipModal($sid);}console && console.debug && console.debug('Memuat modul arsip...');var s=document.createElement('script');s.async=true;s.src='src/SuratKeluar/arsip_modal.js?v='+Date.now();s.onload=function(){if(window.openArsipModal){openArsipModal($sid);}else{alert('Modul arsip tidak siap. Coba ulangi.');}};s.onerror=function(){alert('Gagal memuat modul arsip.');};(document.head||document.body).appendChild(s);}catch(e){alert('Terjadi kesalahan: '+e.message);}return false;";
                    }
                    echo '<a class="waves-effect waves-light tooltipped action-round'.$archCls.'" data-position="top" data-tooltip="'.$archTip.'" href="#" data-id-surat="'.(int)$row['id_surat'].'" onclick="'.$archOnclick.'"><i class="material-icons">archive</i></a>';
                }
            } else {
                echo '<a class="waves-effect waves-light tooltipped action-round pin-trigger" data-position="top" data-tooltip="Edit (PIN)" href="?page=admin&act=tsk&sub=edit&id_surat='.(int)$row['id_surat'].'" data-action-type="edit" data-id-surat="'.(int)$row['id_surat'].'"><i class="material-icons">edit</i></a>';
                echo '<a class="waves-effect waves-light tooltipped action-round delete pin-trigger" data-position="top" data-tooltip="Hapus (PIN)" href="?page=admin&act=tsk&sub=del&id_surat='.(int)$row['id_surat'].'" data-action-type="delete" data-id-surat="'.(int)$row['id_surat'].'"><i class="material-icons">delete</i></a>';
                if($is_operator_level){
                    $alreadyArsip = !empty($row['id_arsip_berkas']);
                    $archTip = $alreadyArsip ? 'Sudah diarsipkan' : 'Arsipkan';
                    $archCls = $alreadyArsip ? ' arch done' : ' arch';
                    if ($alreadyArsip) {
                        $archOnclick = 'return false;';
                    } else {
                        $sid = (int)$row['id_surat'];
                        $archOnclick = "try{if(window.openArsipModal){return openArsipModal($sid);}console && console.debug && console.debug('Memuat modul arsip...');var s=document.createElement('script');s.async=true;s.src='src/SuratKeluar/arsip_modal.js?v='+Date.now();s.onload=function(){if(window.openArsipModal){openArsipModal($sid);}else{alert('Modul arsip tidak siap. Coba ulangi.');}};s.onerror=function(){alert('Gagal memuat modul arsip.');};(document.head||document.body).appendChild(s);}catch(e){alert('Terjadi kesalahan: '+e.message);}return false;";
                    }
                    echo '<a class="waves-effect waves-light tooltipped action-round'.$archCls.'" data-position="top" data-tooltip="'.$archTip.'" href="#" data-id-surat="'.(int)$row['id_surat'].'" onclick="'.$archOnclick.'"><i class="material-icons">archive</i></a>';
                }
            }
            echo '</div>';
        } else {
            echo '<div class="grey-text" style="padding-top:15px;">-</div>';
        }
        echo '</td>';
        echo '</tr>';
        $seq++;
    }
} else {
    echo '<tr><td colspan="7" class="center-align"><div class="card-panel grey lighten-4" style="margin:20px;">';
    if (isset($_GET['cari']) && $_GET['cari']!=='') {
    echo '<i class="material-icons large grey-text">search</i><p class="grey-text">Tidak ada data yang ditemukan untuk pencarian "<strong>'.esc($_GET['cari']).'</strong>"</p>';
    } else {
        echo '<i class="material-icons large grey-text">inbox</i><p class="grey-text">Tidak ada data untuk ditampilkan.</p>';
    }
    echo '</div></td></tr>';
}
// Inject required modal + handlers so archive button works from AJAX results (idempotent)
echo <<<'HTML'
<script>(function(){
    // Re-init tooltips for newly injected elements
    try { if (window.M) { M.Tooltip.init(document.querySelectorAll('.tooltipped')); } } catch(e){}
    // Delegated fallback: click handler for archive buttons if inline onclick is blocked
    try {
        var tbl = document.getElementById('tbl');
        if (tbl && !tbl.__arsipDelegated) {
            tbl.__arsipDelegated = true;
            tbl.addEventListener('click', function(ev){
                var t = ev.target;
                if (t && t.closest) {
                    var btn = t.closest('.action-round.arch');
                    if (btn && btn.getAttribute('onclick')) return; // let inline handle
                    if (btn && !btn.classList.contains('done')) {
                        ev.preventDefault();
                        var id = btn.getAttribute('data-id-surat');
                        try{ if(window.openArsipModal){ return openArsipModal(parseInt(id,10)); }
                            var s=document.createElement('script');
                            s.async=true; s.src='src/SuratKeluar/arsip_modal.js?v='+Date.now();
                            s.onload=function(){ if(window.openArsipModal){ openArsipModal(parseInt(id,10)); } };
                            s.onerror=function(){ alert('Gagal memuat modul arsip.'); };
                            (document.head||document.body).appendChild(s);
                        }catch(e){ alert('Terjadi kesalahan: '+e.message); }
                    }
                }
            });
        }
    } catch(e){}
    // Ensure Arsip modal exists once, appended to body (not inside table)
    if (!document.getElementById('arsipModal')) {
        var wrap = document.createElement('div');
        wrap.innerHTML = `
            <div id="arsipModal" class="modal">
                <div class="modal-content">
                    <h5>Pilih Berkas Arsip</h5>
                    <p class="grey-text">Silakan pilih berkas arsip tujuan. Daftar diambil dari Arsip Berkas Bidang Anda.</p>
                    <ul class="collection" id="arsipList"><li class="collection-item">Memuat...</li></ul>
                </div>
                <div class="modal-footer">
                    <a href="#" class="modal-close waves-effect waves-green btn-flat">Tutup</a>
                </div>
            </div>`;
        // Append the actual modal element (first child) to body
        document.body.appendChild(wrap.firstElementChild);
        try { if (window.M) { M.Modal.init(document.querySelectorAll('#arsipModal')); } } catch(e){}
    }
    // Define handlers once
    if (typeof window.openArsipModal !== 'function') {
        window.__arsipTargetSurat = null;
        window.openArsipModal = function(idSurat){
            window.__arsipTargetSurat = idSurat;
            var modalEl = document.getElementById('arsipModal');
            var listEl = document.getElementById('arsipList');
            if (listEl) { listEl.innerHTML = '<li class="collection-item">Memuat...</li>'; }
            // fetch list of berkas
            fetch('src/SuratKeluar/arsip_list_ajax.php')
                .then(function(r){ return r.json(); })
                .then(function(list){
                    if (!listEl) return;
                    listEl.innerHTML = '';
                    if (!list || !list.length) {
                        listEl.innerHTML = '<li class="collection-item">Belum ada berkas. Tambahkan di menu Arsip Berkas Bidang.</li>';
                    } else {
                        list.forEach(function(it){
                            var li = document.createElement('li');
                            li.className = 'collection-item';
                            var kode = (it.kode_klasifikasi||'').toString();
                            var nama = (it.nama_berkas||'').toString();
                            var uraian = (it.uraian||'').toString();
                            li.innerHTML = '<div><strong>'+kode+'</strong> - '+nama+'<a href="#" class="secondary-content" data-id="'+it.id+'">Pilih</a><br/><small>'+uraian+'</small></div>';
                            li.querySelector('a').addEventListener('click', function(ev){ ev.preventDefault(); window.pilihArsip(it.id); });
                            listEl.appendChild(li);
                        });
                    }
                })
                .catch(function(){ if (listEl) listEl.innerHTML = '<li class="collection-item red-text">Gagal memuat daftar berkas.</li>'; });
            try {
                if (window.M) {
                    var inst = M.Modal.getInstance(modalEl) || M.Modal.init(modalEl);
                    inst.open();
                } else {
                    // fallback: show element
                    modalEl.style.display = 'block';
                }
            } catch(e){}
            return false;
        };
        window.pilihArsip = function(idArsip){
            if (!window.__arsipTargetSurat) return false;
            var fd = new FormData();
            fd.append('__ajax','arsip_surat');
            fd.append('id_surat', String(window.__arsipTargetSurat));
            fd.append('id_arsip_berkas', String(idArsip));
            fetch(window.location.href, { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if (res && res.ok) {
                        try {
                            var modalEl = document.getElementById('arsipModal');
                            if (window.M) { var inst = M.Modal.getInstance(modalEl) || M.Modal.init(modalEl); inst.close(); }
                        } catch(e){}
                        // mark the corresponding archive buttons as done
                        var selector = '.action-round.arch[onclick="return openArsipModal('+window.__arsipTargetSurat+');"]';
                        document.querySelectorAll(selector).forEach(function(btn){
                            btn.classList.add('done');
                            btn.setAttribute('onclick','return false;');
                            if (btn.classList.contains('tooltipped')) {
                                try {
                                    var tt = window.M && M.Tooltip.getInstance(btn);
                                    if (tt) tt.destroy();
                                } catch(e){}
                                btn.setAttribute('data-tooltip','Sudah diarsipkan');
                                try { if (window.M) { M.Tooltip.init(btn); } } catch(e){}
                            }
                        });
                    } else {
                        alert((res && res.error) ? res.error : 'Gagal mengarsipkan surat.');
                    }
                })
                .catch(function(){ alert('Terjadi kesalahan saat menyimpan data.'); });
            return false;
        };
    }
})();</script>
HTML;
exit;
?>

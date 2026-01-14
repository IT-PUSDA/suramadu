<?php
// Form tambah Surat Keluar khusus Nota Dinas (UI sama seperti umum)
if (empty($_SESSION['admin'])) {
    $_SESSION['err'] = '<center>Anda harus login terlebih dahulu!</center>';
    header("Location: index.php");
    die();
}
?>

<div class="row">
    <div class="col s12">
        <nav class="secondary-nav">
            <div class="nav-wrapper blue-grey darken-1">
                <ul class="left">
                    <li class="waves-effect waves-light"><a href="index.php?page=admin&act=tsk_nd&sub=add_nota_dinas" class="judul"><i class="material-icons">drafts</i> Input Surat Keluar - Nota Dinas</a></li>
                </ul>
            </div>
        </nav>
    </div>
</div>

<?php
// Notifikasi error umum
foreach (['errQ', 'errEmpty'] as $k) {
    if (isset($_SESSION[$k])) {
        echo '<div id="alert-message" class="row"><div class="col m12"><div class="card red lighten-5"><div class="card-content notif"><span class="card-title red-text"><i class="material-icons md-36">clear</i> ' . $_SESSION[$k] . '</span></div></div></div></div>';
        unset($_SESSION[$k]);
    }
}
?>

<div class="row jarak-form">
    <form class="col s12" method="POST" action="index.php?page=admin&act=tsk_nd&sub=proses_tambah_nota_dinas" enctype="multipart/form-data">
        <div class="row">
            <div class="input-field col s6">
                <i class="material-icons prefix md-prefix">date_range</i>
                <input id="tgl_surat" type="text" name="tgl_surat" class="datepicker" required>
                <?php if (isset($_SESSION['tgl_suratk'])) {
                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' . $_SESSION['tgl_suratk'] . '</div>';
                    unset($_SESSION['tgl_suratk']);
                } ?>
                <label for="tgl_surat">Tanggal Surat</label>
            </div>
            <div class="input-field col s6 tooltipped" data-position="top" data-tooltip="Diambil dari data referensi kode klasifikasi" style="position:relative;">
                <i class="material-icons prefix md-prefix">bookmark</i>
                <input id="kode" type="text" class="validate" name="kode" autocomplete="off" required>
                <?php if (isset($_SESSION['kodek'])) {
                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' . $_SESSION['kodek'] . '</div>';
                    unset($_SESSION['kodek']);
                } ?>
                <label for="kode">Kode Klasifikasi</label>
                <div id="kode-suggest" class="collection" style="position:absolute; z-index:1000; display:none; max-height:260px; overflow:auto; left:44px; right:0; background:#fff;"></div>
            </div>
            <div class="input-field col s6">
                <i class="material-icons prefix md-prefix">featured_play_list</i>
                <input id="perihal" type="text" class="validate" name="perihal" required>
                <?php if (isset($_SESSION['perihalk'])) {
                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' . $_SESSION['perihalk'] . '</div>';
                    unset($_SESSION['perihalk']);
                } ?>
                <label for="perihal">Perihal</label>
            </div>
            <div class="input-field col s6">
                <i class="material-icons prefix md-prefix">place</i>
                <input id="tujuan" type="text" class="validate" name="tujuan" required>
                <?php if (isset($_SESSION['tujuan_surat'])) {
                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' . $_SESSION['tujuan_surat'] . '</div>';
                    unset($_SESSION['tujuan_surat']);
                } ?>
                <label for="tujuan">Tujuan Surat</label>
            </div>
            <div class="input-field col s6">
                <i class="material-icons prefix md-prefix">person</i>
                <input id="nama_pembuat" type="text" class="validate" name="nama_pembuat" required>
                <label for="nama_pembuat">Nama Pembuat</label>
            </div>
            <div class="input-field col s6">
                <i class="material-icons prefix md-prefix">lock</i>
                <input id="pin" type="tel" class="validate" name="pin" required inputmode="numeric" maxlength="6" pattern="[0-9]{6}" title="Masukkan tepat 6 digit angka" oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)">
                <label for="pin">PIN Surat</label>
                <span class="helper-text grey-text" style="font-size: 0.9em; margin-left: 44px;">Masukkan 6 digit angka</span>
                <?php if (isset($_SESSION['pink'])) {
                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' . $_SESSION['pink'] . '</div>';
                    unset($_SESSION['pink']);
                } ?>
            </div>

            <div class="input-field col s6">
                <i class="material-icons prefix md-prefix">description</i>
                <textarea id="isi" class="materialize-textarea validate" name="isi" required></textarea>
                <label for="isi">Isi Ringkas</label>
                <span class="helper-text grey-text" style="font-size: 0.9em; margin-left: 44px;">Isi ringkas minimal satu kalimat</span>
                <?php if (isset($_SESSION['isik'])) {
                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' . $_SESSION['isik'] . '</div>';
                    unset($_SESSION['isik']);
                } ?>
                <div style="margin-top:18px;"></div>
                <div class="file-field input-field tooltipped" data-position="top" data-tooltip="Jika tidak ada file/scan gambar surat, biarkan kosong">
                    <div class="btn light-green darken-1">
                        <span>File</span>
                        <input type="file" id="file" name="file" accept=".pdf" required>
                    </div>
                    <div class="file-path-wrapper">
                        <input class="file-path validate" type="text" placeholder="Upload file/dokumen yang sesuai">
                        <?php if (isset($_SESSION['errSize'])) {
                            echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' . $_SESSION['errSize'] . '</div>';
                            unset($_SESSION['errSize']);
                        }
                        if (isset($_SESSION['errFormat'])) {
                            echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' . $_SESSION['errFormat'] . '</div>';
                            unset($_SESSION['errFormat']);
                        } ?>
                        <small class="red-text">*Format file yang diperbolehkan hanya *.PDF dan ukuran maksimal file 2 MB!</small>
                    </div>
                </div>
            </div>
            <div class="input-field col s6">
                <i class="material-icons prefix md-prefix">low_priority</i><label>Bidang</label><br />
                <div class="input-field col s11 right">
                    <?php require_once __DIR__ . '/../include/bidang_mapping.php'; $lockCode = (in_array((int)$_SESSION['admin'],[3,4],true) ? resolve_bidang_code_from_session() : null); print_bidang_selector($lockCode); ?>
                </div>
                <?php if (isset($_SESSION['bidangk'])) {
                    echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">' . $_SESSION['bidangk'] . '</div>';
                    unset($_SESSION['bidangk']);
                } ?>
            </div>
        </div>

        <div class="row">
            <div class="col s12">
                <button type="submit" name="submit1" class="btn-large blue waves-effect waves-light" style="margin-right: 1rem;">LANJUT <i class="material-icons">done</i></button>
                <a href="index.php?page=admin&act=tsk_nd" class="btn-large deep-orange waves-effect waves-light">BATAL <i class="material-icons">clear</i></a>
            </div>
        </div>
    </form>
</div>
<style>
    #kode-suggest .collection-item.active { background-color: #e3f2fd !important; color: #212121 !important; }
    #kode-suggest .collection-item.active .blue-text { color: #1565c0 !important; }
</style>
<script>
(function(){
    const input = document.getElementById('kode');
    if(!input) return;
    const box = document.getElementById('kode-suggest');
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
    function pick(i){ if(!items[i]) return; input.value = items[i].getAttribute('data-kode'); hide(); input.focus(); }
    let t;
    function query(q){
        const root = window.location.pathname.replace(/\/index\.php.*$/,'').replace(/\/$/,'');
        const url = (root || '') + '/src/Utils/klasifikasi_search.php?term='+encodeURIComponent(q||'');
        fetch(url, {credentials:'same-origin'})
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
})();
</script>
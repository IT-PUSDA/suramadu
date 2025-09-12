(function(){
  if (window.__arsipModalLoaded) return; // idempotent
  window.__arsipModalLoaded = true;

  function ensureModal(){
    var el = document.getElementById('arsipModal');
    if (el) return el;
    var wrap = document.createElement('div');
    wrap.innerHTML = [
      '<div id="arsipModal" class="modal">',
      '  <div class="modal-content">',
      '    <h5>Pilih Berkas Arsip</h5>',
      '    <p class="grey-text">Silakan pilih berkas arsip tujuan. Daftar diambil dari Arsip Berkas Bidang Anda.</p>',
      '    <ul class="collection" id="arsipList"><li class="collection-item">Memuat...</li></ul>',
      '  </div>',
      '  <div class="modal-footer">',
      '    <a href="#" class="modal-close waves-effect waves-green btn-flat">Tutup</a>',
      '  </div>',
      '</div>'
    ].join('');
    document.body.appendChild(wrap.firstElementChild);
    try { if (window.M) { M.Modal.init(document.querySelectorAll('#arsipModal')); } } catch(e){}
    return document.getElementById('arsipModal');
  }

  function reInitTooltips(){
    try {
      if (window.jQuery && jQuery.fn.tooltip) {
        jQuery('.tooltipped').tooltip({ delay: 10 });
      } else if (window.M && M.Tooltip) {
        M.Tooltip.init(document.querySelectorAll('.tooltipped'));
      }
    } catch(e){}
  }

  window.openArsipModal = function(idSurat){
    window.__arsipTargetSurat = idSurat;
    var modalEl = ensureModal();
    var listEl = document.getElementById('arsipList');
    if (listEl) listEl.innerHTML = '<li class="collection-item">Memuat...</li>';

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
      if (window.jQuery && jQuery.fn.openModal) {
        jQuery('#arsipModal').openModal();
      } else if (window.M && M.Modal) {
        var inst = M.Modal.getInstance(modalEl) || M.Modal.init(modalEl);
        inst.open();
      } else {
        modalEl.style.display = 'block';
      }
    } catch(e){}
    return false;
  };

  window.pilihArsip = function(idArsip){
    if (!window.__arsipTargetSurat) return false;
    var fd = new FormData();
    fd.append('id_surat', String(window.__arsipTargetSurat));
    fd.append('id_arsip_berkas', String(idArsip));
    fetch('src/SuratKeluar/arsip_assign_ajax.php', { method: 'POST', body: fd })
      .then(function(r){
        return r.text().then(function(text){ return { status:r.status, ok:r.ok, text:text }; });
      })
      .then(function(resp){
        var res = null;
        try { res = resp.text ? JSON.parse(resp.text) : null; } catch(e) {}
        if (resp.ok && res && res.ok) {
          try {
            var modalEl = document.getElementById('arsipModal');
            if (window.jQuery && jQuery.fn.closeModal) {
              jQuery('#arsipModal').closeModal();
            } else if (window.M && M.Modal) {
              var inst = M.Modal.getInstance(modalEl) || M.Modal.init(modalEl);
              inst.close();
            } else {
              modalEl.style.display = 'none';
            }
          } catch(e){}
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
              reInitTooltips();
            }
          });
        } else {
          var msg = (res && res.error) ? res.error : ('HTTP '+resp.status+'\n'+(resp.text||''));
          alert(msg || 'Gagal mengarsipkan surat.');
        }
      })
      .catch(function(){ alert('Terjadi kesalahan saat menyimpan data.'); });
    return false;
  };

  reInitTooltips();
})();

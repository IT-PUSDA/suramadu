(function(){
  if (window.__arsipModalLoaded) return; // idempotent
  window.__arsipModalLoaded = true;

  // Utility: throttle for input handling
  function throttle(fn, wait){
    var t, last = 0;
    return function(){
      var now = Date.now(), args = arguments, ctx = this;
      clearTimeout(t);
      var remain = Math.max(0, wait - (now - last));
      t = setTimeout(function(){ last = Date.now(); fn.apply(ctx, args); }, remain);
    };
  }

  function ensureModal(){
    var el = document.getElementById('arsipModal');
    // If legacy modal exists (without our class), remove it to avoid stale UI
    if (el && !el.classList.contains('archives-modal')) {
      try { el.parentNode && el.parentNode.removeChild(el); } catch(_) {}
      el = null;
    }
    if (el) return el;
    var wrap = document.createElement('div');
    wrap.innerHTML = [
      '<div id="arsipModal" class="modal archives-modal">',
      '  <div class="modal-content">',
      '    <div class="am-header">',
      '      <i class="material-icons am-icon">folder_open</i>',
      '      <div class="am-title">',
      '        <h5>Pilih Berkas Arsip</h5>',
      '        <p class="grey-text">Pilih berkas arsip tujuan. Cari berdasarkan nama, kode, atau uraian.</p>',
      '      </div>',
      '    </div>',
  // search removed: dropdown only
      '    <div class="am-select-wrap">',
      '      <select id="amSelect" class="browser-default am-select">',
      '        <option value="" disabled selected>Memuat daftar berkas...</option>',
      '      </select>',
      '    </div>',
      '  </div>',
      '  <div class="modal-footer am-footer">',
      '    <a href="#" class="modal-close waves-effect btn-flat">Tutup</a>',
      '    <a href="#" id="amChoose" class="waves-effect waves-light btn disabled"><i class="material-icons left">check_circle</i>Pilih</a>',
      '  </div>',
      '</div>'
    ].join('');
  document.body.appendChild(wrap.firstElementChild);
  // initialize tooltips if available (for clear button)
  try { if (window.M && M.Tooltip) { M.Tooltip.init(document.querySelectorAll('.tooltipped')); } } catch(e){}

    // Attach handlers after in-DOM
    var modal = document.getElementById('arsipModal');
  var searchInput = null; // removed
  var clearBtn = null; // removed
  var chooseBtn = modal.querySelector('#amChoose');
  var selectEl = modal.querySelector('#amSelect');
    // Dynamic height adjuster to avoid bottom empty space
    function adjustModalHeight(){
      try {
        var mc = modal.querySelector('.modal-content');
        var mf = modal.querySelector('.modal-footer');
        var contentH = (mc ? mc.scrollHeight : 0) + (mf ? mf.offsetHeight : 0);
        var maxH = Math.max(260, Math.min(contentH, window.innerHeight - Math.round(window.innerHeight * 0.12)));
        modal.style.maxHeight = maxH + 'px';
        modal.style.height = maxH + 'px';
      } catch(e){}
    }

    // state
    modal.__state = { items: [], filtered: [], selectedId: null };

    function render(list){
      if (!selectEl) return;
      if (!list || !list.length) {
        selectEl.innerHTML = '<option value="" disabled selected>Tidak ada berkas arsip</option>';
        selectEl.disabled = true;
        return;
      }
      var opts = ['<option value="" disabled selected>Pilih berkas arsip…</option>'];
      list.forEach(function(it){
        var kode = (it.kode_klasifikasi||'').toString();
        var nama = (it.nama_berkas||'').toString();
        var uraian = (it.uraian||'').toString();
        var label = escapeHtml(kode + ' • ' + nama + (uraian?(' — ' + uraian):''));
        opts.push('<option value="'+String(it.id)+'" data-kode="'+escapeHtml(kode)+'" data-nama="'+escapeHtml(nama)+'" data-uraian="'+escapeHtml(uraian)+'">'+label+'</option>');
      });
      selectEl.disabled = false;
      selectEl.innerHTML = opts.join('');
    }

    function setSelected(id){ modal.__state.selectedId = id; if (chooseBtn) chooseBtn.classList.toggle('disabled', !id); }

    if (chooseBtn) chooseBtn.addEventListener('click', function(e){
      e.preventDefault();
      if (!modal.__state.selectedId) return;
      window.pilihArsip(modal.__state.selectedId);
    });

    if (clearBtn) clearBtn.addEventListener('click', function(){
      if (searchInput) { searchInput.value=''; filter(''); searchInput.focus(); }
    });

    // filter removed

    if (selectEl) selectEl.addEventListener('change', function(){ setSelected(this.value || null); });

    // Keyboard navigation (arrows/enter/esc)
    modal.addEventListener('keydown', function(e){
      var cards = Array.prototype.slice.call(modal.querySelectorAll('.am-card'));
      if (!cards.length) return;
      var idx = Math.max(0, cards.findIndex(function(c){ return c.classList.contains('selected'); }));
      if (e.key === 'ArrowDown' || e.key === 'ArrowRight') { e.preventDefault(); setSelected(cards[Math.min(cards.length-1, idx+1)].getAttribute('data-id')); }
      if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') { e.preventDefault(); setSelected(cards[Math.max(0, idx-1)].getAttribute('data-id')); }
      if (e.key === 'Enter') { e.preventDefault(); if (modal.__state.selectedId) window.pilihArsip(modal.__state.selectedId); }
      if (e.key === 'Escape') { try{ if (window.M && M.Modal){ (M.Modal.getInstance(modal)||M.Modal.init(modal)).close(); } }catch(_){} }
    });

    // expose helpers on element
  modal.__setData = function(list){ modal.__state.items = list||[]; modal.__state.filtered = list||[]; render(list||[]); adjustModalHeight(); };
  modal.__selectFirst = function(){ if (selectEl) { var v = selectEl.options && selectEl.options.length > 1 ? selectEl.options[1].value : ''; if (v) { selectEl.value = v; setSelected(v); } } adjustModalHeight(); };

  // Resize on window change
  window.addEventListener('resize', throttle(adjustModalHeight, 100));

    // materialize init
  try { if (window.M) { M.Modal.init(document.querySelectorAll('#arsipModal'), { endingTop: '8%', inDuration: 140, outDuration: 110, preventScrolling:true, dismissible: true, opacity: 0.4, onOpenEnd: adjustModalHeight, onCloseEnd: function(){ modal.style.height=''; modal.style.maxHeight=''; } }); } } catch(e){}
    return modal;
  }

  function escapeHtml(str){
    return String(str||'').replace(/[&<>"]+/g,function(ch){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[ch]);});
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
    if (listEl) listEl.innerHTML = listEl.innerHTML; // keep skeletons

  // set loading state on select
  var sel = modalEl.querySelector('#amSelect');
  if (sel){ sel.disabled = true; sel.innerHTML = '<option value="" selected>Memuat daftar berkas…</option>'; }
  fetch('src/SuratKeluar/arsip_list_ajax.php')
      .then(function(r){ return r.json(); })
      .then(function(list){
        if (!modalEl) return;
        modalEl.__setData(Array.isArray(list)?list:[]);
        modalEl.__selectFirst();
      })
  .catch(function(){ if (sel) { sel.disabled = true; sel.innerHTML = '<option value="" selected>Gagal memuat daftar berkas</option>'; } });

    try {
      if (window.jQuery && jQuery.fn.openModal) {
        jQuery('#arsipModal').openModal();
      } else if (window.M && M.Modal) {
        var inst = M.Modal.getInstance(modalEl) || M.Modal.init(modalEl);
        inst.open();
      } else {
        modalEl.style.display = 'block';
      }
  setTimeout(function(){ var s=modalEl.querySelector('#amSelect'); if (s) s.focus(); adjustModalHeight(); }, 120);
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
          var selector = '.action-round.arch[onclick="return openArsipModal('+window.__arsipTargetSurat+');"],'+
                          '.action-round.arch[data-id-surat="'+window.__arsipTargetSurat+'"]';
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

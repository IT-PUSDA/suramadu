<?php
// Pengecekan session sudah dilakukan di admin.php.
?>
<noscript>
    <meta http-equiv="refresh" content="0;URL='enable-javascript.html'" />
</noscript>

<!-- Footer START -->
<footer class="page-footer">
    <div class="container">
        <div class="row">
            <br />
        </div>
    </div>
    <div class="footer-copyright blue darken-1 white-text">
        <div class="container blue darken-1" id="footer">
            <?php
            $query = mysqli_query($config, "SELECT * FROM tbl_instansi");
            while ($data = mysqli_fetch_array($query)) {
            ?>
                <span class="white-text">&copy; <?php echo date("Y"); ?>
                    <?php
                    if (!empty($data['nama'])) {
                        echo $data['nama']/* .' &nbsp;|&nbsp; <a class="white-text" href="http://dpuair.jatimprov.go.id" target="_blank">By Ivan Agustoni</a>'*/;
                    } else {
                        echo 'Instansi';
                    }
                    ?>
                    </a>
                </span>
                <div class="right hide-on-small-only">
                <?php
                if (!empty($data['website'])) {
                    echo '<i class="material-icons md-12">public</i> ' . substr($data['website'], 0, 50) . ' &nbsp;&nbsp;';
                } else {
                    echo '<i class="material-icons md-12">public</i> dpuair.jatimprov.go.id &nbsp;&nbsp;';
                }
                if (!empty($data['email'])) {
                    echo '<i class="material-icons">mail_outline</i> ' . $data['email'] . '';
                } else {
                    echo '<i class="material-icons">mail_outline</i>  pusda@jatimprov.go.id';
                }
            }
                ?>
                </div>
        </div>
    </div>
</footer>
<!-- Footer END -->

<!-- Global styles for copy bubble notifications -->
<style>
    .copy-bubble {
        position: absolute;
        transform: translate(-50%, 0);
        background: #323232;
        color: #fff;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        opacity: 1;
        z-index: 99999;
        pointer-events: none;
        transition: transform 350ms ease, opacity 350ms ease;
        box-shadow: 0 6px 18px rgba(0,0,0,0.24);
        white-space: nowrap;
    }
</style>

<!-- Javascript START -->
<script type="text/javascript" src="asset/js/jquery-2.1.1.min.js"></script>
<script type="text/javascript" src="asset/js/materialize.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha512-uto9mlQzrs59VwILcLiRYeLKPPbS/bT71da/OEBYEwcdNUk8jYIy+D176RYoop1Da+f9mvkYrmj5MCLZWEtQuA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script type="text/javascript" src="asset/js/bootstrap.min.js"></script>
<script data-pace-options='{ "ajax": false }' src='asset/js/pace.min.js'></script>
<script type="text/javascript" src="src/SuratKeluar/arsip_modal.js"></script>
<script type="text/javascript" src="asset/js/copy-no-surat.js?v=20251211"></script>
<script type="text/javascript">
    //jquery dropdown
    $(".dropdown-button").dropdown({
        hover: false
    });

    //jquery sidenav on mobile
    $('.button-collapse').sideNav({
        menuWidth: 240,
        edge: 'left',
        closeOnClick: true
    });

    //jquery datepicker
    $('#tgl_surat, #batas_waktu, #dari_tanggal, #sampai_tanggal').datepicker({
        dateFormat: "yy-mm-dd",
        regional: "id"
    });

    //jquery teaxtarea
    $('#isi_ringkas').val('');
    $('#isi_ringkas').trigger('autoresize');

    //jquery dropdown select dan tooltip
    $(document).ready(function() {
        $('select').material_select();
        $('.tooltipped').tooltip({
            delay: 10
        });
    });

    //jquery autocomplete
    $(function() {
        $("#kode").autocomplete({
            source: 'index.php?page=kode'
        });
    });

    //jquery untuk menampilkan pemberitahuan
    $("#alert-message").alert().delay(5000).fadeOut('slow');

    //jquery modal
    $(document).ready(function() {
        $('.modal-trigger').leanModal();
    });

    // Global delegated click for Archive button (works for AJAX and CSP)
    (function(){
        var bound = false;
        function bindArchiveDelegation(){
            if (bound || !window.jQuery) return; bound = true;
            $(document).on('click', '.action-round.arch', function(e){
                var $btn = $(this);
                if ($btn.hasClass('done')) { e.preventDefault(); return false; }
                // if inline onclick is present, let it handle it; fallback only if not defined
                var id = $btn.attr('data-id-surat');
                if (!id) { return; }
                e.preventDefault();
                try {
                    if (window.openArsipModal) { return openArsipModal(parseInt(id,10)); }
                    var s = document.createElement('script');
                    s.async = true;
                    s.src = 'src/SuratKeluar/arsip_modal.js?v=' + Date.now();
                    s.onload = function(){ if (window.openArsipModal) { openArsipModal(parseInt(id,10)); } else { alert('Modul arsip tidak siap. Coba ulangi.'); } };
                    s.onerror = function(){ alert('Gagal memuat modul arsip.'); };
                    (document.head||document.body).appendChild(s);
                } catch(err){ alert('Terjadi kesalahan: ' + (err && err.message ? err.message : err)); }
                return false;
            });
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', bindArchiveDelegation); } else { bindArchiveDelegation(); }
    })();
</script>
<!-- Javascript END -->
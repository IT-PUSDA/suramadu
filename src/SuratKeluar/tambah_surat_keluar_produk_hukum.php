<?php
if (empty($_SESSION['admin'])) { $_SESSION['err']='<center>Anda harus login terlebih dahulu!</center>'; header('Location: index.php'); die(); }
?>

<div class="row">
  <div class="col s12">
    <nav class="secondary-nav">
      <div class="nav-wrapper blue-grey darken-1">
        <ul class="left">
          <li class="waves-effect waves-light"><a href="index.php?page=admin&act=tsk_ph&sub=add_produk_hukum" class="judul"><i class="material-icons">drafts</i> Input Surat Keluar - Produk Hukum</a></li>
        </ul>
      </div>
    </nav>
  </div>
</div>

<?php foreach (['errQ','errEmpty'] as $k) { if (isset($_SESSION[$k])) { echo '<div id="alert-message" class="row"><div class="col m12"><div class="card red lighten-5"><div class="card-content notif"><span class="card-title red-text"><i class="material-icons md-36">clear</i> '. $_SESSION[$k] .'</span></div></div></div></div>'; unset($_SESSION[$k]); }} ?>

<div class="row jarak-form">
  <form class="col s12" method="POST" action="index.php?page=admin&act=tsk_ph&sub=proses_tambah_produk_hukum" enctype="multipart/form-data">
    <div class="row">
      <div class="input-field col s6">
        <i class="material-icons prefix md-prefix">date_range</i>
        <input id="tgl_surat" type="text" name="tgl_surat" class="datepicker" required>
        <?php if (isset($_SESSION['tgl_suratk'])) { echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$_SESSION['tgl_suratk'].'</div>'; unset($_SESSION['tgl_suratk']); } ?>
        <label for="tgl_surat">Tanggal Surat</label>
      </div>
      <div class="input-field col s6 tooltipped" data-position="top" data-tooltip="Diambil dari data referensi kode klasifikasi">
        <i class="material-icons prefix md-prefix">bookmark</i>
        <input id="kode" type="text" class="validate" name="kode" required>
        <?php if (isset($_SESSION['kodek'])) { echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$_SESSION['kodek'].'</div>'; unset($_SESSION['kodek']); } ?>
        <label for="kode">Kode Klasifikasi</label>
      </div>
      <div class="input-field col s6">
        <i class="material-icons prefix md-prefix">featured_play_list</i>
        <input id="perihal" type="text" class="validate" name="perihal" required>
        <?php if (isset($_SESSION['perihalk'])) { echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$_SESSION['perihalk'].'</div>'; unset($_SESSION['perihalk']); } ?>
        <label for="perihal">Perihal</label>
      </div>
      <div class="input-field col s6">
        <i class="material-icons prefix md-prefix">place</i>
        <input id="tujuan" type="text" class="validate" name="tujuan" required>
        <?php if (isset($_SESSION['tujuan_surat'])) { echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$_SESSION['tujuan_surat'].'</div>'; unset($_SESSION['tujuan_surat']); } ?>
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
        <?php if (isset($_SESSION['pink'])) { echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$_SESSION['pink'].'</div>'; unset($_SESSION['pink']); } ?>
      </div>

      <div class="input-field col s6">
        <i class="material-icons prefix md-prefix">description</i>
        <textarea id="isi" class="materialize-textarea validate" name="isi" required></textarea>
        <label for="isi">Isi Ringkas</label>
        <span class="helper-text grey-text" style="font-size: 0.9em; margin-left: 44px;">Isi ringkas minimal satu kalimat</span>
        <?php if (isset($_SESSION['isik'])) { echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$_SESSION['isik'].'</div>'; unset($_SESSION['isik']); } ?>
        <div style="margin-top:18px;"></div>
        <div class="file-field input-field tooltipped" data-position="top" data-tooltip="Jika tidak ada file/scan gambar surat, biarkan kosong">
          <div class="btn light-green darken-1">
            <span>File</span>
            <input type="file" id="file" name="file" accept=".pdf" required>
          </div>
          <div class="file-path-wrapper">
            <input class="file-path validate" type="text" placeholder="Upload file/dokumen yang sesuai">
            <?php if (isset($_SESSION['errSize'])) { echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$_SESSION['errSize'].'</div>'; unset($_SESSION['errSize']); }
                if (isset($_SESSION['errFormat'])) { echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$_SESSION['errFormat'].'</div>'; unset($_SESSION['errFormat']); } ?>
            <small class="red-text">*Format file yang diperbolehkan hanya *.PDF dan ukuran maksimal file 2 MB!</small>
          </div>
        </div>
      </div>
      <div class="input-field col s6">
        <i class="material-icons prefix md-prefix">low_priority</i><label>Bidang</label><br />
        <div class="input-field col s11 right">
          <select name="bidang" id="bidang" required>
            <option value="104.1">Sekretariat</option>
            <option value="104.2">PSDA</option>
            <option value="104.3">Irigasi</option>
            <option value="104.4">SWP</option>
            <option value="104.5">Binfat</option>
            <option value="104.6">UPT Kediri</option>
            <option value="104.7">Korwil Malang</option>
            <option value="104.8">Korwil Surabaya</option>
            <option value="104.9">UPT Bojonegoro</option>
            <option value="104.10">Korwil Madiun</option>
            <option value="104.11">UPT Bondowoso</option>
            <option value="104.12">UPT Lumajang</option>
            <option value="104.13">UPT Pasuruan</option>
            <option value="104.14">UPT Madura</option>
          </select>
        </div>
        <?php if (isset($_SESSION['bidangk'])) { echo '<div id="alert-message" class="callout bottom z-depth-1 red lighten-4 red-text">'.$_SESSION['bidangk'].'</div>'; unset($_SESSION['bidangk']); } ?>
      </div>
    </div>

    <div class="row">
      <div class="col s12">
        <button type="submit" name="submit1" class="btn-large blue waves-effect waves-light" style="margin-right: 1rem;">LANJUT <i class="material-icons">done</i></button>
  <a href="index.php?page=admin&act=tsk_ph" class="btn-large deep-orange waves-effect waves-light">BATAL <i class="material-icons">clear</i></a>
      </div>
    </div>
  </form>
</div>


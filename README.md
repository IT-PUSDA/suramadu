# Aplikasi sederhana manajemen surat dengan PHP MySQLi

Aplikasi ini untuk mengelola pencatatan surat masuk dan surat keluar (disposisi). Dilengkapi beberapa fitur, antara lain :

-   Cetak disposisi surat masuk
-   Cetak agenda surat masuk dan keluar berdasarkan tanggal tertentu
-   Upload lampiran file surat, baik file scan/gambar(.JPG, .PNG) serta file dokumen (.DOC, .DOCX dan .PDF)
-   Fitur galeri file lampiran yang diupload
-   Upload kode klasifikasi surat format \*.CSV (file excel)
-   Multilevel user
-   Fitur backup dan restore database

Aplikasi ini dibuat dengan bahasa pemrograman <a href="http://php.net/" target="_blank">PHP</a> dan database <a href="https://en.wikipedia.org/wiki/MySQLi" target="_blank">MySQLi</a> dengan style <a href="https://en.wikipedia.org/wiki/Procedural_programming" target="_blank">prosedural</a>. Sedangkan cssnya menggunakan <a href="http://materializecss.com/" target="_blank">Materializecss</a> dan <a href="https://www.google.com/design/icons/" target="_blank">Google Material Icons</a>.

Untuk menggunakan aplikasi ini silakan lakukan beberapa konfigurasi terlebih dahulu.

-   Konfigurasi database sistem: buka folder **include** -> **config.php** lalu setting databasenya.
-   Konfigurasi kode klasifikasi surat: buka file **kode.php** lalu setting databasenya.
-   Konfigurasi fitur backup database: buka file **backup.php** lalu setting databasenya.
-   Konfigurasi fitur restore database: buka file **restore.php** lalu setting databasenya.

Untuk tampilan terbaik, gunakan browser Google Chrome versi terbaru.

Inspired by Nur Akhwam.

---

## Menjalankan Proyek Secara Lokal

Karena proyek ini sekarang mengikuti standar _front controller_ dengan _public directory_, Anda perlu menjalankan server PHP bawaan dari direktori root proyek dan menunjuk ke folder `public` sebagai _document root_.

Gunakan perintah berikut di terminal dari direktori root proyek:

```bash
php -S localhost:8000
```

Setelah itu, buka browser dan akses `http://localhost:8000`.

---

## Unused Files

Berikut adalah daftar file yang tidak digunakan dan dapat dihapus dengan aman:

```
./admin.php.bak
./tambah_surat_keluar1 - Copy.php
./hapus_teruskan.php.bak
./cetak_terusan.php.bak
./disposisi.php.bak
./edit_teruskan.php.bak
./include/config.php.bak
./include/menu.php.bak
./include/footer.php.bak
./index.php.bak
./gcal/GoogleCalendarApi.class.php.bak
./gcal/google.php.bak
./gcal/google_calendar_event_sync.php.bak
./gcal/config.php.bak
./gcal/dbConfig.php.bak
./gcal/addEvent.php.bak
./tambah_nota_dinas - Copy.php
./edit_surat_keluar.php.bak
./transaksi_surat_keluar.php.bak
./edit_nota_dinas.php.bak
./transaksi_surat_masuk.php.bak
./cetak_disposisi - Copy.php
./tambah_disposisi.php.bak
./teruskan.php.bak
./tambah_surat_keluar.php.bak
./transaksi_nota_dinas.php.bak
./hapus_nota_dinas.php.bak
./tambah_surat_keluar1.php.bak
./hapus_disposisi.php.bak
./tambah_teruskan.php.bak
./tambah_nota_dinas.php.bak
./cetak_terusan - Copy.php
```

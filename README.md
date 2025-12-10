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

## Penyimpanan Lampiran ke Google Drive (Opsional)

Aplikasi ini bisa menyimpan lampiran ke Google Drive menggunakan Service Account tanpa Composer. Fitur ini bersifat opsional dan dapat diaktifkan via konfigurasi.

Langkah singkat pengaktifan:

1) Siapkan Service Account Google Cloud dengan akses Drive API v3 aktif. Unduh berkas kredensial JSON.

2) Letakkan file JSON di path berikut (buat folder jika belum ada):
- `src/Utils/credentials/service-account.json`

3) Atur konfigurasi di `src/include/config.php`:
- `UPLOAD_STORAGE` set ke `'gdrive'` untuk mengaktifkan Drive (default `'local'`).
- `GDRIVE_PARENT_FOLDER_ID` isi dengan Folder ID tujuan di Drive/Shared Drive Anda.
- Opsi: `GDRIVE_MAKE_FILE_PUBLIC` set `true` jika ingin link dapat diakses oleh siapa saja yang memiliki tautan.
- Opsi: `GDRIVE_SUPPORTS_ALL_DRIVES` set `true` jika folder tujuan berada di Shared Drive.

4) Persyaratan PHP:
- Ekstensi `curl` dan `openssl` aktif.

Perilaku saat aktif:
- Proses tambah surat keluar akan mengunggah lampiran ke Drive. Kolom `file` menyimpan penanda `gdrive:fileId=<id>|view=<url>`.
- Halaman penampil file (`src/SuratKeluar/lihat_file_sk.php`) otomatis mendeteksi file Drive dan mengarahkan ke tampilan Drive.
- Galeri dan detail file sudah diarahkan ke penampil tersebut sehingga file di Drive tetap bisa dibuka normal.

Catatan: Jika unggah ke Drive gagal, sistem otomatis kembali menyimpan secara lokal agar alur kerja tetap berjalan.

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

# Profil Ujian CPNS, UTBK, dan TKA

## Keputusan domain

- **CPNS** memakai tiga section awal: TWK, TIU, dan TKP. Nilai section dijumlahkan dari bobot opsi. Admin tetap wajib mengisi bobot pada setiap opsi soal: TWK/TIU umumnya `5` untuk jawaban benar dan `0` untuk lainnya; TKP memakai bobot `1` sampai `5` sesuai kunci materi.
- **UTBK** memakai tujuh section awal sesuai komponen UTBK-SNBT. Sistem hanya menampilkan persentase atau jumlah benar sebagai **skor simulasi**, bukan skor terstandar resmi SNPMB.
- **TKA** memakai tiga mata uji wajib awal: Bahasa Indonesia, Matematika, dan Bahasa Inggris. Mata uji pilihan dapat ditambahkan admin sebagai section berikutnya. Sistem saat ini hanya menghasilkan skor simulasi untuk model pilihan tunggal.
- Section dipertahankan untuk ketiga program karena menjadi batas struktur soal, rincian hasil, urutan, dan opsi timer per bagian.

Konfigurasi dropdown bersumber dari `App\Services\Exam\ExamProgramProfileService`. Validasi server menggunakan profil yang sama sehingga opsi yang disembunyikan oleh antarmuka tidak dapat dikirim secara manual.

## Migrasi data

Migrasi `2026_09_18_000001_add_cpns_utbk_tka_exam_presets.php` bersifat idempoten:

- menambahkan kategori CPNS, UTBK, atau TKA hanya bila nama tersebut belum ada;
- menambahkan preset section hanya bila kategori terkait belum memiliki kategori penilaian aktif;
- tidak mengubah kategori penilaian atau paket yang sudah dibuat admin.

Pemeriksaan sebelum penerapan:

```bash
php artisan migrate:status
php artisan migrate --pretend --path=database/migrations/2026_09_18_000001_add_cpns_utbk_tka_exam_presets.php
```

Penerapan terarah:

```bash
php artisan migrate --path=database/migrations/2026_09_18_000001_add_cpns_utbk_tka_exam_presets.php
```

Setelah migrasi, periksa kategori dan section di admin sebelum membuat soal. Rollback otomatis hanya aman sebelum kategori baru direferensikan oleh judul soal, paket, transaksi, atau nilai. Setelah dipakai, lakukan pemulihan berbasis backup atau migrasi maju agar relasi pengguna tidak terhapus oleh cascade.

## Editor deskripsi ujian

Halaman tambah/edit paket kini memakai Tiptap 3 melalui `RichTextEditor.vue`; jalur ini tidak memakai Tiny Cloud maupun API key. HTML deskripsi lama tetap dimuat dan disimpan sebagai HTML. Toolbar menyediakan format teks dasar, daftar, alignment, tautan, gambar, tabel, undo/redo, dan layar penuh.

Upload gambar menggunakan endpoint lokal yang memerlukan admin aktif dan hanya menerima JPG, JPEG, PNG, WebP, atau GIF sampai 5 MB.

Pastikan symbolic link penyimpanan publik tersedia pada setiap environment:

```bash
php artisan storage:link
```

Keputusan mengganti TinyMCE hanya pada deskripsi paket dibuat karena versi TinyMCE lama di repository memiliki advisori XSS dan integrasi cloud-nya bergantung pada key/domain. Editor lama pada modul Soal, FAQ, dan Pengumuman belum dimigrasikan karena modul Soal mempunyai integrasi rumus WIRIS yang membutuhkan desain kompatibilitas terpisah. Key lama pada modul tersebut harus dirotasi dan migrasinya perlu dilakukan sebagai pekerjaan keamanan lanjutan, bukan penggantian mekanis tanpa uji rumus.

## Keterbatasan yang disengaja

- Skor resmi UTBK tidak direplikasi karena formula standardisasi resminya bukan sekadar jumlah benar/persentase.
- Bentuk TKA pilihan ganda kompleks kategori belum direpresentasikan oleh skema satu jawaban saat ini. Perlu perubahan model jawaban dan penilaian tersendiri sebelum dapat diklaim setara format resmi.
- Preset tidak mengisi bobot setiap soal CPNS karena bobot adalah bagian dari konten/kunci soal, bukan metadata kategori.

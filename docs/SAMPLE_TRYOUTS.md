# Paket Sampel Tryout

Seeder `ProgramTryoutSampleSeeder` menyediakan lima paket mini yang dapat dicoba melalui alur ujian normal:

- CPNS: TWK, TIU, dan TKP dengan bobot opsi 5/0 untuk TWK/TIU dan 1–5 untuk TKP.
- UTBK-SNBT: tujuh subtes, dengan nilai simulasi per subtes.
- TKA SMA: Bahasa Indonesia, Matematika, Bahasa Inggris, Fisika, dan Ekonomi.
- TOEFL ITP: Listening, Structure and Written Expression, dan Reading.
- TOEIC: Listening dan Reading dalam konteks tempat kerja.

Semua soal merupakan contoh orisinal untuk menguji aplikasi. Soal dan nilai mini ini bukan naskah atau hasil resmi penyelenggara ujian.

## Menjalankan

```bash
php artisan db:seed --class=ProgramTryoutSampleSeeder
```

Seeder bersifat idempotent: paket, bank soal, soal, dan akses sampel memakai ID deterministik sehingga perintah dapat dijalankan ulang tanpa membuat duplikat. Jika akun `usertesting3@mail.com` tersedia, lima transaksi akses gratis yang sudah aktif juga dibuat untuk akun tersebut.

## Pemeriksaan data

Setiap paket harus memiliki:

- seluruh section yang diperlukan;
- minimal satu soal pilihan ganda pada setiap section;
- kunci jawaban dan pembahasan pada setiap soal;
- pengaturan tampil jawaban aktif;
- akses transaksi `done` dan voucher aktif untuk akun uji.

## Batas skor

- Nilai UTBK, TKA, dan TOEIC pada paket mini adalah skor latihan internal. TOEIC memakai tabel konversi aplikasi; nilainya bukan skor resmi 10–990.
- TOEFL memakai tabel konversi aplikasi, tetapi jumlah soal mini jauh lebih sedikit daripada TOEFL ITP Level 1 resmi sehingga hasilnya tidak dapat disetarakan dengan sertifikat.
- Bagian listening TOEFL dan TOEIC memakai transkrip agar workflow dapat diuji tanpa ketergantungan berkas audio.

## Referensi struktur

- CPNS SKD: BKN, komponen TWK, TIU, dan TKP — https://www.bkn.go.id/3-juta-pelamar-cpns-2024-berkompetisi-di-tahap-skd-2/
- UTBK-SNBT 2026: SNPMB, materi tes — https://www.snpmb.id/utbk-snbt/informasi-umum
- TKA: Pusat Asesmen Pendidikan, mata pelajaran wajib dan pilihan — https://pusmendik.kemendikdasmen.go.id/tka/
- TOEFL ITP: ETS, Listening, Structure and Written Expression, dan Reading — https://www.ca.ets.org/en/toefl/itp/test-content.html
- TOEIC Listening and Reading: ETS, struktur dua section — https://www.ets.org/toeic/about/listening-reading.html

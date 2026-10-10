# Impor Soal dari DOCX dan PDF

Fitur impor dokumen tersedia pada halaman daftar soal setiap bank soal melalui tombol **Impor DOCX/PDF**.

## Alur

1. Admin memilih dokumen `.docx` atau `.pdf` maksimal 5 MB.
2. Server mengekstrak teks dan memeriksa format, jumlah pilihan, kategori penilaian, bagian, kunci, poin opsi, pembahasan, serta duplikat.
3. Halaman menampilkan pratinjau. Pada tahap ini belum ada soal yang ditulis ke database.
4. Jika seluruh soal valid, admin mengonfirmasi impor.
5. Semua soal disimpan dalam satu transaksi database. Jika satu soal gagal, seluruh impor dibatalkan.

Pratinjau valid disimpan sementara di cache selama 30 menit dan terikat pada akun admin serta bank soal. Dokumen sumber tidak disimpan permanen.

## Format dokumen

Gunakan label teks berikut. `BAGIAN` dan `KATEGORI` berlaku untuk soal-soal sesudahnya sampai label tersebut diganti. Format menyesuaikan program bank soal (5 pilihan untuk TKA/UTBK, 4 pilihan untuk TOEFL/TOEIC).

### Contoh TKA:
```text
BAGIAN: 1
KATEGORI: Bahasa Indonesia
SOAL 1: Tuliskan pertanyaan di sini...
A. Pilihan pertama
B. Pilihan kedua
C. Pilihan ketiga
D. Pilihan keempat
E. Pilihan kelima
KUNCI: A
PEMBAHASAN: Penjelasan jawaban.
```

### Contoh UTBK:
```text
BAGIAN: 1
KATEGORI: Penalaran Umum
SOAL 1: Tuliskan pertanyaan di sini...
A. Pilihan pertama
B. Pilihan kedua
C. Pilihan ketiga
D. Pilihan keempat
E. Pilihan kelima
KUNCI: A
PEMBAHASAN: Penjelasan jawaban.
```

### Contoh TOEFL:
```text
BAGIAN: 1
KATEGORI: Listening Comprehension
SOAL 1: Transcript: ...
A. Pilihan pertama
B. Pilihan kedua
C. Pilihan ketiga
D. Pilihan keempat
KUNCI: A
PEMBAHASAN: Penjelasan jawaban.
```

### Unduh Template Word (.docx)
Admin dapat mengunduh langsung template Word siap pakai via tombol **Unduh Template Word** pada halaman impor atau dari berkas `public/templates/soal/format-soal-[tka|utbk|toefl|toeic|cpns].docx`.

Untuk kategori yang memakai poin per opsi, tambahkan seluruh poin sampai jumlah pilihan terakhir:

```text
POIN A: 1
POIN B: 2
POIN C: 3
POIN D: 4
```

Label `BOBOT A` juga diterima sebagai pengganti `POIN A`. Label berbahasa Inggris (`SECTION`, `CATEGORY`, `QUESTION`, `KEY`, `ANSWER`, `DISCUSSION`, `EXPLANATION`) juga didukung.

## Aturan validasi

- Maksimal 200 soal dan 500.000 karakter teks per dokumen.
- Jumlah pilihan harus sama dengan konfigurasi bank soal.
- `KATEGORI` harus sama dengan kategori penilaian aktif pada program tersebut.
- `BAGIAN` harus sesuai dengan section kategori penilaian.
- `KUNCI` dan `PEMBAHASAN` wajib tersedia.
- Kategori berbobot opsi wajib memiliki poin untuk semua pilihan.
- Soal yang sama dalam dokumen atau sudah ada dalam bank soal ditolak.
- PDF hasil scan/gambar tanpa lapisan teks tidak didukung. Lakukan OCR terlebih dahulu.
- Audio dan gambar tertanam belum diimpor; konten impor dokumen saat ini berupa teks.

## Keamanan dan operasional

- Endpoint berada di bawah middleware autentikasi, admin, dan akun aktif.
- Ekstensi, MIME sebenarnya, dan ukuran berkas divalidasi server-side.
- DOCX dibaca sebagai OOXML dengan akses jaringan XML dinonaktifkan.
- PDF diproses menggunakan `smalot/pdfparser` versi `^2.12.5` atau yang kompatibel berdasarkan lock file.
- Isi teks diubah menjadi HTML ter-escape sebelum disimpan untuk mencegah injeksi skrip.
- Keberhasilan impor dicatat tanpa menyimpan isi soal atau nama dokumen di log.

Tidak ada migrasi database untuk fitur ini. Rollback aplikasi dilakukan dengan mengembalikan route, controller, request, service, halaman Vue, serta dependensi parser. Soal yang sudah sengaja diimpor adalah data bisnis dan tidak boleh dihapus otomatis saat rollback kode.

# SIMRS

Aplikasi SIMRS tahap rawat jalan dengan alur admisi, rekam pemeriksaan, resep, farmasi, dan pembayaran. Aplikasi tidak membuat akun login secara otomatis. Data rumah sakit fiktif untuk uji coba tersedia melalui seeder opsional.

## Kebutuhan

- PHP 8.3 atau lebih baru dan Composer
- Node.js dan npm
- Database SQLite untuk penggunaan lokal, atau database yang didukung Laravel untuk server

## Menjalankan lokal di Windows

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Test-Path database\\database.sqlite)) { New-Item -ItemType File -Path database\\database.sqlite | Out-Null }
composer install
php artisan key:generate
php artisan migrate
php artisan simrs:install
npm install
npm run build
php artisan serve
```

Perintah `php artisan simrs:install` meminta nama, email, serta kata sandi administrator pertama melalui terminal. Kata sandi dimasukkan secara tersembunyi dan hanya dapat digunakan sebelum akun pertama dibuat. Jangan menjalankan seeder untuk membuat akun operasional.

Untuk mengisi database lokal dengan akun presentasi, master data fiktif, dan antrean rawat jalan pada beberapa tahap alur, jalankan:

```powershell
php artisan db:seed
```

Seeder membuat lima akun presentasi lokal. Kata sandi untuk akun presentasi adalah `password`:

| Fungsi | Email | Role sistem |
|---|---|---|
| Administrator sistem (super user) | `administrator@simrs.test` | `super_admin` |
| Admin / Front Office (pendaftaran) | `admin@simrs.test` | `administrasi` |
| Dokter | `dokter@simrs.test` | `dokter` |
| Farmasi | `farmasi@simrs.test` | `farmasi` |
| Kasir | `kasir@simrs.test` | `kasir` |

Seeder menetapkan kata sandi `password` untuk kelima akun tersebut pada environment lokal/testing. Menjalankan ulang seeder akan menetapkan ulang kata sandi akun-akun presentasi.
Nama dan status akun yang sudah ada tidak ditimpa saat seeder dijalankan ulang. Perubahan nama akun dilakukan oleh Administrator sistem melalui menu **Akun & peran**.

Seeder juga menyiapkan lima kunjungan pada tahapan berbeda. Kunjungan `UJI-ALUR-001` masih menunggu dan dapat digunakan untuk alur end-to-end: admin/front office → dokter → farmasi → kasir. Kunjungan berawalan `UJI-DOKTER`, `UJI-FARMASI`, `UJI-KASIR`, dan `UJI-SELESAI` merupakan snapshot antrean untuk memperlihatkan workspace tiap role. Nomor rekam medis diawali `UJI-`; NIK dan nomor telepon pasien dikosongkan. Catatan klinis dan resep diberi penanda simulasi.

Semua akun presentasi ini hanya untuk `local`/`testing`; seeder menolak environment lain. Jangan gunakan kredensial tersebut di server publik atau produksi. Peran perawat/bidan, analis lab/radiografer, logistik, rekam medis, manajemen/EIS, portal pasien, rawat inap/IGD, dan integrasi eksternal belum tersedia dalam aplikasi ini.

Setelah masuk sebagai administrator:

1. Buat akun petugas dengan peran yang sesuai.
2. Jika memakai data uji coba, akun `dokter@simrs.test` sudah terhubung ke profil simulasi aktif. Jika tidak, tambahkan poli dan profil dokter operasional.
3. Daftarkan pasien, buat kunjungan, lalu proses pemeriksaan, resep, farmasi, dan pembayaran.

Tagihan rawat jalan saat ini menghitung tarif konsultasi poli yang tersimpan saat pemeriksaan selesai. Pembayaran BPJS/asuransi, klaim, harga/stok obat, integrasi eksternal, rawat inap/IGD, dan modul enterprise lain belum tersedia; jangan aktifkan untuk penggunaan klinis/keuangan sebelum implementasi dan validasi rumah sakit.

## Menyiapkan server

1. Atur `.env` dengan `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` yang unik, `APP_URL` HTTPS, database dan kredensial khusus server.
2. Pastikan database dan direktori `storage` serta `bootstrap/cache` dapat ditulis oleh proses aplikasi.
3. Pasang dependensi PHP, jalankan migrasi dengan `php artisan migrate --force`, lalu buat administrator pertama dengan `php artisan simrs:install` melalui terminal server yang terlindungi.
4. Bangun aset frontend (`npm install` dan `npm run build`) saat proses deployment.
5. Gunakan HTTPS, backup database terenkripsi, kontrol akses server, dan kebijakan retensi data sebelum menyimpan informasi pasien nyata.

Aplikasi ini belum dinyatakan memenuhi sertifikasi/regulasi SIMRS atau siap menangani data kesehatan produksi tanpa konfigurasi, uji keamanan, audit, dan persetujuan operasional rumah sakit.

## Pemeriksaan

```powershell
php artisan test --compact
vendor/bin/pint --format agent
npm run build
```

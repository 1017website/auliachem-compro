# Deployment Auliachem

## Persiapan server

1. Gunakan PHP 8.2 atau lebih baru dan arahkan document root ke folder `public`.
2. Salin `.env.example` menjadi `.env`, lalu isi koneksi MySQL, `APP_URL`, dan konfigurasi SMTP.
3. Atur `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL` dengan HTTPS.
4. Atur `QUOTE_RECIPIENT` ke email penerima permintaan penawaran.
5. Isi `CMS_BOOTSTRAP_NAME`, `CMS_BOOTSTRAP_EMAIL`, dan `CMS_BOOTSTRAP_PASSWORD`. Password minimal 12 karakter dan hanya dipakai untuk membuat akun developer pertama.

## Perintah deployment

```bash
composer install --no-dev --optimize-autoloader
# Hanya pada deployment pertama, bila APP_KEY masih kosong:
php artisan key:generate --force
php artisan migrate --seed --force
php artisan storage:link
php artisan optimize
php artisan cms:production-check
```

Jangan jalankan `key:generate` lagi pada deployment berikutnya karena akan mengganti kunci enkripsi aplikasi. Pastikan web server dapat menulis ke `storage` dan `bootstrap/cache`. Aktifkan sertifikat HTTPS sebelum website dibuka untuk publik.

Seeder aman dijalankan ulang. Konten hanya ditambahkan jika belum ada, sedangkan akun dengan email yang sama tidak akan diubah atau di-reset password-nya. Setelah login pertama, ganti password melalui profil CMS. Nilai `CMS_BOOTSTRAP_PASSWORD` boleh dihapus dari `.env` setelah akun berhasil dibuat; deployment berikutnya akan memakai akun aktif yang sudah ada.

## Setelah deployment

- Masuk dengan akun developer dan buka **Peralatan developer**.
- Jalankan **Periksa kesiapan production** sampai seluruh item berstatus `OK`.
- Kirim satu permintaan penawaran percobaan dan pastikan email diterima.
- Upload satu PDF percobaan, unduh dari frontend, lalu hapus bila sudah selesai.
- Jadwalkan backup database dan folder `storage/app` melalui fasilitas backup hosting.

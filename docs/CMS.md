# Auliachem company profile dan CMS

Website Laravel memakai template `resources/templates/company.html`, salinan dari `compro-fix.html` milik pengguna. Desain, gambar, isi awal, dan susunan bagian mengikuti template tersebut. Konten diterjemahkan di server sehingga tetap tampil tanpa JavaScript. Gambar awal dan font masih menggunakan URL eksternal dari template; gambar dapat diganti dengan unggahan lokal melalui CMS.

## Menjalankan project

PHP 8.2 atau lebih baru serta ekstensi DOM, fileinfo, GD, dan PDO MySQL diperlukan. Instal dependensi dengan `composer install` bila folder vendor belum tersedia. Salin `.env.example` ke `.env` hanya untuk instalasi baru, lalu jalankan `php artisan key:generate` dan isi koneksi MySQL.

```sh
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Seeder mengimpor konten dan membuat akun developer awal dari `CMS_BOOTSTRAP_NAME`, `CMS_BOOTSTRAP_EMAIL`, dan `CMS_BOOTSTRAP_PASSWORD`. Password minimal 12 karakter dan tidak memiliki nilai default di source code. Akun yang sudah ada tidak ditimpa. Untuk membuat admin tambahan secara interaktif, gunakan `php artisan cms:admin admin@perusahaan.com`.

Media publik dilayani oleh route `/storage/{path}` langsung dari `storage/app/public`. Ini menggantikan symbolic link pada hosting yang menonaktifkan fungsi PHP `symlink()` dan `exec()`.

Halaman publik: `/`. Login CMS: `/admin/login`. Editor CMS: `/admin`.

Untuk instalasi lokal ini migrasi, impor konten, tautan storage, dan akun admin lokal telah disiapkan. Kredensial lokal diberikan terpisah di percakapan, tidak disimpan di repository.

## Mengelola konten

1. Masuk ke CMS dan pilih bagian website di navigasi sebelah kiri. Pada ponsel, navigasi bagian dapat digeser horizontal.
2. Pilih bahasa Indonesia, English, atau Mandarin untuk mengubah teks. Ketiga versi tetap tersimpan saat berpindah pilihan bahasa.
3. Ubah URL gambar atau unggah JPG, PNG, atau WebP maksimal 2 MB. Gambar dan tujuan tautan berlaku untuk semua bahasa.
4. Klik **Simpan perubahan**. Perubahan bagian tersebut langsung tampil di website.
5. Klik **Lihat website** untuk membuka hasil di tab baru.

CMS mencakup SEO, navigasi, hero, ringkasan solusi, produk utama, garam industri, aplikasi industri, solusi laboratorium, fasilitas, kualitas dan dokumen, kontak, serta footer. Label field memakai teks asli template agar elemen mudah dikenali. Gambar latar sekarang tersedia di bagian yang menggunakannya, misalnya Foto utama hero di menu Hero. Menu **Semua gambar** mengumpulkan seluruh gambar dan logo dalam satu halaman. Tombol penawaran mempertahankan `mailto:info@auliachem.com` dari template dan dapat diubah pada bagian Kontak.

## Logo, favicon, dan media

Menu **Logo & favicon** menyediakan unggahan logo header, logo footer, dan favicon. Logo header juga digunakan di CMS dan halaman login. Favicon diterapkan pada tab website publik dan CMS. Gunakan gambar persegi 64 × 64 px untuk favicon; format ICO, PNG, JPG, atau WebP, maksimal 2 MB. Gambar biasa menerima JPG, PNG, atau WebP maksimal 2 MB. SVG tidak diterima.

Klik **Pilih file** atau tarik gambar ke area upload. Thumbnail, nama file, dan ukuran akan tampil sebelum disimpan. **Batalkan file baru** mengembalikan gambar sebelumnya. Pilihan **Gunakan URL gambar** masih tersedia jika menggunakan gambar eksternal. File baru diterbitkan setelah **Simpan perubahan**, bukan saat dipilih.

## Live preview

Editor desktop menampilkan frontend di panel sebelah kanan. Saat field dipilih, area yang terkait disorot dengan garis biru. Teks dan file yang sedang dipilih langsung terlihat tanpa menyimpan. Tombol ikon mata pada setiap kartu membawa preview ke lokasi elemen tersebut. Gambar latar, logo, teks, dan tautan terhubung ke lokasi aslinya pada template.

Tombol monitor/ponsel mengubah ukuran viewport preview. Pemilih bahasa juga mengubah bahasa preview tanpa menghapus perubahan yang belum disimpan. Pada tablet dan ponsel, tombol **Preview** atau ikon mata membuka panel preview; tutup dengan tombol X atau Escape untuk kembali mengedit. Sidebar ponsel dibuka melalui tombol menu.

SEO menyediakan contoh judul/deskripsi mesin pencari; favicon diperlihatkan di bilah alamat preview. Preview hanya dapat diakses oleh admin, tidak menulis ke database, dan tidak masuk analytics. Tautan eksternal di dalam preview dinonaktifkan agar panel tetap berada di website yang sedang diedit.

## Analytics pengunjung

Dashboard tersedia pada `/admin/analytics`, dengan periode 7, 30, atau 90 hari. Data terdiri atas page views, pengunjung unik berdasarkan sesi browser, kunjungan hari ini, grafik harian, perangkat, bahasa halaman, dan domain referrer. Data harian juga tersedia dalam tabel untuk akses keyboard.

Pencatatan dimulai sejak migrasi analytics dijalankan. Tidak ada data contoh atau impor histori. Refresh halaman menambah page view, tetapi sesi yang sama tetap satu pengunjung unik dalam periode terpilih. Pergantian perangkat, sesi yang kedaluwarsa, atau browser lain dapat dihitung sebagai pengunjung baru. Waktu memakai timezone aplikasi yang ditampilkan di dashboard (default project: UTC).

Hanya GET halaman publik yang berhasil dicatat. HEAD, bahasa tidak valid, bot umum, admin yang login, serta `/admin/preview` tidak dihitung. Identitas kunjungan berupa hash ID acak dalam sesi Laravel, tanpa menyimpan IP, user agent mentah, atau URL referrer lengkap. Pengelompokan perangkat didasarkan pada user agent dan bersifat perkiraan. Pembatasan bot berbasis user agent tidak menghilangkan semua trafik otomatis.

Untuk memperbarui instalasi lama, jalankan `php artisan migrate` dan `php artisan cms:import`. Impor kini juga memperbarui label/pengelompokan field tanpa menimpa nilainya. Hindari menambahkan atau mengurutkan ulang node template tanpa migrasi key.

Bahasa publik dapat dipilih lewat tombol ID/EN/中文 atau URL `/?lang=id`, `/?lang=en`, dan `/?lang=zh`. Pilihan bahasa tersimpan di URL dan tidak memerlukan localStorage.

Editor mengatur konten pada struktur template yang sudah ada. Menambah bagian atau kartu baru memerlukan perubahan kode template. Tidak ada editor HTML bebas, alur draft/publikasi, atau pengiriman email otomatis.

## Penyimpanan dan akses

- Konten disimpan di tabel `content_fields`; gambar unggahan di `storage/app/public/cms`.
- `cms:import` aman dijalankan ulang: hanya menambahkan field yang belum ada, tidak menimpa perubahan.
- Jangan mengubah urutan node template setelah konten diisi tanpa migrasi pemetaan key. Key konten berasal dari posisi node pada template sumber.
- Hanya pengguna dengan `is_admin = true` yang bisa mengakses CMS. Tidak ada registrasi publik.
- Login dibatasi lima percobaan per menit per IP. Session diperbarui saat login dan dibatalkan saat logout.
- Form memakai CSRF, teks di-escape, URL skrip ditolak, dan file unggahan diperiksa jenis serta ukurannya.
- Jika bagian yang sama berubah di sesi lain, penyimpanan ditolak supaya perubahan sebelumnya tidak tertimpa. Salin perubahan yang belum disimpan, muat ulang, lalu terapkan kembali.
- Gambar lama tidak dihapus otomatis saat diganti, agar referensi yang masih digunakan tidak rusak. Backup database dan direktori unggahan bersama-sama.

## Pemeriksaan

```sh
php artisan test
php artisan view:cache
```

Tes memakai SQLite dalam memori dan tidak mengubah database lokal. Aset CSS/JS dilayani langsung dari `public`, sehingga halaman ini tidak memerlukan build Vite. Sebelum menjalankan di produksi, gunakan konfigurasi database dan domain produksi, `APP_DEBUG=false`, HTTPS, dan akun admin milik pengelola.

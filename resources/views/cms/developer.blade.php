@extends('cms.layout')

@section('title', 'Peralatan developer')

@section('content')
<main class="developer-page">
    <div class="page-heading">
        <div>
            <p class="eyebrow">KHUSUS AKUN DEVELOPER</p>
            <h1>Perintah pemeliharaan</h1>
            <p>Jalankan perintah Laravel yang sudah dibatasi dari panel ini.</p>
        </div>
    </div>

    @if(session('status') || session('command_error'))
        @php($result = session('status') ?? session('command_error'))
        <div class="alert {{ session('command_error') ? 'error' : 'success' }} command-result" role="status">
            <x-icon :name="session('command_error') ? 'close' : 'check'"/>
            <div><strong>{{ $result['command'] }}</strong><pre>{{ $result['output'] }}</pre></div>
        </div>
    @endif

    <section class="developer-tools" aria-label="Daftar perintah">
        <article class="tool-row">
            <span class="tool-icon"><x-icon name="broom"/></span>
            <div><h2>Bersihkan cache aplikasi</h2><code>php artisan optimize:clear</code><p>Membersihkan cache konfigurasi, route, view, event, dan hasil optimasi Laravel.</p></div>
            <form method="post" action="{{ route('cms.developer.run') }}">@csrf<input type="hidden" name="tool" value="clear-cache"><button class="secondary" type="submit"><x-icon name="broom"/>Jalankan</button></form>
        </article>
        <article class="tool-row">
            <span class="tool-icon"><x-icon name="link"/></span>
            <div><h2>Hubungkan storage publik</h2><code>php artisan storage:link</code><p>Membuat tautan public/storage agar gambar yang diunggah dari CMS dapat diakses.</p></div>
            <form method="post" action="{{ route('cms.developer.run') }}">@csrf<input type="hidden" name="tool" value="storage-link"><button class="secondary" type="submit"><x-icon name="link"/>Jalankan</button></form>
        </article>
        <article class="tool-row">
            <span class="tool-icon"><x-icon name="database"/></span>
            <div><h2>Jalankan migrasi database</h2><code>php artisan migrate</code><p>Menjalankan migrasi yang masih tertunda pada database aktif.</p></div>
            <form method="post" action="{{ route('cms.developer.run') }}">@csrf<input type="hidden" name="tool" value="migrate"><button class="primary" type="submit"><x-icon name="database"/>Jalankan migrasi</button></form>
        </article>
        <article class="tool-row">
            <span class="tool-icon"><x-icon name="shield"/></span>
            <div><h2>Periksa kesiapan production</h2><code>php artisan cms:production-check</code><p>Memeriksa environment, debug, HTTPS, email, penerima lead, dan akses storage.</p></div>
            <form method="post" action="{{ route('cms.developer.run') }}">@csrf<input type="hidden" name="tool" value="production-check"><button class="secondary" type="submit"><x-icon name="shield"/>Periksa sekarang</button></form>
        </article>
        <article class="tool-row"><span class="tool-icon"><x-icon name="image"/></span><div><h2>Audit media tidak terpakai</h2><code>php artisan cms:media-audit</code><p>Mendeteksi unggahan yang tidak lagi dipakai tanpa menghapus file.</p></div><form method="post" action="{{ route('cms.developer.run') }}">@csrf<input type="hidden" name="tool" value="media-audit"><button class="secondary"><x-icon name="image"/>Periksa media</button></form></article>
    </section>
    <p class="developer-note">Setiap perintah hanya tersedia untuk akun dengan role developer. Hasil eksekusi ditampilkan setelah proses selesai.</p>
</main>
@endsection

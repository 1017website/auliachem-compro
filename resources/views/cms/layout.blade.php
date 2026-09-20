<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Editor website') | Auliachem CMS</title>
    <link rel="icon" href="{{ $cmsFavicon }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;650;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/cms.css') }}?v={{ filemtime(public_path('css/cms.css')) }}">
    <script src="{{ asset('js/cms.js') }}?v={{ filemtime(public_path('js/cms.js')) }}" defer></script>
</head>
<body class="{{ auth()->check() ? 'admin-app' : 'auth-page' }}">
@auth
    <button class="sidebar-scrim" data-close-sidebar aria-label="Tutup menu" hidden></button>
    <aside class="sidebar" id="cms-sidebar">
        <a class="brand" href="{{ route('cms.analytics') }}"><img src="{{ $cmsLogo }}" alt="Auliachem Perkasa"><span>Website workspace</span></a>
        <button class="icon-button sidebar-close" data-close-sidebar aria-label="Tutup menu"><x-icon name="close"/></button>
        <p class="nav-label">Workspace</p>
        <nav aria-label="Menu utama">
            <a href="{{ route('cms.analytics') }}" @class(['selected' => request()->routeIs('cms.analytics')])><x-icon name="chart"/> <span>Analytics pengunjung</span></a>
            <a href="{{ route('cms.edit', ['section' => 'media']) }}" @class(['selected' => ($section ?? '') === 'media'])><x-icon name="image"/> <span>Semua gambar</span></a>
            <a href="{{ route('cms.edit', ['section' => 'branding']) }}" @class(['selected' => ($section ?? '') === 'branding'])><x-icon name="brand"/> <span>Logo & favicon</span></a>
            <a href="{{ route('cms.quotes.index') }}" @class(['selected' => request()->routeIs('cms.quotes.*')])><x-icon name="inbox"/> <span>Permintaan penawaran</span>@if($newQuoteCount)<span class="nav-count">{{ $newQuoteCount > 99 ? '99+' : $newQuoteCount }}</span>@endif</a>
            <a href="{{ route('cms.documents.index') }}" @class(['selected' => request()->routeIs('cms.documents.*')])><x-icon name="file-download"/> <span>Dokumen</span></a>
            <a href="{{ route('cms.products.index') }}" @class(['selected' => request()->routeIs('cms.products.*')])><x-icon name="package"/> <span>Katalog produk</span></a>
            <a href="{{ route('cms.activity') }}" @class(['selected' => request()->routeIs('cms.activity')])><x-icon name="shield"/> <span>Log aktivitas</span></a>
            <a href="{{ route('cms.profile') }}" @class(['selected' => request()->routeIs('cms.profile*')])><x-icon name="key"/> <span>Keamanan akun</span></a>
            @if(auth()->user()->isDeveloper())
                <a href="{{ route('cms.developer') }}" @class(['selected' => request()->routeIs('cms.developer*')])><x-icon name="terminal"/> <span>Peralatan developer</span></a>
                <a href="{{ route('cms.users.index') }}" @class(['selected' => request()->routeIs('cms.users.*')])><x-icon name="users"/> <span>Manage users</span></a>
            @endif
        </nav>
        <p class="nav-label">Konten website</p>
        <nav aria-label="Bagian konten">
            @php($sectionIcons = ['settings' => 'globe', 'navbar' => 'navigation', 'home' => 'dashboard', 'solution-strip' => 'layers', 'core' => 'package', 'industrial-salt' => 'droplet', 'applications' => 'factory', 'labsolution' => 'flask', 'handling' => 'warehouse', 'quality' => 'shield', 'contact' => 'mail', 'footer' => 'layout'])
            @foreach($cmsSections as $key => $label)
                @continue($key === 'branding')
                <a href="{{ route('cms.edit', ['section' => $key]) }}" @class(['selected' => ($section ?? '') === $key]) @if(($section ?? '') === $key) aria-current="page" @endif><x-icon :name="$sectionIcons[$key] ?? 'document'"/><span>{{ $label }}</span></a>
            @endforeach
        </nav>
        <div class="sidebar-footer"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><div><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->isDeveloper() ? 'Developer' : 'Administrator' }}</span></div><form method="post" action="{{ route('logout') }}">@csrf<button class="icon-button" title="Keluar" aria-label="Keluar"><x-icon name="logout"/></button></form></div>
    </aside>
@endauth
<div class="app-body">
    <header class="topbar">
        @auth
            <div class="topbar-title"><button type="button" class="icon-button mobile-menu" data-open-sidebar aria-expanded="false" aria-controls="cms-sidebar" aria-label="Buka menu"><x-icon name="menu"/></button><span>Auliachem <span class="breadcrumb-divider">/</span> <strong>@yield('title', 'Editor website')</strong></span></div>
        @else
            <a class="brand" href="{{ route('home') }}"><img src="{{ $cmsLogo }}" alt="Auliachem Perkasa"></a>
        @endauth
        <a class="website-link" href="{{ route('home') }}" target="_blank" rel="noopener">Lihat website <x-icon name="arrow"/></a>
    </header>
    @yield('content')
</div>
</body>
</html>

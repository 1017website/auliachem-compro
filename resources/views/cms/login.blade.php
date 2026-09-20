@extends('cms.layout')
@section('title', 'Masuk admin')
@section('content')
<main class="login-shell">
    <div class="login-intro"><span class="eyebrow">Auliachem Perkasa</span><h1>Konten yang tepat.<br>Informasi yang terjaga.</h1><p>Kelola company profile, produk, dan informasi layanan dari satu tempat.</p></div>
    <section class="login-card" aria-labelledby="login-title">
        <h2 id="login-title">Masuk ke CMS</h2><p>Gunakan akun administrator Anda.</p>
        @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
        <form action="{{ route('login.store') }}" method="post">@csrf
            <label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
            <label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password">
            <button class="primary" type="submit">Masuk</button>
        </form>
    </section>
</main>
@endsection

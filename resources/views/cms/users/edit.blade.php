@extends('cms.layout')

@section('title', 'Edit pengguna')

@section('content')
<main class="users-page narrow-page">
    <div class="page-heading">
        <div>
            <p class="eyebrow">AKSES CMS</p>
            <h1>Edit pengguna</h1>
            <p>Perbarui identitas atau ganti password administrator.</p>
        </div>
        <a class="secondary" href="{{ route('cms.users.index') }}">Kembali</a>
    </div>

    @if($errors->any())
        <div class="alert error" role="alert"><strong>Data belum dapat disimpan.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="user-form-panel edit-user-panel">
        <div class="panel-heading"><span class="tool-icon"><x-icon name="edit"/></span><div><h2>{{ $managedUser->name }}</h2><p>{{ $managedUser->email }}</p></div></div>
        <form method="post" action="{{ route('cms.users.update', $managedUser) }}" class="user-form">
            @csrf @method('PUT')
            <label for="name">Nama</label>
            <input id="name" name="name" value="{{ old('name', $managedUser->name) }}" required autocomplete="name">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $managedUser->email) }}" required autocomplete="email">
            <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$managedUser->is_active))> Akun aktif dan dapat masuk</label>
            <div class="password-heading"><x-icon name="key"/><div><strong>Ganti password</strong><span>Kosongkan bila password lama tetap digunakan.</span></div></div>
            <label for="password">Password baru</label>
            <input id="password" name="password" type="password" minlength="12" autocomplete="new-password">
            <label for="password_confirmation">Ulangi password baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password">
            <button class="primary" type="submit"><x-icon name="save"/>Simpan perubahan</button>
        </form>
    </section>
</main>
@endsection

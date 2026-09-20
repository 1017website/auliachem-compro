@extends('cms.layout')

@section('title', 'Manage users')

@section('content')
<main class="users-page">
    <div class="page-heading">
        <div>
            <p class="eyebrow">AKSES CMS</p>
            <h1>Manage users</h1>
            <p>Tambahkan dan kelola akun administrator yang dapat mengubah konten website.</p>
        </div>
    </div>

    @if(session('status'))
        <div class="alert success" role="status"><x-icon name="check"/>{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert error" role="alert"><strong>Data belum dapat disimpan.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="users-layout">
        <section class="user-form-panel" aria-labelledby="add-user-title">
            <div class="panel-heading"><span class="tool-icon"><x-icon name="user-plus"/></span><div><h2 id="add-user-title">Tambah administrator</h2><p>Akun baru langsung dapat masuk ke CMS.</p></div></div>
            <form method="post" action="{{ route('cms.users.store') }}" class="user-form">
                @csrf
                <label for="name">Nama</label>
                <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="Nama pengguna">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@perusahaan.com">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required minlength="12" autocomplete="new-password">
                <p class="help">Minimal 12 karakter.</p>
                <label for="password_confirmation">Ulangi password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password">
                <button class="primary" type="submit"><x-icon name="user-plus"/>Tambah pengguna</button>
            </form>
        </section>

        <section class="users-list-panel" aria-labelledby="users-title">
            <div class="list-heading"><div><h2 id="users-title">Administrator</h2><p>{{ $users->total() }} akun terdaftar</p></div></div>
            @forelse($users as $user)
                <article class="user-row">
                    <span class="avatar">{{ mb_substr($user->name, 0, 1) }}</span>
                    <div class="user-identity"><strong>{{ $user->name }}</strong><span>{{ $user->email }}</span></div>
                    <span class="user-role">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    <div class="user-actions">
                        <a class="icon-button" href="{{ route('cms.users.edit', $user) }}" title="Edit {{ $user->name }}" aria-label="Edit {{ $user->name }}"><x-icon name="edit"/></a>
                        <form method="post" action="{{ route('cms.users.destroy', $user) }}" onsubmit="return confirm('Hapus akses CMS pengguna ini?')">@csrf @method('DELETE')<button class="icon-button danger-button" type="submit" title="Hapus {{ $user->name }}" aria-label="Hapus {{ $user->name }}"><x-icon name="trash"/></button></form>
                    </div>
                </article>
            @empty
                <div class="empty-state"><x-icon name="users"/><h3>Belum ada administrator</h3><p>Gunakan formulir di samping untuk membuat akun pertama.</p></div>
            @endforelse
            @if($users->hasPages())<div class="pagination">{{ $users->links() }}</div>@endif
        </section>
    </div>
</main>
@endsection

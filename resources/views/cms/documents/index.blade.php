@extends('cms.layout')

@section('title', 'Dokumen')

@section('content')
<main class="records-page">
    <div class="page-heading"><div><p class="eyebrow">FILE PUBLIK</p><h1>Dokumen teknis</h1><p>Kelola PDF yang dapat diunduh dari bagian Kualitas & dokumen.</p></div></div>
    @if(session('status'))<div class="alert success" role="status"><x-icon name="check"/>{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert error" role="alert"><strong>Dokumen belum dapat disimpan.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="documents-layout">
        <section class="user-form-panel">
            <div class="panel-heading"><span class="tool-icon"><x-icon name="upload"/></span><div><h2>Upload dokumen</h2><p>PDF maksimal 10 MB.</p></div></div>
            <form method="post" action="{{ route('cms.documents.store') }}" enctype="multipart/form-data" class="user-form">@csrf
                <label for="document-title">Judul dokumen</label><input id="document-title" name="title" value="{{ old('title') }}" required maxlength="180">
                <label for="document-category">Kategori</label><select id="document-category" name="category" required>@foreach(['coa'=>'CoA','tds'=>'TDS','msds'=>'MSDS / SDS','certificate'=>'Sertifikat','catalog'=>'Katalog','other'=>'Lainnya'] as $value=>$label)<option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>@endforeach</select>
                <label for="document-description">Deskripsi <small>(opsional)</small></label><textarea id="document-description" name="description" maxlength="1000">{{ old('description') }}</textarea>
                <label for="document-file">File PDF</label><input id="document-file" name="file" type="file" accept="application/pdf,.pdf" required>
                <label class="check-row"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', true))><span>Tampilkan di website</span></label>
                <button class="primary"><x-icon name="upload"/>Upload dokumen</button>
            </form>
        </section>
        <section class="users-list-panel">
            <div class="list-heading"><div><h2>Daftar dokumen</h2><p>{{ $documents->total() }} file tersimpan</p></div></div>
            @forelse($documents as $document)
                <article class="document-record"><span class="document-badge">{{ strtoupper($document->category) }}</span><div class="document-identity"><strong>{{ $document->title }}</strong><span>{{ $document->original_name }} · {{ number_format($document->file_size / 1024, 0, ',', '.') }} KB · {{ $document->downloads }} unduhan</span></div><span class="publish-state {{ $document->is_published ? 'is-public' : '' }}">{{ $document->is_published ? 'Publik' : 'Draft' }}</span><div class="user-actions"><a class="icon-button" href="{{ route('cms.documents.edit', $document) }}" title="Edit {{ $document->title }}" aria-label="Edit {{ $document->title }}"><x-icon name="edit"/></a><form method="post" action="{{ route('cms.documents.destroy', $document) }}" onsubmit="return confirm('Hapus dokumen ini?')">@csrf @method('DELETE')<button class="icon-button danger-button" title="Hapus dokumen" aria-label="Hapus {{ $document->title }}"><x-icon name="trash"/></button></form></div></article>
            @empty
                <div class="empty-state"><x-icon name="file-download"/><h3>Belum ada dokumen</h3><p>Upload PDF pertama agar muncul di website.</p></div>
            @endforelse
            @if($documents->hasPages())<div class="pagination">{{ $documents->links() }}</div>@endif
        </section>
    </div>
</main>
@endsection

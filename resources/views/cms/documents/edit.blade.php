@extends('cms.layout')
@section('title', 'Edit dokumen')
@section('content')
<main class="records-page narrow-page">
    <div class="page-heading"><div><p class="eyebrow">FILE PUBLIK</p><h1>Edit dokumen</h1><p>Perbarui informasi atau ganti file PDF.</p></div><a class="secondary" href="{{ route('cms.documents.index') }}">Kembali</a></div>
    @if($errors->any())<div class="alert error" role="alert"><strong>Dokumen belum dapat disimpan.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="user-form-panel edit-user-panel"><div class="panel-heading"><span class="tool-icon"><x-icon name="file-download"/></span><div><h2>{{ $document->title }}</h2><p>{{ $document->original_name }}</p></div></div>
        <form method="post" action="{{ route('cms.documents.update', $document) }}" enctype="multipart/form-data" class="user-form">@csrf @method('PUT')
            <label for="title">Judul dokumen</label><input id="title" name="title" value="{{ old('title', $document->title) }}" required maxlength="180">
            <label for="category">Kategori</label><select id="category" name="category">@foreach(['coa'=>'CoA','tds'=>'TDS','msds'=>'MSDS / SDS','certificate'=>'Sertifikat','catalog'=>'Katalog','other'=>'Lainnya'] as $value=>$label)<option value="{{ $value }}" @selected(old('category', $document->category) === $value)>{{ $label }}</option>@endforeach</select>
            <label for="description">Deskripsi</label><textarea id="description" name="description" maxlength="1000">{{ old('description', $document->description) }}</textarea>
            <label for="file">Ganti PDF <small>(opsional)</small></label><input id="file" name="file" type="file" accept="application/pdf,.pdf"><p class="help">Kosongkan untuk mempertahankan file saat ini.</p>
            <label class="check-row"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $document->is_published))><span>Tampilkan di website</span></label>
            <button class="primary"><x-icon name="save"/>Simpan perubahan</button>
        </form>
    </section>
</main>
@endsection

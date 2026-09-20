@extends('cms.layout')
@section('title', $sections[$section])
@section('content')
<main class="editor" data-section="{{ $section }}">
    <div class="page-heading"><div><div class="eyebrow"><span class="status-dot"></span> CONTENT STUDIO</div><h1>{{ $sections[$section] }}</h1><p>{{ in_array($section, ['media', 'branding']) ? 'Unggah aset Anda. Lihat hasilnya sebelum diterbitkan.' : 'Atur konten, lihat perubahannya, lalu simpan saat sudah sesuai.' }}</p></div><button class="secondary preview-toggle" type="button" data-toggle-preview><x-icon name="eye"/> Preview</button></div>
    @if(session('status'))<div class="alert success" role="status"><x-icon name="check"/> {{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert error" role="alert"><strong>Perubahan belum tersimpan.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="studio-grid">
        <form action="{{ route('cms.update') }}" method="post" enctype="multipart/form-data" data-editor>
            @csrf @method('PUT')
            <input type="hidden" name="section" value="{{ $section }}">
            <div class="editor-toolbar"><div><x-icon name="document"/><strong>{{ $fields->count() }} elemen konten</strong></div><label class="language-select"><x-icon name="globe"/><span class="sr-only">Bahasa teks</span><select aria-label="Bahasa teks" data-language><option value="id">Indonesia</option><option value="en">English</option><option value="zh">Mandarin</option></select></label></div>
            <div class="field-list">
            @forelse($fields as $field)
                <section class="field-card" data-field-key="{{ $field->key }}" data-field-type="{{ $field->type }}" data-field-label="{{ $field->label }}">
                    <input type="hidden" name="version[{{ $field->id }}]" value="{{ old('version.'.$field->id, hash('sha256', json_encode($field->values))) }}">
                    <div class="field-heading"><div class="field-title"><span class="field-symbol"><x-icon :name="['text' => 'document', 'image' => 'image', 'link' => 'link'][$field->type]"/></span><div><span class="field-kind">{{ ['text' => 'Teks', 'image' => 'Media', 'link' => 'Tautan'][$field->type] }} @if($section === 'media') · {{ $cmsSections[$field->section] ?? $field->section }} @endif</span><h2>{{ $field->label }}</h2></div></div><button class="icon-button locate-field" type="button" data-locate title="Lihat posisi di website" aria-label="Lihat posisi {{ $field->label }}"><x-icon name="eye"/></button></div>
                    @if($field->type === 'text')
                        @foreach(['id' => 'Indonesia', 'en' => 'English', 'zh' => 'Mandarin'] as $locale => $language)
                            <div data-locale="{{ $locale }}"><label class="sr-only" for="field-{{ $field->id }}-{{ $locale }}">{{ $language }}: {{ $field->label }}</label><textarea id="field-{{ $field->id }}-{{ $locale }}" data-value-lang="{{ $locale }}" name="fields[{{ $field->id }}][{{ $locale }}]" rows="{{ mb_strlen($field->values[$locale] ?? '') > 160 ? 4 : 2 }}">{{ old('fields.'.$field->id.'.'.$locale, $field->values[$locale] ?? '') }}</textarea></div>
                        @endforeach
                    @elseif($field->type === 'image')
                        <div class="media-upload" data-dropzone>
                            <div class="image-stage"><img class="preview" data-image-preview src="{{ $field->values['id'] }}" alt="{{ $field->label }}" loading="lazy"></div>
                            <div class="upload-copy"><x-icon name="upload"/><strong>Ganti {{ $field->key === 'brand_favicon' ? 'favicon' : 'gambar' }}</strong><span>Tarik file ke sini atau pilih dari perangkat.</span><label class="upload-button" for="upload-{{ $field->id }}">Pilih file<input id="upload-{{ $field->id }}" name="uploads[{{ $field->id }}]" type="file" accept="image/jpeg,image/png,image/webp{{ $field->key === 'brand_favicon' ? ',image/x-icon,image/vnd.microsoft.icon,.ico' : '' }}" data-upload aria-label="Upload {{ $field->label }}"></label><small>{{ $field->key === 'brand_favicon' ? 'ICO, PNG, JPG, WebP · Disarankan 64 × 64 px' : 'JPG, PNG, WebP' }} · Maks. 2 MB</small></div>
                        </div>
                        <div class="upload-feedback" data-upload-feedback role="status"></div>
                        <button type="button" class="text-button" data-cancel-upload hidden>Batalkan file baru</button>
                        <div class="media-options"><label>Gambar khusus mobile <small>(opsional, disarankan lebar 900 px)</small></label><input name="mobile_uploads[{{ $field->id }}]" type="file" accept="image/jpeg,image/png,image/webp">@if($field->mobile_value)<img class="mobile-preview" src="{{ $field->mobile_value }}" alt="Preview mobile">@endif<label>Posisi crop</label><select name="image_position[{{ $field->id }}]">@foreach(['center'=>'Tengah','top'=>'Atas','bottom'=>'Bawah','left'=>'Kiri','right'=>'Kanan'] as $value=>$label)<option value="{{ $value }}" @selected(($field->image_position ?? 'center')===$value)>{{ $label }}</option>@endforeach</select>@foreach(['id'=>'Alt Indonesia','en'=>'Alt English','zh'=>'Alt Mandarin'] as $locale=>$label)<label>{{ $label }}</label><input name="alt[{{ $field->id }}][{{ $locale }}]" value="{{ old('alt.'.$field->id.'.'.$locale,$field->alt_values[$locale] ?? '') }}" maxlength="180">@endforeach</div>
                        <details class="url-details"><summary>Gunakan URL gambar</summary><label for="field-{{ $field->id }}">URL gambar</label><input id="field-{{ $field->id }}" data-value-lang="shared" name="fields[{{ $field->id }}][id]" value="{{ old('fields.'.$field->id.'.id', $field->values['id']) }}" type="text"></details>
                    @else
                        <label class="sr-only" for="field-{{ $field->id }}">Tujuan tautan: {{ $field->label }}</label><input id="field-{{ $field->id }}" data-value-lang="shared" name="fields[{{ $field->id }}][id]" value="{{ old('fields.'.$field->id.'.id', $field->values['id']) }}" type="text" required><p class="help">URL website, #bagian, mailto:email, atau tel:nomor.</p>
                    @endif
                </section>
            @empty
                <div class="empty-state"><x-icon name="document"/><h2>Belum ada konten</h2><p>Elemen bagian ini belum tersedia.</p></div>
            @endforelse
            </div>
            <div class="savebar"><span data-save-status aria-live="polite"><span class="status-dot"></span> Belum ada perubahan</span><button class="primary" type="submit"><x-icon name="save"/><span>Simpan perubahan</span></button></div>
        </form>
        <aside class="preview-panel" aria-label="Preview frontend">
            <div class="preview-toolbar"><div><span class="status-dot"></span><strong>Live preview</strong></div><div class="device-switch" aria-label="Ukuran preview"><button type="button" class="icon-button active" data-device="desktop" aria-label="Preview desktop" aria-pressed="true"><x-icon name="desktop"/></button><button type="button" class="icon-button" data-device="mobile" aria-label="Preview mobile" aria-pressed="false"><x-icon name="mobile"/></button></div><button class="icon-button preview-close" type="button" data-toggle-preview aria-label="Tutup preview"><x-icon name="close"/></button></div>
            <div class="preview-address"><img data-preview-favicon src="{{ $cmsFavicon }}" alt="Preview favicon"><span>auliachem.com / {{ $section === 'media' ? 'media' : $sections[$section] }}</span></div>
            @if($section === 'settings')<div class="seo-preview"><small>Preview informasi mesin pencari</small><strong data-seo-title>{{ $fields->firstWhere('key', 'text_0')?->values['id'] }}</strong><p data-seo-description>{{ $fields->firstWhere('label', 'Deskripsi mesin pencari')?->values['id'] }}</p></div>@endif
            <div class="preview-viewport"><div class="preview-canvas"><iframe title="Preview website Auliachem" src="{{ route('cms.preview', ['lang' => 'id']) }}" data-preview-frame referrerpolicy="same-origin"></iframe></div><div class="preview-loading" data-preview-loading>Memuat preview…</div></div>
            <div class="preview-caption"><x-icon name="eye"/><span data-preview-caption>Pilih elemen untuk melihat posisinya di website.</span></div>
            <p class="preview-note">Preview belum diterbitkan. Klik <strong>Simpan perubahan</strong> untuk memperbarui website.</p>
        </aside>
    </div>
</main>
@endsection

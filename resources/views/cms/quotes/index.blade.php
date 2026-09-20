@extends('cms.layout')

@section('title', 'Permintaan penawaran')

@section('content')
<main class="records-page">
    <div class="page-heading">
        <div><p class="eyebrow">LEAD MASUK</p><h1>Permintaan penawaran</h1><p>Tinjau kebutuhan calon pelanggan dan perbarui progres tindak lanjut.</p></div>
        <form method="get" class="record-filter"><label class="sr-only" for="quote-status">Filter status</label><select id="quote-status" name="status">@foreach(['all'=>'Semua status','new'=>'Baru','contacted'=>'Dihubungi','closed'=>'Selesai'] as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select><button class="secondary">Terapkan</button></form>
    </div>
    @if(session('status'))<div class="alert success" role="status"><x-icon name="check"/>{{ session('status') }}</div>@endif
    <section class="records-panel">
        @forelse($quotes as $quote)
            <article class="quote-record">
                <div class="record-primary"><div class="record-title"><span class="avatar">{{ mb_substr($quote->name, 0, 1) }}</span><div><h2>{{ $quote->name }}</h2><p>{{ $quote->company ?: 'Tanpa nama perusahaan' }} · {{ $quote->created_at->format('d M Y, H:i') }}</p></div></div><span class="status-label status-{{ $quote->status }}">{{ ['new'=>'Baru','contacted'=>'Dihubungi','closed'=>'Selesai'][$quote->status] }}</span></div>
                <div class="record-contact"><a href="mailto:{{ $quote->email }}">{{ $quote->email }}</a>@if($quote->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $quote->phone) }}">{{ $quote->phone }}</a>@endif</div>
                <div class="record-details"><div><span>Kebutuhan</span><strong>{{ ['chemical'=>'Bahan baku kimia','industrial-salt'=>'Garam industri','laboratory-chemical'=>'Bahan kimia laboratorium','laboratory-solution'=>'Solusi laboratorium','other'=>'Lainnya'][$quote->interest] }}</strong></div><div><span>Jumlah</span><strong>{{ $quote->quantity ?: 'Belum disebutkan' }}</strong></div></div>
                <p class="record-message">{{ $quote->message }}</p>
                <div class="record-actions"><form method="post" action="{{ route('cms.quotes.update', $quote) }}">@csrf @method('PATCH')<label class="sr-only" for="status-{{ $quote->id }}">Status {{ $quote->name }}</label><select id="status-{{ $quote->id }}" name="status">@foreach(['new'=>'Baru','contacted'=>'Dihubungi','closed'=>'Selesai'] as $value => $label)<option value="{{ $value }}" @selected($quote->status === $value)>{{ $label }}</option>@endforeach</select><button class="secondary">Simpan status</button></form><form method="post" action="{{ route('cms.quotes.destroy', $quote) }}" onsubmit="return confirm('Hapus permintaan penawaran ini?')">@csrf @method('DELETE')<button class="icon-button danger-button" title="Hapus permintaan" aria-label="Hapus permintaan {{ $quote->name }}"><x-icon name="trash"/></button></form></div>
            </article>
        @empty
            <div class="empty-state"><x-icon name="inbox"/><h3>Belum ada permintaan pada filter ini</h3><p>Permintaan dari formulir website akan muncul di sini.</p></div>
        @endforelse
        @if($quotes->hasPages())<div class="pagination">{{ $quotes->links() }}</div>@endif
    </section>
</main>
@endsection

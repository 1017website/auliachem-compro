@extends('cms.layout')
@section('title', 'Analytics pengunjung')
@section('content')
<main class="analytics-page">
    <div class="page-heading"><div><div class="eyebrow"><span class="status-dot"></span> WEBSITE OVERVIEW</div><h1>Kenali pengunjung Anda.</h1><p>Ringkasan aktivitas dan tindakan penting di website Auliachem.</p></div><div class="analytics-actions"><a class="secondary" href="{{ route('cms.analytics.export',['days'=>$days]) }}"><x-icon name="file-download"/>Ekspor CSV</a><form method="get" class="date-filter"><x-icon name="calendar"/><label class="sr-only" for="days">Rentang waktu</label><select name="days" id="days">@foreach([7,30,90] as $range)<option value="{{ $range }}" @selected($days === $range)>{{ $range }} hari terakhir</option>@endforeach</select><button class="secondary" type="submit">Terapkan</button></form></div></div>
    <div class="stats-grid">
        @foreach([['label'=>'Halaman dilihat','value'=>$total,'icon'=>'eye','note'=>$days.' hari terakhir'],['label'=>'Pengunjung unik (sesi)','value'=>$visitors,'icon'=>'users','note'=>'Berdasarkan sesi browser'],['label'=>'Kunjungan hari ini','value'=>$today,'icon'=>'calendar','note'=>now()->format('d M Y').' · '.config('app.timezone')]] as $stat)
        <article class="stat-card"><div><span>{{ $stat['label'] }}</span><x-icon :name="$stat['icon']"/></div><strong>{{ number_format($stat['value'],0,',','.') }}</strong><small>{{ $stat['note'] }}</small></article>
        @endforeach
    </div>
    <section class="chart-card conversion-card"><div class="card-heading"><div><h2>Konversi</h2><p>Form penawaran, unduhan dokumen, dan klik kontak.</p></div><strong class="conversion-total">{{ number_format($conversionTotal,0,',','.') }}</strong></div><div class="conversion-grid">@forelse($conversionTypes as $event)<div><span>{{ ['quote_submitted'=>'Form penawaran','document_download'=>'Unduh dokumen','email_click'=>'Klik email','phone_click'=>'Klik telepon','whatsapp_click'=>'Klik WhatsApp'][$event->label] ?? $event->label }}</span><strong>{{ $event->total }}</strong></div>@empty<p class="help">Belum ada konversi pada periode ini.</p>@endforelse</div></section>
    <section class="chart-card"><div class="card-heading"><div><h2>Aktivitas pengunjung</h2><p>Jumlah halaman yang dibuka per hari</p></div><span class="chart-legend"><i></i> Page views</span></div>
        @if($total === 0)<div class="empty-state"><x-icon name="chart"/><h3>Belum ada kunjungan pada periode ini</h3><p>Grafik akan terisi saat pengunjung membuka website. Kunjungan admin dan preview tidak dihitung.</p></div>@else
        <div class="traffic-chart" role="img" aria-label="Grafik kunjungan harian, total {{ $total }} kunjungan dalam {{ $days }} hari"><div class="bars">@foreach($series as $point)<div class="bar-column"><span class="chart-bar" style="height:{{ $point['total'] > 0 ? max(2, $point['total'] / max(1, $series->max('total')) * 100) : 0 }}%" title="{{ $point['date'] }}: {{ $point['total'] }} kunjungan"></span></div>@endforeach</div><div class="chart-axis"><span>{{ $series->first()['date'] }}</span><span>{{ $series->last()['date'] }}</span></div></div>
        <details class="chart-data"><summary>Lihat data harian</summary><div class="data-table"><table><thead><tr><th>Tanggal</th><th>Halaman dilihat</th></tr></thead><tbody>@foreach($series as $point)<tr><td>{{ $point['date'] }}</td><td>{{ $point['total'] }}</td></tr>@endforeach</tbody></table></div></details>
        @endif
    </section>
    <div class="breakdown-grid">@foreach([['title'=>'Perangkat','icon'=>'desktop','rows'=>$devices],['title'=>'Bahasa halaman','icon'=>'globe','rows'=>$languages],['title'=>'Sumber kunjungan','icon'=>'arrow','rows'=>$sources]] as $group)
        <section class="breakdown-card"><h2><x-icon :name="$group['icon']"/>{{ $group['title'] }}</h2>@forelse($group['rows'] as $row)<div class="breakdown-row"><div><span>{{ ['id'=>'Indonesia','en'=>'English','zh'=>'Mandarin'][$row->label] ?? ($row->label ?: 'Langsung / internal') }}</span><strong>{{ $row->total }}</strong></div><div class="meter"><span style="width:{{ $row->total / max(1,$total) * 100 }}%"></span></div></div>@empty<p class="help">Belum ada data pengunjung.</p>@endforelse</section>
    @endforeach</div>
    <p class="analytics-note">Data dicatat sejak analytics diaktifkan. Satu sesi browser dihitung sebagai satu pengunjung unik pada periode terpilih. Bot umum, admin yang sedang login, dan live preview dikecualikan. Tidak menyimpan alamat IP atau URL referrer lengkap.</p>
</main>
@endsection

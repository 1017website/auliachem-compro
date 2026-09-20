<!doctype html>
<html lang="id"><body style="font-family:Arial,sans-serif;color:#17324a">
<h1 style="font-size:22px">Permintaan penawaran baru</h1>
<p><strong>Nama:</strong> {{ $quote->name }}</p>
<p><strong>Perusahaan:</strong> {{ $quote->company ?: '—' }}</p>
<p><strong>Email:</strong> {{ $quote->email }}</p>
<p><strong>Telepon:</strong> {{ $quote->phone ?: '—' }}</p>
<p><strong>Kebutuhan:</strong> {{ $quote->interest }}</p>
<p><strong>Jumlah:</strong> {{ $quote->quantity ?: '—' }}</p>
<p><strong>Pesan:</strong><br>{{ $quote->message }}</p>
<p><a href="{{ route('cms.quotes.index') }}">Buka CMS Auliachem</a></p>
</body></html>

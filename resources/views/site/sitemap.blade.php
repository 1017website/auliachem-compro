{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach(['id','en','zh'] as $locale)<url><loc>{{ route('home',['lang'=>$locale]) }}</loc><lastmod>{{ \Illuminate\Support\Carbon::parse($updated)->toAtomString() }}</lastmod><changefreq>weekly</changefreq><priority>{{ $locale==='id'?'1.0':'0.8' }}</priority>@foreach(['id','en','zh'] as $alternate)<xhtml:link rel="alternate" hreflang="{{ $alternate==='zh'?'zh-CN':$alternate }}" href="{{ route('home',['lang'=>$alternate]) }}"/>@endforeach</url>@endforeach
</urlset>

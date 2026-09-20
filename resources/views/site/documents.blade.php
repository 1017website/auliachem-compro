@php($labels = ['id' => ['title' => 'Dokumen tersedia', 'intro' => 'Unduh dokumen teknis dan katalog yang dipublikasikan Auliachem.', 'download' => 'Unduh PDF'], 'en' => ['title' => 'Available documents', 'intro' => 'Download technical documents and catalogs published by Auliachem.', 'download' => 'Download PDF'], 'zh' => ['title' => '可用文件', 'intro' => '下载 Auliachem 发布的技术文件和目录。', 'download' => '下载 PDF']][$locale])
<div class="container public-documents">
    <div class="documents-heading"><h3>{{ $labels['title'] }}</h3><p>{{ $labels['intro'] }}</p></div>
    <div class="document-list">
        @foreach($documents as $document)
            <article class="document-item"><span class="document-type">{{ strtoupper($document->category) }}</span><div><h4>{{ $document->title }}</h4>@if($document->description)<p>{{ $document->description }}</p>@endif</div><a href="{{ route('documents.download', $document) }}">{{ $labels['download'] }}</a></article>
        @endforeach
    </div>
</div>

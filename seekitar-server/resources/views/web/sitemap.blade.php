{{-- Deklarasi XML tidak bisa ditulis apa adanya: PHP membaca tag pembuka
     "<" + "?xml" sebagai awal blok kode dan memicu parse error. Karena itu
     string-nya dipecah lalu dicetak dari blok kode. --}}
@php
    echo '<'.'?xml version="1.0" encoding="UTF-8"?'.'>', PHP_EOL;
@endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($pages as $page)
    <url>
        <loc>{{ $page['loc'] }}</loc>
        <changefreq>{{ $page['freq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
@endforeach
</urlset>

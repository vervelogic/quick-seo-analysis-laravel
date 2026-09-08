{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('app.name') }} Blog</title>
        <link>{{ route('blog.index') }}</link>
        <description>SEO, AI visibility, GEO, AEO, and content strategy insights from {{ config('app.name') }}.</description>
        <language>en-us</language>
        <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml" />
        <lastBuildDate>{{ optional($entries->first()?->published_at)->toRssString() ?? now()->toRssString() }}</lastBuildDate>
        @foreach ($entries as $entry)
            <item>
                <title><![CDATA[{{ $entry->title }}]]></title>
                <link>{{ url($entry->publicPath()) }}</link>
                <guid>{{ url($entry->publicPath()) }}</guid>
                <pubDate>{{ optional($entry->published_at)->toRssString() }}</pubDate>
                <description><![CDATA[{{ $entry->excerpt }}]]></description>
                @if ($entry->category)
                    <category><![CDATA[{{ $entry->category->name }}]]></category>
                @endif
            </item>
        @endforeach
    </channel>
</rss>

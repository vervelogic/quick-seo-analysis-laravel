<?php

namespace App\Services\Content;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Str as SupportStr;

class OldBlogCrawler
{
    public function crawl(
        string $baseUrl = 'https://www.quickseoanalysis.com',
        int $maxPages = 250,
        int $timeout = 10,
        int $maxDepth = 2,
        ?callable $progress = null
    ): array {
        $queue = collect([
            ['url' => rtrim($baseUrl, '/'), 'depth' => 0],
            ['url' => rtrim($baseUrl, '/').'/Blog', 'depth' => 0],
            ['url' => rtrim($baseUrl, '/').'/blog', 'depth' => 0],
            ['url' => rtrim($baseUrl, '/').'/blog/category/seo', 'depth' => 1],
        ]);

        $visited = [];
        $posts = [];
        $categories = [];
        $tags = [];
        $failed = [];
        $duplicates = [];
        $skippedDepth = [];

        while ($queue->isNotEmpty() && count($visited) < $maxPages) {
            $current = $queue->shift();
            $url = (string) ($current['url'] ?? '');
            $depth = (int) ($current['depth'] ?? 0);

            if ($url === '') {
                continue;
            }

            if (isset($visited[$url])) {
                $duplicates[] = $url;
                continue;
            }

            if ($depth > $maxDepth) {
                $skippedDepth[] = $url;
                continue;
            }

            $visited[$url] = true;
            if ($progress) {
                $progress('visiting', [
                    'url' => $url,
                    'depth' => $depth,
                    'visited' => count($visited),
                    'queue' => $queue->count(),
                    'posts' => count($posts),
                ]);
            }

            $response = $this->request($url, $timeout);

            if (! $response['ok']) {
                $failed[] = ['url' => $url, 'status' => $response['status']];
                if ($progress) {
                    $progress('failed', [
                        'url' => $url,
                        'depth' => $depth,
                        'status' => $response['status'],
                    ]);
                }
                continue;
            }

            $html = $response['body'];
            $document = $this->parseDocument($html);
            $links = $document
                ? $this->extractLinks($document['xpath'])
                : $this->extractLinksByRegex($html);

            foreach ($links as $href) {
                $absolute = $this->absoluteUrl($baseUrl, html_entity_decode($href));

                if (! $absolute || ! str_contains($absolute, 'quickseoanalysis.com')) {
                    continue;
                }

                if (preg_match('~\/blog\/category\/([^\/?#]+)~i', $absolute, $categoryMatch)) {
                    $categories[] = Str::of($categoryMatch[1])->replace('-', ' ')->title()->toString();
                    if (! isset($visited[$absolute])) {
                        $queue->push(['url' => $absolute, 'depth' => $depth + 1]);
                    }
                    continue;
                }

                if (preg_match('~\/blog\/tag\/([^\/?#]+)~i', $absolute, $tagMatch)) {
                    $tags[] = Str::of($tagMatch[1])->replace('-', ' ')->title()->toString();
                    if (! isset($visited[$absolute])) {
                        $queue->push(['url' => $absolute, 'depth' => $depth + 1]);
                    }
                    continue;
                }

                if ($this->isBlogPostUrl($absolute)) {
                    if (! in_array($absolute, $posts, true)) {
                        $posts[] = $absolute;
                        if ($progress) {
                            $progress('discovered_post', [
                                'url' => $absolute,
                                'depth' => $depth + 1,
                                'posts' => count($posts),
                            ]);
                        }
                    }
                    continue;
                }

                if ($this->isBlogIndexUrl($absolute) && ! isset($visited[$absolute])) {
                    $queue->push(['url' => $absolute, 'depth' => $depth + 1]);
                }
            }

            if (str_contains($html, 'View More')) {
                foreach ($this->guessAjaxEndpoints($url) as $candidate) {
                    if (! isset($visited[$candidate])) {
                        $queue->push(['url' => $candidate, 'depth' => $depth + 1]);
                    }
                }
            }
        }

        $posts = array_values(array_unique($posts));
        sort($posts);

        return [
            'total_blogs_found' => count($posts),
            'blog_urls' => $posts,
            'categories' => array_values(array_unique(array_filter($categories))),
            'tags' => array_values(array_unique(array_filter($tags))),
            'broken_urls' => $failed,
            'duplicate_urls' => array_values(array_unique($duplicates)),
            'failed_urls' => $failed,
            'visited_urls' => array_keys($visited),
            'skipped_depth_urls' => array_values(array_unique($skippedDepth)),
            'crawl_limits' => [
                'max_pages' => $maxPages,
                'max_depth' => $maxDepth,
                'timeout' => $timeout,
            ],
        ];
    }

    public function fetchPost(string $url, int $timeout = 10): array
    {
        $response = $this->request($url, $timeout);

        if (! $response['ok']) {
            return [
                'ok' => false,
                'url' => $url,
                'status' => $response['status'],
            ];
        }

        $html = $response['body'];
        $document = $this->parseDocument($html);
        $xpath = $document['xpath'] ?? null;

        $title = $xpath ? $this->textFirst($xpath, ['//head/title']) : $this->extractRegex('/<title>(.*?)<\/title>/is', $html);
        $metaDescription = $this->metaContent($xpath, ['description']) ?? $this->extractRegex('/<meta[^>]+name="description"[^>]+content="([^"]*)"/is', $html);
        $metaKeywords = $this->metaContent($xpath, ['keywords']) ?? $this->extractRegex('/<meta[^>]+name="keywords"[^>]+content="([^"]*)"/is', $html);
        $canonical = $xpath ? $this->firstAttribute($xpath, ['//link[contains(translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"canonical")]'], 'href') : $this->extractRegex('/<link[^>]+rel="canonical"[^>]+href="([^"]*)"/is', $html);
        $ogTitle = $this->metaPropertyContent($xpath, ['og:title']) ?? $this->extractRegex('/<meta[^>]+property="og:title"[^>]+content="([^"]*)"/is', $html);
        $ogDescription = $this->metaPropertyContent($xpath, ['og:description']) ?? $this->extractRegex('/<meta[^>]+property="og:description"[^>]+content="([^"]*)"/is', $html);
        $ogImage = $this->metaPropertyContent($xpath, ['og:image']) ?? $this->extractRegex('/<meta[^>]+property="og:image"[^>]+content="([^"]*)"/is', $html);
        $twitterTitle = $this->metaContent($xpath, ['twitter:title']) ?? $this->extractRegex('/<meta[^>]+name="twitter:title"[^>]+content="([^"]*)"/is', $html);
        $twitterDescription = $this->metaContent($xpath, ['twitter:description']) ?? $this->extractRegex('/<meta[^>]+name="twitter:description"[^>]+content="([^"]*)"/is', $html);
        $twitterImage = $this->metaContent($xpath, ['twitter:image']) ?? $this->extractRegex('/<meta[^>]+name="twitter:image"[^>]+content="([^"]*)"/is', $html);

        $body = $xpath ? $this->extractMainBodyDom($xpath, $html) : $this->extractMainBody($html);
        $author = $xpath ? $this->extractAuthorDom($xpath) : $this->extractByClassFragment($html, ['author']);
        $category = $xpath ? $this->extractCategoryDom($xpath) : $this->extractCategory($html);
        $publishedDate = $xpath ? $this->extractPublishedDateDom($xpath, $html) : $this->extractDate($html, ['published', 'date', 'post-date']);
        $modifiedDate = $xpath ? $this->extractModifiedDateDom($xpath, $html) : $this->extractDate($html, ['updated', 'modified']);
        $featured = $xpath ? $this->extractFeaturedImageDom($xpath, $url) : [
            'src' => $this->extractFeaturedImage($html),
            'alt' => $this->extractFeaturedImageAlt($html),
        ];
        $featuredImage = $featured['src'] ?? null;
        $imageAlt = $featured['alt'] ?? null;
        $excerpt = $this->extractExcerpt($body);
        $tags = $xpath ? $this->extractTagsDom($xpath, (string) $metaKeywords) : $this->extractTags($html, (string) $metaKeywords);
        $missingMetadata = array_values(array_filter([
            blank($title) ? 'title' : null,
            blank($metaDescription) ? 'meta_description' : null,
            blank($publishedDate) ? 'published_at' : null,
            blank($category) ? 'category' : null,
        ]));

        return [
            'ok' => true,
            'url' => $url,
            'title' => html_entity_decode(trim(strip_tags((string) $title))),
            'slug' => trim((string) Str::of(parse_url($url, PHP_URL_PATH) ?: '')->afterLast('/')),
            'legacy_url' => $url,
            'author' => $author,
            'category' => $category,
            'tags' => $tags,
            'published_at' => $publishedDate,
            'modified_at' => $modifiedDate,
            'excerpt' => $excerpt,
            'content_html' => trim($body),
            'featured_image' => $featuredImage,
            'featured_image_alt' => $imageAlt,
            'seo_title' => $this->cleanMeta((string) ($ogTitle ?: $title)),
            'meta_description' => $this->cleanMeta((string) $metaDescription),
            'meta_keywords' => $this->cleanMeta((string) $metaKeywords),
            'canonical' => $this->cleanMeta((string) ($canonical ?: $url)),
            'og_title' => $this->cleanMeta((string) $ogTitle),
            'og_description' => $this->cleanMeta((string) $ogDescription),
            'og_image' => $this->cleanMeta((string) $ogImage),
            'twitter_title' => $this->cleanMeta((string) $twitterTitle),
            'twitter_description' => $this->cleanMeta((string) $twitterDescription),
            'twitter_image' => $this->cleanMeta((string) $twitterImage),
            'images_found' => $featuredImage ? 1 : 0,
            'missing_metadata' => $missingMetadata,
            'raw_html' => $html,
        ];
    }

    private function request(string $url, int $timeout = 10): array
    {
        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(min($timeout, 5))
                ->withHeaders([
                    'User-Agent' => config('qsa.scan_user_agent', 'QSA Blog Importer/1.0'),
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'body' => (string) $response->body(),
            ];
        } catch (\Throwable) {
            return [
                'ok' => false,
                'status' => null,
                'body' => '',
            ];
        }
    }

    private function absoluteUrl(string $baseUrl, string $href): ?string
    {
        if (blank($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
            return null;
        }

        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($href, '/');
    }

    private function isBlogPostUrl(string $url): bool
    {
        return (bool) preg_match('~\/blog\/[^\/?#]+$~i', $url)
            && ! str_contains($url, '/blog/category/')
            && ! str_contains($url, '/blog/tag/')
            && ! str_contains($url, '/blog/author/');
    }

    private function isBlogIndexUrl(string $url): bool
    {
        return in_array(rtrim($url, '/'), [
            'https://www.quickseoanalysis.com/blog',
            'https://www.quickseoanalysis.com/Blog',
        ], true)
            || str_contains($url, '/blog/category/')
            || str_contains($url, '/blog/tag/')
            || str_contains($url, '/blog/archive/');
    }

    private function guessAjaxEndpoints(string $url): array
    {
        return [
            $url.'?page=2',
            $url.'?show=all',
        ];
    }

    private function extractMainBody(string $html): string
    {
        preg_match('/<body[^>]*>(.*)<\/body>/isU', $html, $bodyMatch);

        return trim($bodyMatch[1] ?? '');
    }

    private function parseDocument(string $html): ?array
    {
        try {
            $internalErrors = libxml_use_internal_errors(true);
            $document = new DOMDocument();
            $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            if (! $loaded) {
                return null;
            }

            return [
                'document' => $document,
                'xpath' => new DOMXPath($document),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractLinks(DOMXPath $xpath): array
    {
        $hrefs = [];
        foreach ($xpath->query('//a[@href]') ?: [] as $link) {
            $href = trim((string) $link->attributes?->getNamedItem('href')?->nodeValue);
            if ($href !== '') {
                $hrefs[] = $href;
            }
        }

        return $hrefs;
    }

    private function extractLinksByRegex(string $html): array
    {
        preg_match_all('/href="([^"]+)"/i', $html, $matches);
        return $matches[1] ?? [];
    }

    private function textFirst(DOMXPath $xpath, array $queries): ?string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes && $nodes->length > 0) {
                $value = trim($nodes->item(0)?->textContent ?? '');
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function firstAttribute(DOMXPath $xpath, array $queries, string $attribute): ?string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if (! $nodes || $nodes->length === 0) {
                continue;
            }

            foreach ($nodes as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                $value = trim($node->getAttribute($attribute));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function metaContent(?DOMXPath $xpath, array $names): ?string
    {
        if (! $xpath) {
            return null;
        }

        $lower = array_map('strtolower', $names);
        foreach ($xpath->query('//meta[@name]') ?: [] as $meta) {
            if (! $meta instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($meta->getAttribute('name')), $lower, true)) {
                $content = trim($meta->getAttribute('content'));
                if ($content !== '') {
                    return $content;
                }
            }
        }

        return null;
    }

    private function metaPropertyContent(?DOMXPath $xpath, array $properties): ?string
    {
        if (! $xpath) {
            return null;
        }

        $lower = array_map('strtolower', $properties);
        foreach ($xpath->query('//meta[@property]') ?: [] as $meta) {
            if (! $meta instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($meta->getAttribute('property')), $lower, true)) {
                $content = trim($meta->getAttribute('content'));
                if ($content !== '') {
                    return $content;
                }
            }
        }

        return null;
    }

    private function extractMainBodyDom(DOMXPath $xpath, string $html): string
    {
        $queries = [
            '//article',
            '//*[contains(@class,"post-content")]',
            '//*[contains(@class,"entry-content")]',
            '//*[contains(@class,"article-content")]',
            '//*[contains(@class,"blog-detail")]',
            '//*[contains(@class,"single-post")]',
            '//main',
        ];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes && $nodes->length > 0) {
                $node = $nodes->item(0);
                if ($node instanceof DOMElement) {
                    return $this->innerHtml($node);
                }
            }
        }

        return $this->extractMainBody($html);
    }

    private function extractAuthorDom(DOMXPath $xpath): ?string
    {
        $queries = [
            '//*[@itemprop="author"]',
            '//*[contains(@class,"author")]',
            '//*[contains(@rel,"author")]',
            '//meta[@name="author"]',
        ];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if (! $nodes || $nodes->length === 0) {
                continue;
            }

            foreach ($nodes as $node) {
                $value = $node instanceof DOMElement && $node->hasAttribute('content')
                    ? trim($node->getAttribute('content'))
                    : trim(strip_tags($node->textContent ?? ''));

                if ($value !== '') {
                    return html_entity_decode($value);
                }
            }
        }

        return null;
    }

    private function extractCategoryDom(DOMXPath $xpath): ?string
    {
        $queries = [
            '//nav[contains(@class,"breadcrumb")]//a[contains(@href,"/blog/category/")]',
            '//*[contains(@class,"breadcrumb")]//a[contains(@href,"/blog/category/")]',
            '//*[contains(@class,"category")]//a[contains(@href,"/blog/category/")]',
            '//a[contains(@href,"/blog/category/")]',
        ];

        foreach ($queries as $index => $query) {
            $nodes = $xpath->query($query);
            if (! $nodes || $nodes->length === 0) {
                continue;
            }

            foreach ($nodes as $node) {
                $href = $node instanceof DOMElement ? $node->getAttribute('href') : '';
                $label = trim(strip_tags($node->textContent ?? ''));

                if ($href === '' || $label === '') {
                    continue;
                }

                if ($index >= 3 && $this->looksLikeFooterOrNav($node)) {
                    continue;
                }

                return Str::title(SupportStr::of($label)->replace('-', ' ')->toString());
            }
        }

        return null;
    }

    private function extractPublishedDateDom(DOMXPath $xpath, string $html): ?string
    {
        $schemaDate = $this->extractSchemaDate($html, 'datePublished');
        if ($schemaDate) {
            return $schemaDate;
        }

        $metaQueries = [
            '//meta[@property="article:published_time"]',
            '//meta[@itemprop="datePublished"]',
            '//meta[@name="pubdate"]',
        ];
        $metaDate = $this->firstAttribute($xpath, $metaQueries, 'content');
        if ($metaDate) {
            return $metaDate;
        }

        $timeDate = $this->firstAttribute($xpath, ['//time[@datetime]'], 'datetime');
        if ($timeDate) {
            return $timeDate;
        }

        return $this->extractDate($html, ['published', 'date', 'post-date']);
    }

    private function extractModifiedDateDom(DOMXPath $xpath, string $html): ?string
    {
        $schemaDate = $this->extractSchemaDate($html, 'dateModified');
        if ($schemaDate) {
            return $schemaDate;
        }

        $metaQueries = [
            '//meta[@property="article:modified_time"]',
            '//meta[@itemprop="dateModified"]',
        ];

        $metaDate = $this->firstAttribute($xpath, $metaQueries, 'content');
        if ($metaDate) {
            return $metaDate;
        }

        return $this->extractDate($html, ['updated', 'modified']);
    }

    private function extractFeaturedImageDom(DOMXPath $xpath, string $pageUrl): array
    {
        $priority = [
            $this->metaPropertyContent($xpath, ['og:image']),
            $this->metaContent($xpath, ['twitter:image']),
            $this->firstMeaningfulImage($xpath, [
                '//article//img',
                '//*[contains(@class,"featured")]//img',
                '//*[contains(@class,"post-thumbnail")]//img',
                '//*[contains(@class,"entry-content")]//img',
                '//main//img',
            ], $pageUrl),
        ];

        foreach ($priority as $candidate) {
            if (is_array($candidate) && ! empty($candidate['src'])) {
                return $candidate;
            }

            if (is_string($candidate) && $candidate !== '') {
                return ['src' => $candidate, 'alt' => null];
            }
        }

        return ['src' => null, 'alt' => null];
    }

    private function firstMeaningfulImage(DOMXPath $xpath, array $queries, string $pageUrl): ?array
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if (! $nodes) {
                continue;
            }

            foreach ($nodes as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                $src = trim($node->getAttribute('src'));
                if ($src === '') {
                    continue;
                }

                $src = $this->absoluteUrl($pageUrl, $src) ?? $src;
                $alt = trim($node->getAttribute('alt'));

                if ($this->ignoreImage($node, $src, $alt)) {
                    continue;
                }

                return [
                    'src' => $this->stripTrackingFromUrl($src),
                    'alt' => $alt !== '' ? html_entity_decode($alt) : null,
                ];
            }
        }

        return null;
    }

    private function ignoreImage(DOMElement $image, string $src, string $alt): bool
    {
        $haystack = strtolower($src.' '.$alt.' '.$image->getAttribute('class'));

        foreach (['logo', 'icon', 'avatar', 'author', 'profile', 'sprite'] as $term) {
            if (str_contains($haystack, $term)) {
                return true;
            }
        }

        $width = (int) $image->getAttribute('width');
        $height = (int) $image->getAttribute('height');

        if (($width > 0 && $width < 120) || ($height > 0 && $height < 120)) {
            return true;
        }

        return false;
    }

    private function extractTagsDom(DOMXPath $xpath, string $keywordMeta): array
    {
        $tags = [];
        $queries = [
            '//*[contains(@class,"tags")]//a',
            '//*[contains(@class,"tag")]//a[contains(@href,"/blog/tag/")]',
            '//a[contains(@href,"/blog/tag/")]',
        ];

        foreach ($queries as $query) {
            foreach ($xpath->query($query) ?: [] as $node) {
                $label = trim(strip_tags($node->textContent ?? ''));
                if ($label !== '') {
                    $tags[] = $this->normalizeTagLabel($label);
                }
            }
        }

        if ($tags === [] && $keywordMeta !== '') {
            foreach (explode(',', $keywordMeta) as $keyword) {
                $keyword = trim(html_entity_decode($keyword));
                if ($keyword !== '' && str_word_count($keyword) <= 5) {
                    $tags[] = $this->normalizeTagLabel($keyword);
                }
            }
        }

        return array_values(array_unique(array_filter($tags)));
    }

    private function normalizeTagLabel(string $value): string
    {
        return Str::title(SupportStr::of($value)->replace('-', ' ')->squish()->toString());
    }

    private function extractSchemaDate(string $html, string $field): ?string
    {
        if (preg_match('/"'.preg_quote($field, '/').'"\s*:\s*"([^"]+)"/i', $html, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    private function looksLikeFooterOrNav(DOMNode $node): bool
    {
        $current = $node;
        while ($current instanceof DOMNode) {
            if ($current instanceof DOMElement) {
                $class = strtolower($current->getAttribute('class'));
                $tag = strtolower($current->tagName);
                if ($tag === 'footer' || $tag === 'nav' || str_contains($class, 'footer') || str_contains($class, 'nav')) {
                    return true;
                }
            }
            $current = $current->parentNode;
        }

        return false;
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument?->saveHTML($child) ?? '';
        }

        return trim($html);
    }

    private function extractRegex(string $pattern, string $html): ?string
    {
        if (preg_match($pattern, $html, $match)) {
            return trim($match[1] ?? '');
        }

        return null;
    }

    private function extractByClassFragment(string $html, array $fragments): ?string
    {
        foreach ($fragments as $fragment) {
            if (preg_match('/<[^>]*class="[^"]*'.$fragment.'[^"]*"[^>]*>(.*?)<\/[^>]+>/is', $html, $match)) {
                return trim(strip_tags($match[1]));
            }
        }

        return null;
    }

    private function extractCategory(string $html): ?string
    {
        if (preg_match('#/blog/category/([^"\']+)#i', $html, $match)) {
            return Str::of($match[1])->replace('-', ' ')->title()->toString();
        }

        return null;
    }

    private function extractDate(string $html, array $classHints): ?string
    {
        foreach ($classHints as $hint) {
            if (preg_match('/<[^>]*class="[^"]*'.$hint.'[^"]*"[^>]*>(.*?)<\/[^>]+>/is', $html, $match)) {
                $value = trim(strip_tags($match[1]));

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function extractFeaturedImage(string $html): ?string
    {
        if (preg_match('/<img[^>]+src="([^"]+)"[^>]*>/is', $html, $match)) {
            return $match[1];
        }

        return null;
    }

    private function extractFeaturedImageAlt(string $html): ?string
    {
        if (preg_match('/<img[^>]+alt="([^"]*)"[^>]*>/is', $html, $match)) {
            return html_entity_decode(trim($match[1]));
        }

        return null;
    }

    private function extractExcerpt(string $body): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($body)));

        return Str::limit($text, 220, '');
    }

    private function extractTags(string $html, string $keywordMeta): array
    {
        $tags = [];

        preg_match_all('#/blog/tag/([^"\']+)#i', $html, $matches);

        foreach ($matches[1] ?? [] as $tag) {
            $tags[] = Str::of($tag)->replace('-', ' ')->title()->toString();
        }

        if ($tags === [] && $keywordMeta !== '') {
            foreach (explode(',', $keywordMeta) as $keyword) {
                $keyword = trim(html_entity_decode($keyword));

                if ($keyword !== '' && str_word_count($keyword) <= 5) {
                    $tags[] = Str::title($keyword);
                }
            }
        }

        return array_values(array_unique(array_filter($tags)));
    }

    private function cleanMeta(string $value): ?string
    {
        $value = html_entity_decode(trim(strip_tags($value)));

        return $value !== '' ? $value : null;
    }

    private function stripTrackingFromUrl(string $url): string
    {
        $parts = parse_url($url);
        if (! $parts || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $params = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $params);
            foreach (array_keys($params) as $key) {
                if (preg_match('/^(utm_|gclid|fbclid|msclkid|gad_)/i', $key)) {
                    unset($params[$key]);
                }
            }
        }

        $rebuilt = $parts['scheme'].'://'.$parts['host'].($parts['path'] ?? '');
        if ($params !== []) {
            $rebuilt .= '?'.http_build_query($params);
        }
        if (! empty($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return $rebuilt;
    }
}

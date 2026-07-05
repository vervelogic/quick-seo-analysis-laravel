<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OldBlogCrawler
{
    public function crawl(string $baseUrl = 'https://www.quickseoanalysis.com', int $maxPages = 250): array
    {
        $queue = collect([
            rtrim($baseUrl, '/'),
            rtrim($baseUrl, '/').'/Blog',
            rtrim($baseUrl, '/').'/blog',
            rtrim($baseUrl, '/').'/blog/category/seo',
        ]);

        $visited = [];
        $posts = [];
        $categories = [];
        $tags = [];
        $failed = [];
        $duplicates = [];

        while ($queue->isNotEmpty() && count($visited) < $maxPages) {
            $url = $queue->shift();

            if (isset($visited[$url])) {
                $duplicates[] = $url;
                continue;
            }

            $visited[$url] = true;
            $response = $this->request($url);

            if (! $response['ok']) {
                $failed[] = ['url' => $url, 'status' => $response['status']];
                continue;
            }

            $html = $response['body'];
            preg_match_all('/href="([^"]+)"/i', $html, $matches);

            foreach ($matches[1] ?? [] as $href) {
                $absolute = $this->absoluteUrl($baseUrl, html_entity_decode($href));

                if (! $absolute || ! str_contains($absolute, 'quickseoanalysis.com')) {
                    continue;
                }

                if (preg_match('#/blog/category/([^/?#]+)#i', $absolute, $categoryMatch)) {
                    $categories[] = Str::of($categoryMatch[1])->replace('-', ' ')->title()->toString();
                    $queue->push($absolute);
                    continue;
                }

                if (preg_match('#/blog/tag/([^/?#]+)#i', $absolute, $tagMatch)) {
                    $tags[] = Str::of($tagMatch[1])->replace('-', ' ')->title()->toString();
                    $queue->push($absolute);
                    continue;
                }

                if ($this->isBlogPostUrl($absolute)) {
                    $posts[] = $absolute;
                    continue;
                }

                if ($this->isBlogIndexUrl($absolute)) {
                    $queue->push($absolute);
                }
            }

            if (str_contains($html, 'View More')) {
                foreach ($this->guessAjaxEndpoints($url) as $candidate) {
                    if (! isset($visited[$candidate])) {
                        $queue->push($candidate);
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
        ];
    }

    public function fetchPost(string $url): array
    {
        $response = $this->request($url);

        if (! $response['ok']) {
            return [
                'ok' => false,
                'url' => $url,
                'status' => $response['status'],
            ];
        }

        $html = $response['body'];

        preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatch);
        preg_match('/<meta[^>]+name="description"[^>]+content="([^"]*)"/is', $html, $descMatch);
        preg_match('/<meta[^>]+name="keywords"[^>]+content="([^"]*)"/is', $html, $keywordsMatch);
        preg_match('/<link[^>]+rel="canonical"[^>]+href="([^"]*)"/is', $html, $canonicalMatch);
        preg_match('/<meta[^>]+property="og:title"[^>]+content="([^"]*)"/is', $html, $ogTitleMatch);
        preg_match('/<meta[^>]+property="og:description"[^>]+content="([^"]*)"/is', $html, $ogDescriptionMatch);
        preg_match('/<meta[^>]+property="og:image"[^>]+content="([^"]*)"/is', $html, $ogImageMatch);
        preg_match('/<meta[^>]+name="twitter:title"[^>]+content="([^"]*)"/is', $html, $twitterTitleMatch);
        preg_match('/<meta[^>]+name="twitter:description"[^>]+content="([^"]*)"/is', $html, $twitterDescriptionMatch);
        preg_match('/<meta[^>]+name="twitter:image"[^>]+content="([^"]*)"/is', $html, $twitterImageMatch);
        preg_match('/<article[^>]*>(.*)<\/article>/isU', $html, $articleMatch);

        $body = $articleMatch[1] ?? $this->extractMainBody($html);
        $author = $this->extractByClassFragment($html, ['author']);
        $category = $this->extractCategory($html);
        $publishedDate = $this->extractDate($html, ['published', 'date', 'post-date']);
        $modifiedDate = $this->extractDate($html, ['updated', 'modified']);
        $featuredImage = $this->extractFeaturedImage($html);
        $imageAlt = $this->extractFeaturedImageAlt($html);
        $excerpt = $this->extractExcerpt($body);

        return [
            'ok' => true,
            'url' => $url,
            'title' => html_entity_decode(trim(strip_tags($titleMatch[1] ?? ''))),
            'slug' => trim((string) Str::of(parse_url($url, PHP_URL_PATH) ?: '')->afterLast('/')),
            'legacy_url' => $url,
            'author' => $author,
            'category' => $category,
            'tags' => $this->extractTags($html, $keywordsMatch[1] ?? ''),
            'published_at' => $publishedDate,
            'modified_at' => $modifiedDate,
            'excerpt' => $excerpt,
            'content_html' => trim($body),
            'featured_image' => $featuredImage,
            'featured_image_alt' => $imageAlt,
            'seo_title' => $this->cleanMeta($ogTitleMatch[1] ?? ($titleMatch[1] ?? '')),
            'meta_description' => $this->cleanMeta($descMatch[1] ?? ''),
            'meta_keywords' => $this->cleanMeta($keywordsMatch[1] ?? ''),
            'canonical' => $this->cleanMeta($canonicalMatch[1] ?? $url),
            'og_title' => $this->cleanMeta($ogTitleMatch[1] ?? ''),
            'og_description' => $this->cleanMeta($ogDescriptionMatch[1] ?? ''),
            'og_image' => $this->cleanMeta($ogImageMatch[1] ?? ''),
            'twitter_title' => $this->cleanMeta($twitterTitleMatch[1] ?? ''),
            'twitter_description' => $this->cleanMeta($twitterDescriptionMatch[1] ?? ''),
            'twitter_image' => $this->cleanMeta($twitterImageMatch[1] ?? ''),
            'raw_html' => $html,
        ];
    }

    private function request(string $url): array
    {
        try {
            $response = Http::timeout(20)
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
        return (bool) preg_match('#/blog/[^/?#]+$#i', $url)
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
}

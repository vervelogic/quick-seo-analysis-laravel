<?php

namespace App\Services\Content;

use App\Models\ContentEntry;

class ContentSchemaBuilder
{
    public function build(ContentEntry $entry): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $entry->schema_type ?: 'BlogPosting',
            'headline' => $entry->title,
            'description' => $entry->seoDescription(),
            'url' => url($entry->publicPath()),
            'datePublished' => optional($entry->published_at)->toAtomString(),
            'dateModified' => optional($entry->modified_at ?: $entry->updated_at)->toAtomString(),
            'articleSection' => $entry->category?->name,
            'keywords' => $entry->meta_keywords,
            'author' => [
                '@type' => 'Person',
                'name' => $entry->author_name ?: ($entry->author?->name ?: config('app.name')),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => route('home'),
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url($entry->publicPath()),
            ],
            'image' => $entry->og_image ?: $entry->featured_image,
        ];

        if ($entry->schema_type === 'FAQPage') {
            $questions = $this->faqItems($entry->content);
            $schema['mainEntity'] = $questions;
        }

        if ($entry->schema_type === 'HowTo') {
            $schema['step'] = $this->howToSteps($entry->content);
        }

        return array_filter($schema, fn ($value) => ! is_null($value) && $value !== '');
    }

    public function buildJson(ContentEntry $entry): string
    {
        return (string) json_encode(
            $this->build($entry),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    private function faqItems(?string $content): array
    {
        if (blank($content)) {
            return [];
        }

        preg_match_all('/<h[2-4][^>]*>(.*?)<\/h[2-4]>\s*<p[^>]*>(.*?)<\/p>/is', $content, $matches, PREG_SET_ORDER);

        $items = [];

        foreach ($matches as $match) {
            $question = trim(strip_tags($match[1] ?? ''));
            $answer = trim(strip_tags($match[2] ?? ''));

            if (! str_contains($question, '?') || blank($answer)) {
                continue;
            }

            $items[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        return array_slice($items, 0, 10);
    }

    private function howToSteps(?string $content): array
    {
        if (blank($content)) {
            return [];
        }

        preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $content, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $step) => trim(strip_tags($step)))
            ->filter()
            ->take(10)
            ->map(fn (string $step) => [
                '@type' => 'HowToStep',
                'text' => $step,
            ])
            ->values()
            ->all();
    }
}

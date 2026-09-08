<?php

namespace App\Models;

use App\Services\Content\ContentSchemaBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ContentEntry extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_UNPUBLISHED = 'unpublished';
    public const STATUS_SCHEDULED = 'scheduled';

    protected $fillable = [
        'company_id',
        'content_category_id',
        'author_user_id',
        'type',
        'title',
        'slug',
        'legacy_url',
        'author_name',
        'excerpt',
        'content',
        'featured_image',
        'featured_image_alt',
        'status',
        'is_featured',
        'scheduled_for',
        'published_at',
        'modified_at',
        'word_count',
        'reading_time_minutes',
        'seo_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_type',
        'generated_json_ld',
        'custom_json_ld',
        'legacy_metadata',
        'import_metadata',
        'imported_at',
        'last_crawled_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
            'modified_at' => 'datetime',
            'imported_at' => 'datetime',
            'last_crawled_at' => 'datetime',
            'legacy_metadata' => 'array',
            'import_metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ContentEntry $entry): void {
            if (blank($entry->slug)) {
                $entry->slug = static::uniqueSlug($entry->title, $entry->type, $entry->id);
            }

            [$wordCount, $readingTime] = static::measureContent((string) $entry->content);
            $entry->word_count = $wordCount;
            $entry->reading_time_minutes = $readingTime;

            if ($entry->status === static::STATUS_SCHEDULED && $entry->scheduled_for instanceof Carbon && $entry->scheduled_for->isPast()) {
                $entry->status = static::STATUS_PUBLISHED;
            }

            if ($entry->status === static::STATUS_PUBLISHED && blank($entry->published_at)) {
                $entry->published_at = now();
            }

            if (blank($entry->modified_at) && $entry->published_at instanceof Carbon) {
                $entry->modified_at = $entry->published_at->copy();
            }

            $entry->generated_json_ld = app(ContentSchemaBuilder::class)->buildJson($entry);
        });

        static::updated(function (ContentEntry $entry): void {
            if (! $entry->wasChanged('slug')) {
                return;
            }

            $oldSlug = $entry->getOriginal('slug');

            if (blank($oldSlug) || $oldSlug === $entry->slug) {
                return;
            }

            ContentRedirect::firstOrCreate(
                ['from_path' => '/blog/'.$oldSlug],
                [
                    'content_entry_id' => $entry->id,
                    'to_path' => $entry->publicPath(),
                    'status_code' => 301,
                ],
            );
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'content_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContentTag::class, 'content_entry_tag')->withTimestamps();
    }

    public function redirects(): HasMany
    {
        return $this->hasMany(ContentRedirect::class);
    }

    public function scopeBlogs(Builder $query): Builder
    {
        return $query->where('type', 'blog');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', static::STATUS_PUBLISHED)
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->where('title', 'like', '%'.$term.'%')
                ->orWhere('excerpt', 'like', '%'.$term.'%')
                ->orWhere('content', 'like', '%'.$term.'%');
        });
    }

    public function publicPath(): string
    {
        return '/blog/'.$this->slug;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === static::STATUS_PUBLISHED
            && (! $this->published_at || $this->published_at->lte(now()));
    }

    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function seoDescription(): string
    {
        return $this->meta_description ?: Str::limit(strip_tags((string) ($this->excerpt ?: $this->content)), 160, '');
    }

    public function resolvedCanonicalUrl(): string
    {
        return $this->canonical_url ?: url($this->publicPath());
    }

    public static function uniqueSlug(string $title, string $type = 'blog', ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'entry';
        $slug = $base;
        $suffix = 1;

        while (static::query()
            ->where('type', $type)
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $builder) => $builder->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public static function measureContent(string $content): array
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
        $wordCount = str_word_count($text);
        $readingTime = max(1, (int) ceil($wordCount / 200));

        return [$wordCount, $readingTime];
    }
}

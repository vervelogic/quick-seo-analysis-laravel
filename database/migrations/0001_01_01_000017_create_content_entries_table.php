<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('content_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('blog');
            $table->string('title');
            $table->string('slug');
            $table->string('legacy_url')->nullable();
            $table->string('author_name')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('modified_at')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('reading_time_minutes')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('schema_type')->default('BlogPosting');
            $table->longText('generated_json_ld')->nullable();
            $table->longText('custom_json_ld')->nullable();
            $table->json('legacy_metadata')->nullable();
            $table->json('import_metadata')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('last_crawled_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['type', 'slug']);
            $table->index(['type', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entries');
    }
};

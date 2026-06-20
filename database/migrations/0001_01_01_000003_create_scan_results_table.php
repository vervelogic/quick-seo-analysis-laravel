<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->boolean('is_reachable')->default(false);
            $table->boolean('uses_https')->default(false);
            $table->text('title')->nullable();
            $table->unsignedSmallInteger('title_length')->default(0);
            $table->text('meta_description')->nullable();
            $table->unsignedSmallInteger('meta_description_length')->default(0);
            $table->unsignedSmallInteger('h1_count')->default(0);
            $table->text('canonical')->nullable();
            $table->string('robots_meta')->nullable();
            $table->unsignedInteger('page_size_bytes')->default(0);
            $table->unsignedInteger('response_time_ms')->default(0);
            $table->unsignedInteger('internal_links_count')->default(0);
            $table->unsignedInteger('external_links_count')->default(0);
            $table->unsignedInteger('images_count')->default(0);
            $table->unsignedInteger('images_missing_alt_count')->default(0);
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('checks')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_results');
    }
};

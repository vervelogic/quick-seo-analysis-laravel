<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entry_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['content_entry_id', 'content_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entry_tag');
    }
};

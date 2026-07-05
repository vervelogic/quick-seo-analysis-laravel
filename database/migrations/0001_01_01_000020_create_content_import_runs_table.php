<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('source')->default('quickseoanalysis_old_blog');
            $table->string('type')->default('blog');
            $table->string('status')->default('pending');
            $table->boolean('dry_run')->default(false);
            $table->json('summary')->nullable();
            $table->json('failures')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_import_runs');
    }
};

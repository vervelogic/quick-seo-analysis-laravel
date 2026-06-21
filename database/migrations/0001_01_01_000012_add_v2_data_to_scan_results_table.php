<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_results', function (Blueprint $table) {
            $table->json('technical_data')->nullable();
            $table->json('on_page_data')->nullable();
            $table->json('content_data')->nullable();
            $table->json('performance_data')->nullable();
            $table->json('security_data')->nullable();
            $table->json('social_data')->nullable();
            $table->json('structured_data')->nullable();
            $table->json('ai_readiness_data')->nullable();
            $table->json('score_breakdown')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('scan_results', function (Blueprint $table) {
            $table->dropColumn([
                'technical_data',
                'on_page_data',
                'content_data',
                'performance_data',
                'security_data',
                'social_data',
                'structured_data',
                'ai_readiness_data',
                'score_breakdown',
            ]);
        });
    }
};

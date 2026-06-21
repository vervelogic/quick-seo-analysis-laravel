<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_results', function (Blueprint $table) {
            foreach ([
                'topic_intelligence_data',
                'ranking_potential_data',
                'prompt_intelligence_data',
                'content_coverage_data',
                'ai_citation_readiness_data',
            ] as $column) {
                if (! Schema::hasColumn('scan_results', $column)) {
                    $table->json($column)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('scan_results', function (Blueprint $table) {
            foreach ([
                'topic_intelligence_data',
                'ranking_potential_data',
                'prompt_intelligence_data',
                'content_coverage_data',
                'ai_citation_readiness_data',
            ] as $column) {
                if (Schema::hasColumn('scan_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

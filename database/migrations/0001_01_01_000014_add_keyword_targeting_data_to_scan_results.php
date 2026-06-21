<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_results', function (Blueprint $table) {
            if (! Schema::hasColumn('scan_results', 'keyword_targeting_data')) {
                $table->json('keyword_targeting_data')->nullable()->after('ai_citation_readiness_data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scan_results', function (Blueprint $table) {
            if (Schema::hasColumn('scan_results', 'keyword_targeting_data')) {
                $table->dropColumn('keyword_targeting_data');
            }
        });
    }
};

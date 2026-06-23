<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('scans', 'scan_mode')) {
            Schema::table('scans', function (Blueprint $table): void {
                $table->string('scan_mode')->default('current_visibility')->after('normalized_url');
            });
        }

        if (! Schema::hasColumn('scans', 'target_keywords')) {
            Schema::table('scans', function (Blueprint $table): void {
                $table->json('target_keywords')->nullable()->after('scan_mode');
            });
        }

        if (! Schema::hasColumn('scan_results', 'keyword_alignment_data')) {
            Schema::table('scan_results', function (Blueprint $table): void {
                $table->json('keyword_alignment_data')->nullable()->after('keyword_targeting_data');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('scan_results', 'keyword_alignment_data')) {
            Schema::table('scan_results', function (Blueprint $table): void {
                $table->dropColumn('keyword_alignment_data');
            });
        }

        if (Schema::hasColumn('scans', 'target_keywords')) {
            Schema::table('scans', function (Blueprint $table): void {
                $table->dropColumn('target_keywords');
            });
        }

        if (Schema::hasColumn('scans', 'scan_mode')) {
            Schema::table('scans', function (Blueprint $table): void {
                $table->dropColumn('scan_mode');
            });
        }
    }
};

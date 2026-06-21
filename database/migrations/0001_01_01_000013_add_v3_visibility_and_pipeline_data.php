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
                'visibility_data',
                'ai_visibility_data',
                'geo_data',
                'aeo_data',
                'opportunity_data',
            ] as $column) {
                if (! Schema::hasColumn('scan_results', $column)) {
                    $table->json($column)->nullable();
                }
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'status')) {
                $table->string('status')->default('new')->index();
            }

            if (! Schema::hasColumn('leads', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('leads', 'last_contacted_at')) {
                $table->timestamp('last_contacted_at')->nullable();
            }

            if (! Schema::hasColumn('leads', 'source_report_uuid')) {
                $table->uuid('source_report_uuid')->nullable()->index();
            }
        });

        if (! Schema::hasTable('competitor_comparisons')) {
            Schema::create('competitor_comparisons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('scan_id')->nullable()->constrained()->nullOnDelete();
                $table->string('primary_domain');
                $table->json('competitor_domains')->nullable();
                $table->json('comparison_data')->nullable();
                $table->string('status')->default('placeholder')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('white_label_reports')) {
            Schema::create('white_label_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('report_template_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->json('branding')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pdf_reports')) {
            Schema::create('pdf_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('scan_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status')->default('placeholder')->index();
                $table->string('storage_path')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('scheduled_scans')) {
            Schema::create('scheduled_scans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('url', 2048);
                $table->string('frequency')->default('monthly');
                $table->timestamp('next_run_at')->nullable();
                $table->timestamp('last_run_at')->nullable();
                $table->boolean('is_active')->default(false);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_scans');
        Schema::dropIfExists('pdf_reports');
        Schema::dropIfExists('white_label_reports');
        Schema::dropIfExists('competitor_comparisons');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'legacy_id')) {
                $table->string('legacy_id')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'legacy_source')) {
                $table->string('legacy_source')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'legacy_imported_at')) {
                $table->timestamp('legacy_imported_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'legacy_login_provider')) {
                $table->string('legacy_login_provider')->nullable();
            }

            if (! Schema::hasColumn('users', 'invite_required')) {
                $table->boolean('invite_required')->default(false)->index();
            }

            if (! Schema::hasColumn('users', 'legacy_metadata')) {
                $table->json('legacy_metadata')->nullable();
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'legacy_company_logo')) {
                $table->string('legacy_company_logo')->nullable();
            }

            if (! Schema::hasColumn('companies', 'legacy_pdf_logo')) {
                $table->string('legacy_pdf_logo')->nullable();
            }

            if (! Schema::hasColumn('companies', 'legacy_company_description')) {
                $table->text('legacy_company_description')->nullable();
            }

            if (! Schema::hasColumn('companies', 'legacy_metadata')) {
                $table->json('legacy_metadata')->nullable();
            }
        });

        Schema::table('scans', function (Blueprint $table) {
            if (! Schema::hasColumn('scans', 'legacy_id')) {
                $table->string('legacy_id')->nullable()->index();
            }

            if (! Schema::hasColumn('scans', 'legacy_source')) {
                $table->string('legacy_source')->nullable()->index();
            }

            if (! Schema::hasColumn('scans', 'legacy_client_id')) {
                $table->string('legacy_client_id')->nullable()->index();
            }

            if (! Schema::hasColumn('scans', 'legacy_audit_type')) {
                $table->string('legacy_audit_type')->nullable();
            }

            if (! Schema::hasColumn('scans', 'legacy_score')) {
                $table->unsignedTinyInteger('legacy_score')->nullable();
            }

            if (! Schema::hasColumn('scans', 'legacy_created_at')) {
                $table->timestamp('legacy_created_at')->nullable()->index();
            }

            if (! Schema::hasColumn('scans', 'normalized_domain')) {
                $table->string('normalized_domain')->nullable()->index();
            }
        });

        Schema::create('legacy_report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('legacy_source')->index();
            $table->string('legacy_table')->index();
            $table->string('legacy_id')->index();
            $table->string('legacy_client_id')->nullable()->index();
            $table->text('source_url')->nullable();
            $table->longText('payload');
            $table->string('payload_hash', 64)->index();
            $table->json('metadata')->nullable();
            $table->timestamp('legacy_created_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['legacy_source', 'legacy_table', 'legacy_id']);
            $table->unique(['legacy_source', 'payload_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_report_snapshots');

        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn([
                'legacy_id',
                'legacy_source',
                'legacy_client_id',
                'legacy_audit_type',
                'legacy_score',
                'legacy_created_at',
                'normalized_domain',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'legacy_company_logo',
                'legacy_pdf_logo',
                'legacy_company_description',
                'legacy_metadata',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'legacy_id',
                'legacy_source',
                'legacy_imported_at',
                'legacy_login_provider',
                'invite_required',
                'legacy_metadata',
            ]);
        });
    }
};

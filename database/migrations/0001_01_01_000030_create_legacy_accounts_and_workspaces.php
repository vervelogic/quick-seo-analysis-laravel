<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workspaces')) {
            Schema::create('workspaces', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->index();
                $table->string('status')->default('active')->index();
                $table->json('settings')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'slug']);
            });
        }

        if (! Schema::hasTable('legacy_accounts')) {
            Schema::create('legacy_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('legacy_source')->index();
                $table->string('legacy_id')->index();
                $table->string('name')->nullable();
                $table->string('email')->nullable()->index();
                $table->string('status')->default('pending_claim')->index();
                $table->unsignedInteger('scan_count')->default(0);
                $table->unsignedInteger('report_count')->default(0);
                $table->timestamp('registered_at')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamp('claimed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['legacy_source', 'legacy_id']);
            });
        }

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'workspace_id')) {
                $table->foreignId('workspace_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'normalized_domain')) {
                $table->string('normalized_domain')->nullable()->after('website_url')->index();
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'legacy_source')) {
                $table->string('legacy_source')->nullable()->after('status')->index();
            }
        });

        Schema::table('scans', function (Blueprint $table) {
            if (! Schema::hasColumn('scans', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('scans', function (Blueprint $table) {
            if (! Schema::hasColumn('scans', 'workspace_id')) {
                $table->foreignId('workspace_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('scans', function (Blueprint $table) {
            if (! Schema::hasColumn('scans', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('workspace_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        foreach (['project_id', 'workspace_id', 'user_id'] as $column) {
            Schema::table('scans', function (Blueprint $table) use ($column) {
                if (Schema::hasColumn('scans', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            });
        }

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'workspace_id')) {
                $table->dropConstrainedForeignId('workspace_id');
            }
        });

        foreach (['normalized_domain', 'legacy_source'] as $column) {
            Schema::table('projects', function (Blueprint $table) use ($column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            });
        }

        Schema::dropIfExists('legacy_accounts');
        Schema::dropIfExists('workspaces');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            if (! Schema::hasColumn('companies', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('companies', 'subscription_status')) {
                $table->string('subscription_status')->default('free')->index();
            }

            if (! Schema::hasColumn('companies', 'subscription_renews_at')) {
                $table->timestamp('subscription_renews_at')->nullable();
            }

            if (! Schema::hasColumn('companies', 'plan_overrides')) {
                $table->json('plan_overrides')->nullable();
            }

            if (! Schema::hasColumn('companies', 'logo_path')) {
                $table->string('logo_path')->nullable();
            }

            if (! Schema::hasColumn('companies', 'primary_color')) {
                $table->string('primary_color', 20)->nullable();
            }

            if (! Schema::hasColumn('companies', 'secondary_color')) {
                $table->string('secondary_color', 20)->nullable();
            }

            if (! Schema::hasColumn('companies', 'accent_color')) {
                $table->string('accent_color', 20)->nullable();
            }

            if (! Schema::hasColumn('companies', 'contact_name')) {
                $table->string('contact_name')->nullable();
            }

            if (! Schema::hasColumn('companies', 'contact_email')) {
                $table->string('contact_email')->nullable();
            }

            if (! Schema::hasColumn('companies', 'contact_phone')) {
                $table->string('contact_phone')->nullable();
            }

            if (! Schema::hasColumn('companies', 'website_url')) {
                $table->string('website_url')->nullable();
            }

            if (! Schema::hasColumn('companies', 'billing_email')) {
                $table->string('billing_email')->nullable();
            }

            if (! Schema::hasColumn('companies', 'white_label_enabled')) {
                $table->boolean('white_label_enabled')->default(false)->index();
            }

            if (! Schema::hasColumn('companies', 'white_label_settings')) {
                $table->json('white_label_settings')->nullable();
            }

            if (! Schema::hasColumn('companies', 'feature_flags')) {
                $table->json('feature_flags')->nullable();
            }

            if (! Schema::hasColumn('companies', 'usage_limits')) {
                $table->json('usage_limits')->nullable();
            }

            if (! Schema::hasColumn('companies', 'usage_counters')) {
                $table->json('usage_counters')->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'company_role')) {
                $table->string('company_role')->default('viewer')->index();
            }

            if (! Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable();
            }

            if (! Schema::hasColumn('users', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable();
            }
        });

        Schema::table('plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('plans', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('plans', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->index();
            }

            if (! Schema::hasColumn('plans', 'monthly_scans')) {
                $table->unsignedInteger('monthly_scans')->nullable();
            }

            if (! Schema::hasColumn('plans', 'team_members')) {
                $table->unsignedInteger('team_members')->nullable();
            }

            if (! Schema::hasColumn('plans', 'storage_mb')) {
                $table->unsignedInteger('storage_mb')->nullable();
            }

            foreach ([
                'allows_white_label_reports',
                'allows_pdf_exports',
                'allows_ai_reports',
                'allows_api_access',
                'allows_competitor_tracking',
                'allows_scheduled_scans',
                'allows_projects',
                'allows_custom_branding',
            ] as $column) {
                if (! Schema::hasColumn('plans', $column)) {
                    $table->boolean($column)->default(false);
                }
            }
        });

        if (! Schema::hasTable('report_usages')) {
            Schema::create('report_usages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('scan_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action')->index();
                $table->string('channel')->nullable();
                $table->unsignedInteger('credits_used')->default(1);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->string('website_url')->nullable();
                $table->string('status')->default('active')->index();
                $table->json('settings')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'slug']);
            });
        }

        if (! Schema::hasTable('integration_connections')) {
            Schema::create('integration_connections', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('provider');
                $table->string('status')->default('disconnected')->index();
                $table->json('scopes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('connected_at')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'provider']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_connections');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('report_usages');

        Schema::table('plans', function (Blueprint $table): void {
            $columns = [
                'description',
                'sort_order',
                'monthly_scans',
                'team_members',
                'storage_mb',
                'allows_white_label_reports',
                'allows_pdf_exports',
                'allows_ai_reports',
                'allows_api_access',
                'allows_competitor_tracking',
                'allows_scheduled_scans',
                'allows_projects',
                'allows_custom_branding',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            foreach (['company_role', 'permissions', 'last_active_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('companies', function (Blueprint $table): void {
            if (Schema::hasColumn('companies', 'plan_id')) {
                $table->dropConstrainedForeignId('plan_id');
            }

            $columns = [
                'subscription_status',
                'subscription_renews_at',
                'plan_overrides',
                'logo_path',
                'primary_color',
                'secondary_color',
                'accent_color',
                'contact_name',
                'contact_email',
                'contact_phone',
                'website_url',
                'billing_email',
                'white_label_enabled',
                'white_label_settings',
                'feature_flags',
                'usage_limits',
                'usage_counters',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

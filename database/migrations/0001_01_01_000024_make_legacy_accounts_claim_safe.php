<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('legacy_accounts')) {
            Schema::table('legacy_accounts', function (Blueprint $table): void {
                if (! Schema::hasColumn('legacy_accounts', 'claimed_by_user_id')) {
                    $table->foreignId('claimed_by_user_id')->nullable()->after('claimed_at')->constrained('users')->nullOnDelete();
                }
            });

            Schema::table('legacy_accounts', function (Blueprint $table): void {
                if (Schema::hasColumn('legacy_accounts', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->change();
                }

                if (Schema::hasColumn('legacy_accounts', 'company_id')) {
                    $table->foreignId('company_id')->nullable()->change();
                }

                if (Schema::hasColumn('legacy_accounts', 'workspace_id')) {
                    $table->foreignId('workspace_id')->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('legacy_accounts') && Schema::hasColumn('legacy_accounts', 'claimed_by_user_id')) {
            Schema::table('legacy_accounts', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('claimed_by_user_id');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Starter workspace for basic visibility scans.',
                'price_cents' => 0,
                'interval' => 'month',
                'monthly_scans' => 10,
                'team_members' => 1,
                'storage_mb' => 100,
                'allows_white_label_reports' => false,
                'allows_pdf_exports' => false,
                'allows_ai_reports' => true,
                'allows_api_access' => false,
                'allows_competitor_tracking' => false,
                'allows_scheduled_scans' => false,
                'allows_projects' => false,
                'allows_custom_branding' => false,
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Small business plan with more scans and PDF exports.',
                'price_cents' => 2900,
                'interval' => 'month',
                'monthly_scans' => 100,
                'team_members' => 3,
                'storage_mb' => 1000,
                'allows_white_label_reports' => false,
                'allows_pdf_exports' => true,
                'allows_ai_reports' => true,
                'allows_api_access' => false,
                'allows_competitor_tracking' => false,
                'allows_scheduled_scans' => false,
                'allows_projects' => true,
                'allows_custom_branding' => false,
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Professional visibility plan for growing teams.',
                'price_cents' => 7900,
                'interval' => 'month',
                'monthly_scans' => 500,
                'team_members' => 10,
                'storage_mb' => 5000,
                'allows_white_label_reports' => true,
                'allows_pdf_exports' => true,
                'allows_ai_reports' => true,
                'allows_api_access' => true,
                'allows_competitor_tracking' => true,
                'allows_scheduled_scans' => true,
                'allows_projects' => true,
                'allows_custom_branding' => true,
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Agency',
                'slug' => 'agency',
                'description' => 'Agency plan with white-label reporting and expanded usage.',
                'price_cents' => 19900,
                'interval' => 'month',
                'monthly_scans' => 2500,
                'team_members' => 25,
                'storage_mb' => 25000,
                'allows_white_label_reports' => true,
                'allows_pdf_exports' => true,
                'allows_ai_reports' => true,
                'allows_api_access' => true,
                'allows_competitor_tracking' => true,
                'allows_scheduled_scans' => true,
                'allows_projects' => true,
                'allows_custom_branding' => true,
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Custom plan for large teams, custom branding, API access and advanced reporting.',
                'price_cents' => 0,
                'interval' => 'month',
                'monthly_scans' => null,
                'team_members' => null,
                'storage_mb' => null,
                'allows_white_label_reports' => true,
                'allows_pdf_exports' => true,
                'allows_ai_reports' => true,
                'allows_api_access' => true,
                'allows_competitor_tracking' => true,
                'allows_scheduled_scans' => true,
                'allows_projects' => true,
                'allows_custom_branding' => true,
                'sort_order' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->updateOrInsert(
                ['slug' => $plan['slug']],
                array_merge($plan, [
                    'features' => json_encode([]),
                    'limits' => json_encode([]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('plans')->whereIn('slug', [
            'free',
            'starter',
            'professional',
            'agency',
            'enterprise',
        ])->delete();
    }
};

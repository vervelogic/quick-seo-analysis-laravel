<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Plan;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['slug' => 'quick-seo-analysis'],
            [
                'name' => config('qsa.default_company_name'),
                'brand_settings' => [
                    'primary_color' => '#2563eb',
                    'accent_color' => '#14b8a6',
                ],
            ]
        );

        User::query()->firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'company_id' => $company->id,
                'name' => env('ADMIN_NAME', 'Admin User'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => 'owner',
                'is_admin' => true,
            ]
        );

        ReportTemplate::query()->firstOrCreate(
            ['slug' => 'default', 'company_id' => $company->id],
            ['name' => 'Default SEO Report', 'is_default' => true]
        );

        foreach (['Free', 'Starter', 'Agency'] as $name) {
            Plan::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'price_cents' => $name === 'Free' ? 0 : ($name === 'Starter' ? 2900 : 9900),
                    'features' => [
                        'seo_scans' => 'SEO scans',
                        'lead_capture' => 'Lead capture',
                        'report_history' => 'Report history',
                    ],
                    'limits' => ['scans_per_month' => $name === 'Free' ? 10 : 1000],
                ]
            );
        }
    }
}

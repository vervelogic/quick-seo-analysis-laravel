<?php

namespace App\Services\Widget;

use App\Models\Company;
use App\Models\WidgetKey;
use Illuminate\Support\Str;

class WidgetKeyManager
{
    public function createForCompany(Company $company, string $name): WidgetKey
    {
        return WidgetKey::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'key' => 'qsa_'.Str::random(40),
            'is_active' => true,
        ]);
    }
}

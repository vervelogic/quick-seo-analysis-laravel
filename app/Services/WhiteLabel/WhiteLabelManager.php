<?php

namespace App\Services\WhiteLabel;

use App\Models\Company;

class WhiteLabelManager
{
    public function settingsFor(?Company $company): array
    {
        return $company?->brand_settings ?? [];
    }
}

<?php

namespace App\Services\GeoVisibility;

use App\Models\Scan;

class GeoVisibilityAnalyzer
{
    public function analyze(Scan $scan): array
    {
        return [
            'enabled' => false,
            'summary' => 'GEO visibility checks are reserved for a future release.',
        ];
    }
}

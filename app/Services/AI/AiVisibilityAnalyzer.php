<?php

namespace App\Services\AI;

use App\Models\Scan;

class AiVisibilityAnalyzer
{
    public function analyze(Scan $scan): array
    {
        return [
            'enabled' => false,
            'summary' => 'AI visibility analysis is reserved for a future release.',
        ];
    }
}

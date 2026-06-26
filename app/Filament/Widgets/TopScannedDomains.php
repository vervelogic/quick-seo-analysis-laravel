<?php

namespace App\Filament\Widgets;

use App\Models\Scan;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class TopScannedDomains extends Widget
{
    protected static string $view = 'filament.widgets.top-scanned-domains';

    protected int|string|array $columnSpan = 'full';

    public function domains(): Collection
    {
        return Scan::query()
            ->whereNull('legacy_source')
            ->latest()
            ->limit(500)
            ->get(['normalized_url'])
            ->map(fn (Scan $scan): ?string => parse_url($scan->normalized_url, PHP_URL_HOST))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10);
    }
}

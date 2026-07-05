<?php

namespace App\Filament\Widgets;

use App\Models\ContentCategory;
use App\Models\ContentEntry;
use App\Models\ContentImportRun;
use App\Models\ContentRedirect;
use App\Models\ContentTag;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentCmsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $latestRun = ContentImportRun::query()->latest('started_at')->first();
        $failures = count((array) data_get($latestRun?->summary, 'failed_urls', []));

        return [
            Stat::make('Total blogs', (string) ContentEntry::query()->blogs()->count()),
            Stat::make('Published blogs', (string) ContentEntry::query()->blogs()->where('status', ContentEntry::STATUS_PUBLISHED)->count()),
            Stat::make('Drafts', (string) ContentEntry::query()->blogs()->where('status', ContentEntry::STATUS_DRAFT)->count()),
            Stat::make('Scheduled', (string) ContentEntry::query()->blogs()->where('status', ContentEntry::STATUS_SCHEDULED)->count()),
            Stat::make('Categories', (string) ContentCategory::count()),
            Stat::make('Tags', (string) ContentTag::count()),
            Stat::make('Imported blogs', (string) ContentEntry::query()->blogs()->whereNotNull('imported_at')->count()),
            Stat::make('Failed imports', (string) $failures),
            Stat::make('Redirects created', (string) ContentRedirect::count()),
        ];
    }
}

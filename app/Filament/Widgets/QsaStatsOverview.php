<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\Scan;
use App\Models\ScanResult;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QsaStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $averageVisibilityScore = (int) round(ScanResult::query()->avg('score') ?? 0);

        return [
            Stat::make('Total scans', Scan::query()->count()),
            Stat::make('Total leads', Lead::query()->count()),
            Stat::make('Average visibility score', $averageVisibilityScore.'/100'),
            Stat::make('Scans today', Scan::query()->whereDate('created_at', today())->count()),
            Stat::make('Leads today', Lead::query()->whereDate('created_at', today())->count()),
        ];
    }
}

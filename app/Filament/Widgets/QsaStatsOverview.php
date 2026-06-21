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
        $timezone = config('app.timezone', 'Asia/Kolkata');
        $todayStart = now($timezone)->startOfDay();
        $todayEnd = $todayStart->copy()->endOfDay();
        $averageVisibilityScore = (int) round(ScanResult::query()->avg('score') ?? 0);

        return [
            Stat::make('Total scans', Scan::query()->count()),
            Stat::make('Total leads', Lead::query()->count()),
            Stat::make('Average visibility score', $averageVisibilityScore.'/100'),
            Stat::make('Scans today', Scan::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count()),
            Stat::make('Leads today', Lead::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count()),
        ];
    }
}

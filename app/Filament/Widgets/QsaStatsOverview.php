<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\Scan;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QsaStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total scans', Scan::query()->count()),
            Stat::make('Completed scans', Scan::query()->where('status', 'completed')->count()),
            Stat::make('Captured leads', Lead::query()->count()),
            Stat::make('Admin users', User::query()->where('is_admin', true)->count()),
        ];
    }
}

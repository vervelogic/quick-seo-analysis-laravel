<?php

namespace App\Filament\Resources\ReportUsageResource\Pages;

use App\Filament\Resources\ReportUsageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReportUsages extends ListRecords
{
    protected static string $resource = ReportUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

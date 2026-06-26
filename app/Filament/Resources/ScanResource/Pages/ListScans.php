<?php

namespace App\Filament\Resources\ScanResource\Pages;

use App\Filament\Resources\ScanResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListScans extends ListRecords
{
    protected static string $resource = ScanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Scans'),
            'live' => Tab::make('Live QSA Scans')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereNull('legacy_source')
                    ->where('status', '!=', 'legacy_archived')),
            'legacy' => Tab::make('Legacy Archived Scans')
                ->badge(fn (): int => static::getResource()::getModel()::query()
                    ->where(fn (Builder $query): Builder => $query
                        ->where('status', 'legacy_archived')
                        ->orWhereNotNull('legacy_source'))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where(fn (Builder $query): Builder => $query
                        ->where('status', 'legacy_archived')
                        ->orWhereNotNull('legacy_source'))),
        ];
    }
}

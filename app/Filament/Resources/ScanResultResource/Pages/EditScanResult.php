<?php

namespace App\Filament\Resources\ScanResultResource\Pages;

use App\Filament\Resources\ScanResultResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScanResult extends EditRecord
{
    protected static string $resource = ScanResultResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}

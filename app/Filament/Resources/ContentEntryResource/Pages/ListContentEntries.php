<?php

namespace App\Filament\Resources\ContentEntryResource\Pages;

use App\Filament\Resources\ContentEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListContentEntries extends ListRecords
{
    protected static string $resource = ContentEntryResource::class;
}

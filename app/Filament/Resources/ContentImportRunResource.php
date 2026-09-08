<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentImportRunResource\Pages;
use App\Models\ContentImportRun;
use Filament\Forms\Form;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContentImportRunResource extends Resource
{
    protected static ?string $model = ContentImportRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Import Runs';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('source')->badge(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\IconColumn::make('dry_run')->boolean()->label('Dry run'),
                Tables\Columns\TextColumn::make('started_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('finished_at')->dateTime()->sortable()->toggleable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            KeyValueEntry::make('summary')->columnSpanFull(),
            KeyValueEntry::make('failures')->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentImportRuns::route('/'),
            'view' => Pages\ViewContentImportRun::route('/{record}'),
        ];
    }
}

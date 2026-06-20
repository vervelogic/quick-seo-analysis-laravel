<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScanResultResource\Pages;
use App\Models\ScanResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScanResultResource extends Resource
{
    protected static ?string $model = ScanResult::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Scan Results';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('scan_id')->relationship('scan', 'normalized_url')->searchable()->required(),
            Forms\Components\TextInput::make('score')->numeric()->required(),
            Forms\Components\TextInput::make('http_status')->numeric(),
            Forms\Components\Toggle::make('is_reachable'),
            Forms\Components\Toggle::make('uses_https'),
            Forms\Components\Textarea::make('title')->columnSpanFull(),
            Forms\Components\Textarea::make('meta_description')->columnSpanFull(),
            Forms\Components\TextInput::make('h1_count')->numeric(),
            Forms\Components\TextInput::make('canonical')->maxLength(2048)->columnSpanFull(),
            Forms\Components\TextInput::make('robots_meta')->maxLength(255),
            Forms\Components\Textarea::make('checks')
                ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT))
                ->dehydrateStateUsing(fn (?string $state): array => json_decode($state ?: '[]', true) ?: [])
                ->columnSpanFull(),
            Forms\Components\Textarea::make('recommendations')
                ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT))
                ->dehydrateStateUsing(fn (?string $state): array => json_decode($state ?: '[]', true) ?: [])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('scan.normalized_url')->label('URL')->limit(48)->searchable(),
                Tables\Columns\TextColumn::make('score')->sortable(),
                Tables\Columns\TextColumn::make('http_status')->label('HTTP'),
                Tables\Columns\IconColumn::make('is_reachable')->boolean(),
                Tables\Columns\IconColumn::make('uses_https')->boolean(),
                Tables\Columns\TextColumn::make('response_time_ms')->label('Time')->suffix(' ms')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScanResults::route('/'),
            'view' => Pages\ViewScanResult::route('/{record}'),
            'edit' => Pages\EditScanResult::route('/{record}/edit'),
        ];
    }
}

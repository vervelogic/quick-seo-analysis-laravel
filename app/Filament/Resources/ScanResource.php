<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScanResource\Pages;
use App\Models\Scan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScanResource extends Resource
{
    protected static ?string $model = Scan::class;
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('company_id')->relationship('company', 'name')->searchable()->preload(),
            Forms\Components\TextInput::make('url')->required()->maxLength(2048)->columnSpanFull(),
            Forms\Components\TextInput::make('normalized_url')->required()->maxLength(2048)->columnSpanFull(),
            Forms\Components\Select::make('status')->options([
                'pending' => 'Pending',
                'running' => 'Running',
                'completed' => 'Completed',
                'failed' => 'Failed',
            ])->required(),
            Forms\Components\Textarea::make('error_message')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('normalized_url')->label('URL')->searchable()->limit(48),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('result.score')->label('Score')->sortable(),
                Tables\Columns\TextColumn::make('result.http_status')->label('HTTP'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->timezone(config('app.timezone'))
                    ->suffix(' IST')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'running' => 'Running',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('public_report')
                    ->label('Report')
                    ->url(fn (Scan $record): string => route('report.show', ['scan' => $record->uuid]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScans::route('/'),
            'create' => Pages\CreateScan::route('/create'),
            'view' => Pages\ViewScan::route('/{record}'),
            'edit' => Pages\EditScan::route('/{record}/edit'),
        ];
    }
}

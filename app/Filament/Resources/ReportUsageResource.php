<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportUsageResource\Pages;
use App\Models\ReportUsage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportUsageResource extends Resource
{
    protected static ?string $model = ReportUsage::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'SaaS Platform';
    protected static ?string $navigationLabel = 'Report Usage';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('company_id')->relationship('company', 'name')->searchable()->preload(),
            Forms\Components\Select::make('user_id')->relationship('user', 'name')->searchable()->preload(),
            Forms\Components\Select::make('scan_id')->relationship('scan', 'url')->searchable()->preload(),
            Forms\Components\TextInput::make('action')->required()->maxLength(80),
            Forms\Components\TextInput::make('channel')->maxLength(80),
            Forms\Components\TextInput::make('credits_used')->numeric()->default(1)->required(),
            Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->placeholder('System'),
                Tables\Columns\TextColumn::make('action')->badge()->searchable(),
                Tables\Columns\TextColumn::make('channel')->placeholder('N/A'),
                Tables\Columns\TextColumn::make('credits_used')->label('Credits')->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->timezone(config('app.timezone'))
                    ->suffix(' IST')
                    ->sortable(),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportUsages::route('/'),
            'create' => Pages\CreateReportUsage::route('/create'),
            'edit' => Pages\EditReportUsage::route('/{record}/edit'),
        ];
    }
}

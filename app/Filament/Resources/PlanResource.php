<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'SaaS Platform';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Plan')
                ->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(120),
                    Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(160),
                    Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
                    Forms\Components\TextInput::make('price_cents')->numeric()->required(),
                    Forms\Components\Select::make('interval')
                        ->options(['month' => 'Month', 'year' => 'Year'])
                        ->required(),
                    Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Limits')
                ->schema([
                    Forms\Components\TextInput::make('monthly_scans')->numeric()->helperText('Leave empty for unlimited.'),
                    Forms\Components\TextInput::make('team_members')->numeric()->helperText('Leave empty for unlimited.'),
                    Forms\Components\TextInput::make('storage_mb')->numeric()->label('Storage MB')->helperText('Leave empty for unlimited.'),
                    Forms\Components\KeyValue::make('limits')->columnSpanFull(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Features')
                ->schema([
                    Forms\Components\Toggle::make('allows_white_label_reports')->label('White-label reports'),
                    Forms\Components\Toggle::make('allows_pdf_exports')->label('PDF exports'),
                    Forms\Components\Toggle::make('allows_ai_reports')->label('AI reports'),
                    Forms\Components\Toggle::make('allows_api_access')->label('API access'),
                    Forms\Components\Toggle::make('allows_competitor_tracking')->label('Competitor tracking'),
                    Forms\Components\Toggle::make('allows_scheduled_scans')->label('Scheduled scans'),
                    Forms\Components\Toggle::make('allows_projects')->label('Projects'),
                    Forms\Components\Toggle::make('allows_custom_branding')->label('Custom branding'),
                    Forms\Components\KeyValue::make('features')->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('price_cents')->money('usd', divideBy: 100),
                Tables\Columns\TextColumn::make('interval'),
                Tables\Columns\TextColumn::make('monthly_scans')->placeholder('Unlimited')->sortable(),
                Tables\Columns\TextColumn::make('team_members')->placeholder('Unlimited')->sortable(),
                Tables\Columns\IconColumn::make('allows_white_label_reports')->boolean()->label('White-label'),
                Tables\Columns\IconColumn::make('allows_pdf_exports')->boolean()->label('PDF'),
                Tables\Columns\IconColumn::make('allows_api_access')->boolean()->label('API'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}

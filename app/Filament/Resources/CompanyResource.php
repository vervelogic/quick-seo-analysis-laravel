<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'SaaS Platform';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Company Profile')
                ->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(160),
                    Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(180),
                    Forms\Components\TextInput::make('domain')->maxLength(190),
                    Forms\Components\TextInput::make('website_url')->url()->maxLength(255),
                    Forms\Components\TextInput::make('contact_name')->maxLength(255),
                    Forms\Components\TextInput::make('contact_email')->email()->maxLength(255),
                    Forms\Components\TextInput::make('contact_phone')->tel()->maxLength(255),
                    Forms\Components\TextInput::make('billing_email')->email()->maxLength(255),
                ])
                ->columns(2),

            Forms\Components\Section::make('Subscription')
                ->schema([
                    Forms\Components\Select::make('plan_id')
                        ->relationship('plan', 'name')
                        ->searchable()
                        ->preload()
                        ->label('Active plan'),
                    Forms\Components\Select::make('subscription_status')
                        ->options([
                            'free' => 'Free',
                            'trialing' => 'Trialing',
                            'active' => 'Active',
                            'past_due' => 'Past due',
                            'paused' => 'Paused',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('free'),
                    Forms\Components\DateTimePicker::make('subscription_renews_at'),
                    Forms\Components\KeyValue::make('plan_overrides')->columnSpanFull(),
                    Forms\Components\KeyValue::make('usage_limits')->columnSpanFull(),
                    Forms\Components\KeyValue::make('usage_counters')->columnSpanFull(),
                ])
                ->columns(3),

            Forms\Components\Section::make('White Label Branding')
                ->schema([
                    Forms\Components\Toggle::make('white_label_enabled')->label('Enable white-label reports'),
                    Forms\Components\FileUpload::make('logo_path')
                        ->image()
                        ->directory('company-logos')
                        ->label('Logo'),
                    Forms\Components\ColorPicker::make('primary_color'),
                    Forms\Components\ColorPicker::make('secondary_color'),
                    Forms\Components\ColorPicker::make('accent_color'),
                    Forms\Components\KeyValue::make('white_label_settings')->columnSpanFull(),
                    Forms\Components\KeyValue::make('brand_settings')->columnSpanFull(),
                    Forms\Components\KeyValue::make('settings')->columnSpanFull(),
                    Forms\Components\KeyValue::make('feature_flags')->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\TextColumn::make('domain')->searchable(),
                Tables\Columns\TextColumn::make('plan.name')->label('Plan')->placeholder('No plan'),
                Tables\Columns\TextColumn::make('subscription_status')->badge()->sortable(),
                Tables\Columns\IconColumn::make('white_label_enabled')->boolean()->label('White-label'),
                Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Users'),
                Tables\Columns\TextColumn::make('scans_count')->counts('scans')->label('Scans'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->timezone(config('app.timezone'))
                    ->suffix(' IST')
                    ->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}

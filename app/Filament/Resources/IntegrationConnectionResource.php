<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IntegrationConnectionResource\Pages;
use App\Models\IntegrationConnection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IntegrationConnectionResource extends Resource
{
    protected static ?string $model = IntegrationConnection::class;
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'SaaS Platform';
    protected static ?string $navigationLabel = 'Integrations';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->required(),
            Forms\Components\Select::make('provider')
                ->options([
                    'google_search_console' => 'Google Search Console',
                    'google_analytics_4' => 'Google Analytics 4',
                    'google_business_profile' => 'Google Business Profile',
                    'bing_webmaster_tools' => 'Bing Webmaster Tools',
                    'pagespeed_insights' => 'PageSpeed Insights',
                    'indexnow' => 'IndexNow',
                    'openai' => 'OpenAI',
                    'anthropic' => 'Anthropic',
                    'ahrefs' => 'Ahrefs',
                    'semrush' => 'Semrush',
                    'majestic' => 'Majestic',
                ])
                ->searchable()
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'disconnected' => 'Disconnected',
                    'pending' => 'Pending',
                    'connected' => 'Connected',
                    'error' => 'Error',
                ])
                ->default('disconnected')
                ->required(),
            Forms\Components\DateTimePicker::make('connected_at'),
            Forms\Components\KeyValue::make('scopes')->columnSpanFull(),
            Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable(),
                Tables\Columns\TextColumn::make('provider')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('connected_at')
                    ->dateTime('d M Y, h:i A')
                    ->timezone(config('app.timezone'))
                    ->suffix(' IST')
                    ->placeholder('Not connected'),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListIntegrationConnections::route('/'),
            'create' => Pages\CreateIntegrationConnection::route('/create'),
            'edit' => Pages\EditIntegrationConnection::route('/{record}/edit'),
        ];
    }
}

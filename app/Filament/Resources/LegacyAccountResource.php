<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegacyAccountResource\Pages;
use App\Models\LegacyAccount;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LegacyAccountResource extends Resource
{
    protected static ?string $model = LegacyAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Legacy Archive';
    protected static ?string $navigationLabel = 'Legacy Accounts';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_activity_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->placeholder('N/A'),
                Tables\Columns\TextColumn::make('status')->badge()->colors([
                    'warning' => LegacyAccount::STATUS_PENDING_CLAIM,
                    'success' => LegacyAccount::STATUS_CLAIMED,
                ])->sortable(),
                Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable()->placeholder('Not linked'),
                Tables\Columns\TextColumn::make('scan_count')->label('Scans')->sortable(),
                Tables\Columns\TextColumn::make('report_count')->label('Reports')->sortable(),
                Tables\Columns\TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->formatStateUsing(fn ($state): string => $state ? $state->timezone(config('app.timezone'))->format('d M Y, h:i A').' IST' : 'N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('claimed_at')
                    ->formatStateUsing(fn ($state): string => $state ? $state->timezone(config('app.timezone'))->format('d M Y, h:i A').' IST' : 'Pending')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    LegacyAccount::STATUS_PENDING_CLAIM => 'Pending Claim',
                    LegacyAccount::STATUS_CLAIMED => 'Claimed',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Legacy Account')
                ->schema([
                    Infolists\Components\TextEntry::make('legacy_source'),
                    Infolists\Components\TextEntry::make('legacy_id'),
                    Infolists\Components\TextEntry::make('name'),
                    Infolists\Components\TextEntry::make('email'),
                    Infolists\Components\TextEntry::make('status')->badge(),
                    Infolists\Components\TextEntry::make('scan_count'),
                    Infolists\Components\TextEntry::make('report_count'),
                    Infolists\Components\TextEntry::make('last_activity_at')
                        ->formatStateUsing(fn ($state): string => $state ? $state->timezone(config('app.timezone'))->format('d M Y, h:i A').' IST' : 'N/A'),
                    Infolists\Components\TextEntry::make('claimed_at')
                        ->formatStateUsing(fn ($state): string => $state ? $state->timezone(config('app.timezone'))->format('d M Y, h:i A').' IST' : 'Pending'),
                ])
                ->columns(2),
            Infolists\Components\Section::make('Workspace Link')
                ->schema([
                    Infolists\Components\TextEntry::make('company.name')->label('Company')->placeholder('Not linked'),
                    Infolists\Components\TextEntry::make('workspace.name')->label('Workspace')->placeholder('Not linked'),
                    Infolists\Components\TextEntry::make('user.email')->label('User')->placeholder('Not linked'),
                ])
                ->columns(3),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegacyAccounts::route('/'),
            'view' => Pages\ViewLegacyAccount::route('/{record}'),
        ];
    }
}

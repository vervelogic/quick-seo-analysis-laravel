<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScanResource\Pages;
use App\Models\Scan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                'legacy_archived' => 'Legacy Archived',
            ])->required(),
            Forms\Components\TextInput::make('normalized_domain')->maxLength(255),
            Forms\Components\TextInput::make('legacy_score')->label('Legacy score')->numeric()->disabled(),
            Forms\Components\TextInput::make('legacy_audit_type')->label('Legacy audit type')->disabled(),
            Forms\Components\TextInput::make('legacy_client_id')->label('Legacy client ID')->disabled(),
            Forms\Components\Textarea::make('error_message')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('legacySnapshot'))
            ->columns([
                Tables\Columns\TextColumn::make('legacy_source')
                    ->label('Archive')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Legacy Archive' : 'Live Scan')
                    ->color(fn (?string $state): string => $state ? 'warning' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('normalized_url')
                    ->label('Scanned URL')
                    ->searchable()
                    ->limit(48),
                Tables\Columns\TextColumn::make('normalized_domain')
                    ->label('Domain')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'legacy_archived' ? 'Legacy Archived' : str($state)->headline()->toString())
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'running' => 'info',
                        'legacy_archived' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('result.score')
                    ->label('Score')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('legacy_score')
                    ->label('Legacy Score')
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('legacy_audit_type')
                    ->label('Audit Type')
                    ->badge()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('legacy_created_at')
                    ->label('Legacy Created')
                    ->dateTime('d M Y, h:i A')
                    ->timezone(config('app.timezone'))
                    ->suffix(' IST')
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('legacy_client_id')
                    ->label('Legacy Client ID')
                    ->searchable()
                    ->copyable()
                    ->limit(18)
                    ->placeholder('N/A')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('legacySnapshot.id')
                    ->label('Snapshot')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('legacy_source')
                    ->label('Legacy Source')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('result.http_status')
                    ->label('HTTP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->timezone(config('app.timezone'))
                    ->suffix(' IST')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('legacy_archived')
                    ->label('Legacy Archived Scans')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'legacy_archived')
                        ->orWhereNotNull('legacy_source')),
                Tables\Filters\Filter::make('live_scans')
                    ->label('Live QSA Scans')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNull('legacy_source')
                        ->where('status', '!=', 'legacy_archived')),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'running' => 'Running',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'legacy_archived' => 'Legacy Archived',
                ]),
                Tables\Filters\SelectFilter::make('legacy_audit_type')
                    ->label('Legacy Audit Type')
                    ->options([
                        'Desktop' => 'Desktop',
                        'Mobile' => 'Mobile',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_legacy_snapshot')
                    ->label('View Legacy Snapshot')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn (Scan $record): bool => $record->legacySnapshot !== null)
                    ->modalHeading('Legacy Snapshot Metadata')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (Scan $record) => view('filament.resources.scan-resource.legacy-snapshot-modal', [
                        'snapshot' => $record->legacySnapshot,
                    ])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('public_report')
                    ->label('Report')
                    ->url(fn (Scan $record): string => route('report.show', ['scan' => $record->uuid]))
                    ->openUrlInNewTab()
                    ->visible(fn (Scan $record): bool => $record->status !== 'legacy_archived'),
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

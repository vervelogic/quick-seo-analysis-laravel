<?php

namespace App\Filament\Resources\LegacyAccountResource\RelationManagers;

use App\Filament\Resources\ScanResource;
use App\Models\Scan;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ScansRelationManager extends RelationManager
{
    protected static string $relationship = 'scans';

    protected static ?string $title = 'Scanned URLs';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('normalized_url')
            ->defaultSort('legacy_created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('legacySnapshot'))
            ->columns([
                Tables\Columns\TextColumn::make('normalized_url')
                    ->label('Scanned URL')
                    ->searchable()
                    ->wrap()
                    ->copyable(),
                Tables\Columns\TextColumn::make('normalized_domain')
                    ->label('Normalized Domain')
                    ->searchable()
                    ->sortable()
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
                Tables\Columns\TextColumn::make('legacy_score')
                    ->label('Legacy Score')
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('legacy_audit_type')
                    ->label('Audit Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ScanResource::displayAuditTypeForLegacy($state))
                    ->color(fn (?string $state): string => match (ScanResource::displayAuditTypeForLegacy($state)) {
                        'Desktop' => 'info',
                        'Mobile' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('legacy_created_at')
                    ->label('Legacy Created')
                    ->dateTime('d M Y, h:i A')
                    ->timezone(config('app.timezone'))
                    ->suffix(' IST')
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\IconColumn::make('legacySnapshot.id')
                    ->label('Snapshot Available')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('legacy_audit_type')
                    ->label('Audit Type')
                    ->options([
                        'Desktop' => 'Desktop',
                        'Mobile' => 'Mobile',
                        'Unknown' => 'Unknown',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if (! $value || $value === 'Unknown') {
                            if ($value === 'Unknown') {
                                return $query->where(function ($subQuery) {
                                    $subQuery
                                        ->whereNull('legacy_audit_type')
                                        ->orWhere('legacy_audit_type', '')
                                        ->orWhereNotIn('legacy_audit_type', ['Desktop', 'Mobile']);
                                });
                            }

                            return $query;
                        }

                        return $query->where('legacy_audit_type', $value);
                    }),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view_scan')
                    ->label('View Scan')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Scan $record): string => ScanResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
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
            ])
            ->bulkActions([]);
    }
}

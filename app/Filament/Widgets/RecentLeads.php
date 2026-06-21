<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentLeads extends BaseWidget
{
    protected static ?string $heading = 'Recent leads';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Lead::query()->with(['assignedUser', 'scan'])->latest()->limit(8))
            ->columns([
                Tables\Columns\TextColumn::make('name')->default('Unknown')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('assignedUser.name')->label('Owner')->default('Unassigned'),
                Tables\Columns\TextColumn::make('scan.normalized_url')->label('Report')->limit(44),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ]);
    }
}
